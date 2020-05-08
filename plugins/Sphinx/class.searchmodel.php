<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use \Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;
use Garden\Container\Container;
use Vanilla\Adapters\SphinxClient;

/**
 * Sphinx Search Model
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @package internal
 */
class SphinxSearchModel extends \SearchModel {

    /// PROPERTIES ///

    public $types = [];
    public $typeInfo = [];
    public $dataPath = '/var/data/searchd';
    public $logPath = '/var/log/searchd';
    public $runPath = '/var/run/searchd';
    public $useDeltas;

    public static $maxResults = 1000;
    public static $ranker = [];

    protected $_fp = null;

    /**
     * Sphinx Client Object
     * @var SphinxClient
     */
    protected $_sphinxClient = null;

    /** @var SearchRecordTypeProviderInterface  */
    private $searchRecordTypeProvider;

   /** @var Container  */
   private $container;

    /**
     * Constructor
     *
     */
    public function __construct(SearchRecordTypeProviderInterface $searchRecordTypeProvider, Container $container) {
        // Bit of a kludge, but we need these functions even if advanced search is disabled.
        require_once PATH_PLUGINS . '/AdvancedSearch/class.search.php';

        $this->searchRecordTypeProvider = $searchRecordTypeProvider;
        $this->container = $container;

        $this->useDeltas = c('Plugins.Sphinx.UseDeltas');

        self::$ranker['score'] = [
            'items'     => [-5, 0, 1, 3, 5, 10, 19],
            'add'       => -1,
            'weight'    => 1
        ];

        self::$ranker['dateinserted'] = [
            'items' => [
                strtotime('-2 years'),
                strtotime('-1 year'),
                strtotime('-6 months'),
                strtotime('-3 months'),
                strtotime('-1 month'),
                strtotime('-1 week'),
                strtotime('-1 day')
            ],
            'add' => -4,
            'weight' => 1
        ];

        parent::__construct();
    }

    public static function interval($val, $items) {
        if ($val < $items[0]) {
            return 0;
        }

        foreach ($items as $i => $val2) {
            if ($val < $val2) {
                return $i;
            }
        }
        return $i + 1;
    }

    public function addTypeInfo($type, $getCallback, $indexCallback = null) {
        if (!is_numeric($type)) {
            $type = array_search($type, $this->types);
        }

        $this->typeInfo[$type] = ['GetCallback' => $getCallback, 'IndexCallback' => $indexCallback];
    }

    /**
     * Get comments by ID list
     *
     * @param array $iDs
     * @return array
     */
    public function getComments($iDs) {

        $this->fireEvent("GetComments");

        $sql = Gdn::sql()
            ->select('c.CommentID as PrimaryID, c.CommentID, d.DiscussionID, c.Body as Summary, c.Format, d.CategoryID')
            ->select('"RE: ", d.Name', 'concat', 'Title')
            ->select('c.DateInserted, c.Score, d.Type')
            ->select('c.InsertUserID as UserID');

        if (Gdn::applicationManager()->checkApplication('Groups')) {
            $sql->select('d.GroupID');
        }

        $result = $sql->from('Comment c')
            ->join('Discussion d', 'd.DiscussionID = c.DiscussionID')
            ->whereIn('c.CommentID', $iDs)
            ->get()->resultArray();

        foreach ($result as &$row) {
            $row['RecordType'] = 'Comment';
            $row['Url'] = commentUrl($row, '/');
        }

        return $result;
    }

    /**
     * Get discussions by ID list
     *
     * @param array $iDs
     * @return array
     */
    public function getDiscussions($iDs) {
        $this->fireEvent("GetDiscussions");

        $sql = Gdn::sql()
            ->select('d.DiscussionID as PrimaryID, d.DiscussionID, d.Name as Title, d.Body as Summary, d.Format, d.CategoryID')
            ->select('d.DateInserted, d.Score, d.Type')
            ->select('d.InsertUserID as UserID');

        if (Gdn::applicationManager()->checkApplication('Groups')) {
            $sql->select('d.GroupID');
        }

        $result = $sql->from('Discussion d')
            ->whereIn('d.DiscussionID', $iDs)
            ->get()->resultArray();

        foreach ($result as &$row) {
            $row['RecordType'] = 'Discussion';
            $row['Name'] = $row['Title'];
            $row['Url'] = discussionUrl($row, '', '/');
            unset($row['Name']);
        }
        return $result;
    }

    /**
     * Get groups by ID list
     *
     * @param array $iDs
     * @return array
     */
    public function getGroups($iDs) {

        $this->fireEvent("GetGroups");

        $sql = Gdn::sql();
        $sql->select('g.GroupID as PrimaryID, g.GroupID, g.Name as Title, g.Description as Summary, g.Format, 0 as CategoryID')
            ->select('g.DateInserted, 1000 as Score, \'group\' as Type')
            ->select('g.InsertUserID as UserID');

        $result = $sql->from('Group g')
            ->whereIn('g.GroupID', $iDs)
            ->get()->resultArray();

        foreach ($result as &$row) {
            $row['RecordType'] = 'Group';
            $row['Name'] = $row['Title'];
            $row['Url'] = groupUrl($row, '', '/');
            unset($row['Name']);
        }

        return $result;
    }

    public function getDocuments($search) {
        if (!is_array($search) || !isset($search['matches'])) {
            return [];
        }

       $grouppedIDs = [];
       foreach ($search['matches'] as $guid => $record) {
          $matches[$guid]['orderIndex'] = $record['weight'];
          $recordType = $this->searchRecordTypeProvider->getByDType($record['attrs']['dtype']);
          $grouppedIDs[$recordType->getDType()][] = $recordType->getRecordID($guid);
       };
       $docs = [];
       // Grab all of the documents.
       foreach ($grouppedIDs as $dtype => $recordIDs) {
          array_push($docs, ...$this->searchRecordTypeProvider->getByDType($dtype)->getDocuments($recordIDs, $this));
       }
        $docs = array_combine(array_column($docs, 'guid'), $docs);
       // Join them with the search results.
        $result = [];
        foreach ($search['matches'] as $documentID => $info) {
            $row = val($documentID, $docs);
            if ($row === false) {
                continue;
            }

            $row['Relevance'] = $info['weight'];
            $row['Score'] = $info['attrs']['score'];
            $row['Count'] = val('@count', $info['attrs'], 1);
            $row['sort'] = val('sort', $info['attrs']);

            if (!isset($row['DiscussionID'])) {
                $row['DiscussionID'] = val('discussionid', $info['attrs']);
            }

            $result[] = $row;
        }
        return $result;
    }

    public function advancedSearch($search, $offset = 0, $limit = 10, $clean = true) {
        /** @var SphinxClient */
        $sphinx = $this->sphinxClient();
        $sphinx->setLimits($offset, $limit, self::$maxResults);

        // Filter the search into proper terms.
        if ($clean) {
            // This $searchDirty variable contains initial arguments passed to the function
            // cleanSearch() is replacing some of them and ['cat'] value in particular
            // when 'cat' is not valid categoryID it got replaced with bolean 'false' value
            // and sphinx client setFilter() fails with some not proper handled exception when called.
            $searchDirty = $search;
            $search = Search::cleanSearch($search, $clean === 'api');
        }

        $doSearch = $search['dosearch'];
        $filtered = false;
        $indexes = $this->indexes();
        $query = '';
        $terms = [];

        if (isset($search['search'])) {
            list($query, $terms) = $this->splitTags($search['search']);
        }
        $this->setSort($sphinx, $terms, $search);

        // Set the filters based on the search.
        if (isset($search['cat'])) {
           if ($search['cat'] === false) {
              $catFilterValues = [$searchDirty['cat']];
           } else {
              $catFilterValues = (array) $search['cat'];
           }
           $sphinx->setFilter('CategoryID', $catFilterValues);
           $filtered &= (count($catFilterValues) > 0);
        }

        if (isset($search['timestamp-from'])) {
            $sphinx->setFilterRange('DateInserted', $search['timestamp-from'], $search['timestamp-to']);
            $filtered = true;
        } else if (isset($search['date-filters'])) {
            $dtZone = new DateTimeZone('UTC');

            $fromDate = array_shift($search['date-filters']);
            $adjustedFrom = new DateTime('@'.$fromDate->getTimestamp());
            $adjustedFrom->setTimezone($dtZone);

            // We want an exact "date" but we still need to use setFilterRange().
            if (count($search['date-filters']) === 0) {
                $adjustedTo = $adjustedFrom;
            } else {
                $toDate = array_shift($search['date-filters']);
                $adjustedTo = new DateTime('@'.$toDate->getTimestamp());
                $adjustedTo->setTimezone($dtZone);
            }

            $sphinx->setFilterRange('DateInserted', $adjustedFrom->getTimestamp(), $adjustedTo->getTimestamp());
            $filtered = true;
        }

        if (isset($search['title'])) {
            $indexes = $this->indexes(['Discussion']);
            list($titleQuery, $titleTerms) = $this->splitTags($search['title']);
            $query .= ' @name ' . $titleQuery;
            $terms = array_merge($terms, $titleTerms);
        }

        if (isset($search['users'])) {
            $sphinx->setFilter('InsertUserID', array_column($search['users'], 'UserID'));
            $filtered = true;
        }

        if (isset($search['tags'])) {
            if (val('tags-op', $search) == 'and') {
                foreach ($search['tags'] as $row) {
                    $sphinx->setFilter('Tags', (array) $row['TagID']);
                }
            } else {
                $sphinx->setFilter('Tags', array_column($search['tags'], 'TagID'));
            }

            $filtered = true;
        }

        if (isset($search['types'])) {
            $indexes = [];
            $idxWeights = [];
            $values = [];
            /** @var \Vanilla\Contracts\Search\SearchRecordTypeInterface $recordType */
           foreach ($search['types'] as $recordType) {
                $indexes[] = $idxName = $recordType->getIndexName();
                $idxWeights[$idxName] = $recordType->getIndexWeight();
                $values[] = $recordType->getDType();
            }

            $sphinx->setFilter('dtype', $values);
            $indexes = $this->indexes($indexes);
            $idxWeights = $this->dbIndexNames($idxWeights);
            $sphinx->setIndexWeights($idxWeights);
        }

        if (isset($search['discussionid'])) {
            $sphinx->setFilter('DiscussionID', (array) $search['discussionid']);
        }

        if ($search['group']) {
            $sphinx->setGroupBy('DiscussionID', SphinxClient::GROUPBY_ATTR, 'sort DESC');
        }

        $results['Search'] = $search;

        if ($doSearch) {
            if ($filtered && empty($query)) {
                $sphinx->setRankingMode(SphinxClient::RANK_PROXIMITY);
            }
            $results = $this->doSearch($sphinx, $query, $indexes);
            $results['SearchTerms'] = array_unique($terms);
        } else {
            $results = ['SearchResults' => [], 'RecordCount' => 0, 'SearchTerms' => $terms];
        }
        $results['CalculatedSearch'] = $search;
        return $results;
    }

    public function autoComplete($search, $limit = 10) {
        $sphinx = $this->sphinxClient();
        $sphinx->setLimits(0, $limit, 100);
        $search = Search::cleanSearch($search);

        $str = $search['search'];
        list ($query, $terms) = $this->splitTags($str);

        if (isset($search['cat'])) {
            $sphinx->setFilter('CategoryID', (array) $search['cat']);
        }
        if (isset($search['discussionid'])) {
            $sphinx->setFilter('DiscussionID', (array) $search['discussionid']);
        }
        if (isset($search['tags'])) {
            if (val('tags-op', $search) == 'and') {
                foreach ($search['tags'] as $trow) {
                    $sphinx->setFilter('Tags', (array) $trow['TagID']);
                }
            } else {
                $sphinx->setFilter('Tags', array_column($search['tags'], 'TagID'));
            }
        }
       $dtypes = [];
       $indexes = [];
        if (empty($search['types'])) {
              /** @var \Vanilla\Contracts\Search\SearchRecordTypeInterface $recordType */
              foreach ($this->searchRecordTypeProvider->getAll() as $recordType) {
                 $indexes[] = $recordType->getIndexName();
                 $dtypes[] = $recordType->getDType();
              }
        } else {
           /** @var \Vanilla\Contracts\Search\SearchRecordTypeInterface $recordType */
           foreach ($search['types'] as $recordType) {
              $indexes[] = $recordType->getIndexName();
              $dtypes[] = $recordType->getDType();
           }
        }
       $sphinx->setFilter('dtype', $dtypes);
       $indexes = $this->indexes($indexes);

        $this->setSort($sphinx, $terms, $search);
        $results = $this->doSearch($sphinx, $query, $indexes);
        $results['SearchTerms'] = $terms;

        return $results;
    }

    /**
     * Do a search on group names for auto-complete look ups.
     *
     * @param string $search The search term.
     * @param int $limit The number of results to return.
     * @return array Returns the search results.
     */
    public function groupAutoComplete($search, $limit = 10) {
        $sphinx = $this->sphinxClient();
        $sphinx->setLimits(0, $limit, 100);
        $indexes = $this->indexes(['Group']);

        $search = Search::cleanSearch($search);

        $str = $search['search'];
        list ($query, $terms) = $this->splitTags($str);

        $this->setSort($sphinx, $terms, $search);
        $results = $this->doSearch($sphinx, $query, $indexes);
        $results['SearchTerms'] = $terms;

        return $results;
    }

    /**
     * Perform a search.
     *
     * @param SphinxClient $sphinx
     * @param string $query
     * @param array $indexes
     * @return array
     * @throws Exception If there is an error while performing the search.
     */
    protected function doSearch($sphinx, $query, $indexes) {
        $this->EventArguments['SphinxClient'] = $sphinx;
        $this->fireEvent('BeforeSphinxSearch');
        $search = $sphinx->query($query, implode(' ', $indexes));
        if ($search === false) {
            $error = $sphinx->getLastError();
            throw new \Exception($error, 500);
        }

        $results = $this->getDocuments($search);
        $total = $search['total'] ?? 0;
        $controller = Gdn::controller();
        $searchTerms = val('words', $search);
        if ($controller) {
            $controller->setData('RecordCount', $total);
        }
        if (is_array($searchTerms)) {
            $searchTerms = array_keys($searchTerms);
        } else {
            $searchTerms = [];
        }

        unset($search['matches']);

        return [
            'SearchResults' => $results,
            'RecordCount' => $total,
            'SearchTerms' => $searchTerms
        ];
    }

    /**
     * Perform an advanced search and use a model to expand the records.
     *
     * @param Gdn_Model $model An instance of Gdn_Model or its subclasses.
     * @param string $query The search query.
     * @param array $params Parameters being passed to advancedSearch. $query is added in the "search" key.
     * @param int|null $limit The maximum number of records to return.
     * @param int|null $offset An offset to use in conjunction with $limit for pagination.
     * @param bool $expandInsertUser Expand rows with user records associated with creating them.
     * @return array
     */
    public function modelSearch(Gdn_Model $model, $query, array $params, $limit = null, $offset = null, $expandInsertUser = false) {
        $params['search'] = $query;
        $search = $this->advancedSearch($params, $offset, $limit);
        $rows = $search['SearchResults'];
        $recordIDs = array_column($rows, 'PrimaryID');
        $primaryKey = $model->PrimaryKey;
        $result = [];

        if (!empty($recordIDs)) {
            $result = $model
                ->getWhere([$primaryKey => $recordIDs])
                ->resultArray();
        }

        if ($expandInsertUser) {
            $userModel = new UserModel();
            $userModel->expandUsers($result, ['InsertUserID']);
        }

        return $result;
    }

   /**
    * @param string[] $indexNames
    * @return array
    */
    public function indexes(array $indexNames = []) {
        $indexes = [];
        $prefix = str_replace(['-'], '_', c('Database.Name')) . '_';
        foreach ($indexNames as $name) {
           // for some undescovered reason yet Group type has no index attached
           if (!empty($name)) {
              $indexes[] = $prefix . $name;
           }
        }

        if ($this->useDeltas) {
            foreach ($indexes as $name) {
                $indexes[] = $name . '_Delta';
            }
        }
        return $indexes;
    }

    /**
     * Replace spinx index names with real names adjusted for current DB.
     *
     * @param string[] $indexWeights
     * @return array
     */
    public function dbIndexNames(array $indexWeights): array {
        $indexes = [];
        $prefix = str_replace(['-'], '_', c('Database.Name')) . '_';
        foreach ($indexWeights as $idxName => $weight) {
            $indexes[$prefix . $idxName] = $weight;
        }

        if ($this->useDeltas) {
            foreach ($indexes as $idxName => $weight) {
                $indexes[ $idxName . '_Delta'] = $weight;
            }
        }
        return $indexes;
    }

    /**
     * Whether or not groups can be searched.
     *
     * @return bool Returns true if groups can be searched or false otherwise.
     */
    public static function searchGroups() {
        $result = in_array('groups', c('Plugins.Sphinx.Templates', [])) && Gdn::applicationManager()->isEnabled('Groups');
        return $result;
    }

    /**
     * Get SphinxClient object
     *
     * @return SphinxClient
     */
    public function sphinxClient() {
        $sphinxHost = c('Plugins.Sphinx.Server', c('Database.Host', 'localhost'));
        $sphinxPort = c('Plugins.Sphinx.Port', 9312);

        $this->_sphinxClient = new SphinxClient();
        $this->_sphinxClient->setServer($sphinxHost, $sphinxPort);

        $this->_sphinxClient->setSortMode(SphinxClient::SORT_RELEVANCE);
        $this->_sphinxClient->setRankingMode(SphinxClient::RANK_SPH04);
        $this->_sphinxClient->setMaxQueryTime(5000);
        $this->_sphinxClient->setFieldWeights(['name' => 3, 'body' => 1]);

        return $this->_sphinxClient;
    }

    public function search($terms, $offset = 0, $limit = 20) {
        $search = ['search' => $terms, 'group' => false];
        $controller = Gdn::controller();
        if ($controller && $categoryID = $controller->Request->get('CategoryID')) {
            $search['cat'] = $categoryID;
        }

        $results = $this->advancedSearch($search, $offset, $limit);
        return $results['SearchResults'];
    }

    /**
     * Set soritng for Sphinx object
     *
     * @param SphinxClient $sphinx
     * @param array $terms
     * @param array $search
     */
    public function setSort($sphinx, $terms, $search) {
        if (!isset($search['sort'])) {
            $sphinx->setSelect("*, WEIGHT() + IF(dtype=5,2,1)*dateinserted/1000 AS sorter");
            $sphinx->setSortMode(SphinxClient::SORT_EXTENDED, "sorter DESC");
        } elseif (val('sort', $search) === 'date' || (count($terms) < 2 && val('sort', $search) !== 'relevance')) {
            // If there is just one search term then we really want to just sort by date.
            $sphinx->setSelect('*, (dateinserted + 1) as sort');
            $sphinx->setSortMode(SphinxClient::SORT_ATTR_DESC, 'sort');
        } else {
            $funcs = [];
            foreach (self::$ranker as $field => $row) {
                $items = $row['items'];
                $weight = $row['weight'];
                $add = $row['add'];

                $func = "interval($field, " . implode(', ', $items) . ")";
                if ($add > 0) {
                    $func = "($func +$add)";
                } elseif ($add < 0) {
                    $func = "($func $add)";
                }

                if ($weight != 1) {
                    $func .= " * $weight";
                }

                $funcs[] = "$func";
            }
            $maxScore = self::maxScore();

            if ($maxScore > 0) {
                $mult = 1 / $maxScore;

                $fullfunc = implode(' + ', $funcs);
                $sort = "(($fullfunc) * $mult + 1) * WEIGHT()";
                trace($sort, 'sort');

                $sphinx->setSelect("*, $sort as sort");

                $sphinx->setSortMode(SphinxClient::SORT_ATTR_DESC, 'sort');
            }
        }
    }

    public static function addNotes(&$row, $terms) {
        $map = ['score' => ['Score', 'score'], 'dateinserted' => ['DateInserted', 'date']];
        $maxscore = self::maxScore();

        if ($maxscore == 0) {
            return '';
        }

        $notes = ['rank: ' . $row['Relevance']];
        $mult = 1 / $maxscore;
        $totalInt = 0;

        foreach (self::$ranker as $name => $info) {
            list($field, $label) = $map[$name];

            $val = $row[$field];
            if ($name == 'dateinserted') {
                $val = strtotime($val);
            }

            $int = (self::interval($val, $info['items']) + $info['add']);
            $totalInt += $int;
            $notes[] = sprintf('%s(%s): %+d%%', $label, $val, $int * 100 * $mult);
        }

        $calcRank = (1 + $mult * $totalInt) * $row['Relevance'];
        $notes[] = sprintf('total: %d', $calcRank);
        $notes[] = 'expr: ' . round($row['sort']);

        $notes[] = "mult: " . round($mult);
        return implode(' ', $notes);
    }

    protected static function maxScore() {
        $maxScore = 0;
        foreach (self::$ranker as $field => $row) {
            $items = $row['items'];
            $weight = $row['weight'];
            $add = $row['add'];
            $maxScore += $weight * (count($items) + $add);
        }

        return $maxScore;
    }

    public function splitTags($search) {
        $sphinx = $this->sphinxClient();
        $search = preg_replace('`\s`', ' ', $search);
        $tokens = preg_split('`([\s"+=-])`', $search, -1, PREG_SPLIT_DELIM_CAPTURE);
        $tokens = array_filter($tokens);
        $inquote = false;
        $inword = false;

        $queries = [];
        $terms = [];
        $query = ['', '', ''];

        $hasops = false; // whether or not search has operators

        foreach ($tokens as $c) {
            // Figure out where to push the token.
            switch ($c) {
                case '+':
                case '-':
                case '=':
                    if ($inquote || $inword) {
                        $query[1] .= $c;
                    } elseif (!$query[0]) {
                        $query[0] .= $c;
                    } else {
                        $query[1] .= $c;
                    }
                    $hasops = true;
                    break;
                case '"':
                    if ($inquote) {
                        $query[2] = $c;
                        $inquote = false;
                        $inword = false;
                    } else {
                        $query[0] .= $c;
                        $inquote = true;
                    }
                    $hasops = true;
                    break;
                case ' ':
                    if ($inquote) {
                        $query[1] .= $c;
                    } else {
                        $inword = false;
                    }
                    break;
                default:
                    $query[1] .= $c;
                    $inword = true;
                    break;
            }

            // Now split the query into terms and move on.
            if ($query[2] || ($query[1] && !$inquote && !$inword)) {
                $queries[] = $query[0] . $sphinx->escapeString($query[1]) . $query[2];
                $terms[] = $query[1];
                $query = ['', '', ''];
            }
        }
        // Account for someone missing their last quote.
        if ($inquote && $query[1]) {
            $queries[] = $query[0] . $sphinx->escapeString($query[1]) . '"';
            $terms[] = $query[1];
        } elseif ($inword && $query[1]) {
            $queries[] = $query[0] . $sphinx->escapeString($query[1]);
            $terms[] = $query[1];
        }

        // Now we need to convert the queries into sphinx syntax.
        $firstmod = false; // whether first term had a modifier.
        $finalqueries = [];
        $quorums = [];

        foreach ($queries as $i => $query) {
            $c = substr($query, 0, 1);
            if ($c == '+') {
                $finalqueries[] = substr($query, 1);
                $firstmod = $i == 0;
            } elseif ($c == '-' || $c == '=') {
                $finalqueries[] = $c . substr($query, 1);
                $firstmod = $i == 0;
            } elseif ($c == '"') {
                if (!$firstmod && count($finalqueries) > 0) {
                    $query = '| ' . $query;
                }
                $finalqueries[] = $query;
            } else {
                // Collect this term into a list for the quorum operator.
                $quorums[] = $query;
            }
        }
        // Calculate the quorum.
        if (count($quorums) <= 2) {
            $quorum = implode(' ', $quorums);
        } else {
            $quorum = '"' . implode(' ', $quorums) . '"/' . round(count($quorums) * .6); // must have at least 60% of search terms
        }

        $finalquery = implode(' ', $finalqueries) . ' ' . $quorum;

//      return array($search, array_unique($terms));
        return [trim($finalquery), array_unique($terms)];
    }

}
