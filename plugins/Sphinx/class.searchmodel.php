<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

if (!defined('SPH_RANK_SPH04')) {
    define('SPH_RANK_SPH04', 7);
}

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
    public static $rankingMode = SPH_RANK_SPH04; //SPH_RANK_PROXIMITY_BM25;
    public static $typeMap = [
        'd' => 0,
        'c' => 100,
        'question' => 1,
        'poll' => 2,
        'answer' => 101,
        'group' => 400
    ];

    protected $_fp = null;

    /**
     * Sphinx Client Object
     * @var SphinxClient
     */
    protected $_sphinxClient = null;

    /// METHODS ///

    /**
     * Constructor
     *
     */
    public function __construct() {
        // Bit of a kludge, but we need these functions even if advanced search is disabled.
        require_once PATH_PLUGINS . '/AdvancedSearch/class.search.php';

        $this->useDeltas = c('Plugins.Sphinx.UseDeltas');

        if (array_key_exists("Vanilla", Gdn::applicationManager()->enabledApplications())) {
            $this->types[1] = 'Discussion';
            $this->addTypeInfo('Discussion', [$this, 'getDiscussions'], [$this, 'indexDiscussions']);

            $this->types[2] = 'Comment';
            $this->addTypeInfo('Comment', [$this, 'getComments'], [$this, 'indexComments']);
        }

        if (array_key_exists("Pages", Gdn::applicationManager()->enabledApplications())) {
            $this->types[3] = 'Page';
            $this->addTypeInfo('Page', [$this, 'getPages'], [$this, 'indexPages']);
        }

        if (static::searchGroups()) {
            $this->types[4] = 'Group';
            $this->addTypeInfo('Group', [$this, 'getGroups']);
        }

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

    /**
     * Get pages by ID list
     *
     * @param array $iDs
     * @return array
     */
    public function getPages($iDs) {
        $result = Gdn::sql()
            ->select('p.*')
            ->select('u.UserID, u.Name as Username, u.Photo')
            ->from('Page p')
            ->join('User u', 'p.InsertUserID = u.UserID', 'left')
            ->whereIn('p.PageID', $iDs)
            ->get()->resultArray();

        $pageModel = new PageModel();
        $pageModel->UrlRoot = c('Pages.UrlRoot', '/page');
        if (!$pageModel->UrlRoot) {
            $pageModel->UrlRoot = '/page';
        }

        foreach ($result as &$page) {
            $pageModel->calculate($page);
            $page['PrimaryID'] = $page['PageID'];

            // Change the html a little to make it look nice as a summary.
            $html = $page['Html'];
            unset($page['Html']);
            $i = strpos($html, '<!-- End of Template -->');
            if ($i !== false) {
                $html = substr($html, $i);
            }
            $html = preg_replace('`<h1.*?>.*?</h1>`i', '', $html, 1);
            $page['Summary'] = Gdn_Format::text($html);

            $page['Url'] = $pageModel->UrlRoot . '/' . PageModel::urlName($page['Name']);
            $page['Name'] = $page['Username'];
        }

        return $result;
    }

    public function getDocuments($search) {
        $result = [];

        // Loop through the matches to figure out which IDs we have to grab.
        $iDs = [];
        if (!is_array($search) || !isset($search['matches'])) {
            return [];
        }

        foreach ($search['matches'] as $documentID => $info) {
            $iD = (int) ($documentID / 10);
            $type = $documentID % 10;

            $iDs[$type][] = $iD;
        }

        // Grab all of the documents.
        $documents = [];
        foreach ($iDs as $type => $iDin) {
            if (!isset($this->typeInfo[$type])) {
                continue;
            }
            $docs = call_user_func($this->typeInfo[$type]['GetCallback'], $iDin);

            // Index the document according to the type again.
            foreach ($docs as $row) {
                $iD = $row['PrimaryID'] * 10 + $type;
                $documents[$iD] = $row;
            }
        }

        // Join them with the search results.
        $result = [];
        foreach ($search['matches'] as $documentID => $info) {
            $row = val($documentID, $documents);
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
        $sphinx = $this->sphinxClient();
        $sphinx->setLimits($offset, $limit, self::$maxResults);
        $sphinx->setMatchMode(SPH_MATCH_EXTENDED2); // Default match mode.

        // Filter the search into proper terms.
        if ($clean) {
            $search = Search::cleanSearch($search, $clean === 'api');
        }

        $doSearch = $search['dosearch'];
        $filtered = false;
        $indexes = $this->indexes();
        $query = '';
        $terms = [];

        // Skip search if no parameters were set
//      if (!isset($Search['search']) && !isset($Search['timestamp-from']) && !isset($Search['title'])
//         && !isset($Search['users']) && !isset($Search['discussionid'])) {
//         $DoSearch = false;
//      }

        if (isset($search['search'])) {
            list($query, $terms) = $this->splitTags($search['search']);
        }
        $this->setSort($sphinx, $terms, $search);

        // Set the filters based on the search.
        if (isset($search['cat'])) {
            $sphinx->setFilter('CategoryID', (array) $search['cat']);
            $filtered &= (count($search['cat']) > 0);
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
            $indexes = $this->indexes('Discussion');
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
            $values = [];

            foreach ($search['types'] as $table => $types) {
                $indexes[] = ucfirst($table);

                foreach ($types as $t) {
                    $v = val($t, self::$typeMap);
                    if ($v !== false) {
                        $values[] = $v;
                    }
                }
            }

            trace($values, "dtype");
            $sphinx->setFilter('dtype', $values);
            $indexes = $this->indexes($indexes);
        }

        if (isset($search['discussionid'])) {
            $sphinx->setFilter('DiscussionID', (array) $search['discussionid']);
        }

        if ($search['group']) {
            $sphinx->setGroupBy('DiscussionID', SPH_GROUPBY_ATTR, 'sort DESC');
        }

        $results['Search'] = $search;

        if ($doSearch) {
            if ($filtered && empty($query)) {
                $sphinx->setMatchMode(SPH_MATCH_ALL);
            }
            $results = $this->doSearch($sphinx, $query, $indexes);
            $results['SearchTerms'] = array_unique($terms);

//         if ($Search['group']) {
//            // This was a grouped search so join the sub-documents.
//            $Subsearch = $Search;
//            $Subsearch['group'] = false;
//            $Subsearch['discussionid'] = array_column($Results['SearchResults'], 'DiscussionID');
//            unset($Subsearch['cat']);
//            $Sphinx->resetFilters();
//            $Sphinx->resetGroupBy();
//            $ChildResults = $this->advancedSearch($Subsearch, 0, 50, false);
//            $Results['ChildResults'] = $ChildResults['SearchResults'];
//         }
        } else {
            $results = ['SearchResults' => [], 'RecordCount' => 0, 'SearchTerms' => $terms];
        }
        $results['CalculatedSearch'] = $search;
        return $results;
    }

    public function autoComplete($search, $limit = 10) {
        $sphinx = $this->sphinxClient();
        $sphinx->setLimits(0, $limit, 100);
        $indexes = $this->indexes('Discussion');

        $search = Search::cleanSearch($search);

        $str = $search['search'];
        list ($query, $terms) = $this->splitTags($str);

        if (isset($search['cat'])) {
            $sphinx->setFilter('CategoryID', (array) $search['cat']);
        }
        if (isset($search['discussionid'])) {
            $indexes = $this->indexes(['Discussion', 'Comment']);
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
        $indexes = $this->indexes('Group');

        $search = Search::cleanSearch($search);

        $str = $search['search'];
        list ($query, $terms) = $this->splitTags($str);

        $this->setSort($sphinx, $terms, $search);
        $results = $this->doSearch($sphinx, $query, $indexes);
        $results['SearchTerms'] = $terms;

        return $results;
    }

    protected function doSearch($sphinx, $query, $indexes) {
        $this->EventArguments['SphinxClient'] = $sphinx;
        $this->fireEvent('BeforeSphinxSearch');
        trace($query, 'Query');
        trace($indexes, 'indexes');

        $search = $sphinx->query($query, implode(' ', $indexes));
        if (!$search) {
            trace($sphinx->getLastError(), TRACE_ERROR);
            trace($sphinx->getLastWarning(), TRACE_WARNING);

            if (isset($sphinx->error)) {
                logMessage(__FILE__, __LINE__, 'SphinxPlugin::SphinxSearchModel', 'Search', 'Error: ' . $sphinx->error);
            } elseif (getValue('warning', $sphinx)) {
                logMessage(__FILE__, __LINE__, 'SphinxPlugin::SphinxSearchModel', 'Search', 'Warning: ' . $sphinx->warning);
            } else {
                trace($sphinx);
                trace('Sphinx returned an error', TRACE_ERROR);
            }
//      } else {
//         trace($Search, 'search');
        }

        $results = $this->getDocuments($search);
        $total = val('total', $search);
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
        trace($search, 'sphinx');

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

    public function indexes($type = null) {
        $indexes = $this->types;

        if (!empty($type)) {
            $indexes = array_intersect($this->types, (array) $type);
        }


        $prefix = str_replace(['-'], '_', c('Database.Name')) . '_';
        foreach ($indexes as &$name) {
            $name = $prefix . $name;
        }
        unset($name);

        if ($this->useDeltas) {
            foreach ($indexes as $name) {
                $indexes[] = $name . '_Delta';
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
        if ($this->_sphinxClient === null) {
            $sphinxHost = c('Plugins.Sphinx.Server', c('Database.Host', 'localhost'));
            $sphinxPort = c('Plugins.Sphinx.Port', 9312);

            $this->_sphinxClient = new SphinxClient();
            $this->_sphinxClient->setServer($sphinxHost, $sphinxPort);

            // Set some defaults.
            $this->_sphinxClient->setMatchMode(SPH_MATCH_EXTENDED2);
            $this->_sphinxClient->setSortMode(SPH_SORT_TIME_SEGMENTS, 'DateInserted');
//            $Sphinx->setRankingMode(SPH_RANK_SPH04);
//            $Sphinx->setRankingMode(SPH_RANK_PROXIMITY_BM25);
//            $Sphinx->setRankingMode(SPH_RANK_BM25);
            $this->_sphinxClient->setRankingMode(self::$rankingMode);
            $this->_sphinxClient->setMaxQueryTime(5000);
            $this->_sphinxClient->setFieldWeights(['name' => 3, 'body' => 1]);
        }
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

//      $search = Search::cleanSearch($search);
//      trace($search, 'calc search');

        /*
          $Indexes = $this->Types;
          $Prefix = c('Database.Name').'_';
          foreach ($Indexes as &$Name) {
          $Name = $Prefix.$Name;
          }
          unset($Name);

          if ($this->UseDeltas) {
          foreach ($Indexes as $Name) {
          $Indexes[] = $Name.'_Delta';
          }
          }

          $SphinxHost = c('Plugins.Sphinx.Server', c('Database.Host', 'localhost'));
          $SphinxPort = c('Plugins.Sphinx.Port', 9312);

          // Get the raw results from sphinx.
          $Sphinx = new sphinxClient();
          $Sphinx->setServer($SphinxHost, $SphinxPort);
          $Sphinx->setMatchMode(SPH_MATCH_EXTENDED2);
          //      $Sphinx->setSortMode(SPH_SORT_TIME_SEGMENTS, 'DateInserted');
          $Sphinx->setSortMode(SPH_SORT_ATTR_DESC, 'DateInserted');
          $Sphinx->setLimits($Offset, $Limit, self::$MaxResults);
          $Sphinx->setMaxQueryTime(5000);

          // Allow the client to be overridden.
          $this->EventArguments['SphinxClient'] = $Sphinx;
          $this->fireEvent('BeforeSphinxSearch');

          $Cats = DiscussionModel::categoryPermissions();
          if ($CategoryID = Gdn::controller()->Request->get('CategoryID')) {
          $Cats2 = CategoryModel::getSubtree($CategoryID);
          Gdn::controller()->setData('Categories', $Cats2);
          $Cats2 = array_column($Cats2, 'CategoryID');
          if (is_array($Cats))
          $Cats = array_intersect($Cats, $Cats2);
          elseif ($Cats)
          $Cats = $Cats2;
          }
          //      $Cats = CategoryModel::categoryWatch();
          //      var_dump($Cats);
          if ($Cats !== true)
          $Sphinx->setFilter('CategoryID', (array)$Cats);
          $terms = $Sphinx->query($terms, implode(' ', $Indexes));
          if (!$terms) {
          trace($Sphinx->getLastError(), TRACE_ERROR);
          trace($Sphinx->getLastWarning(), TRACE_WARNING);
          $Warning = $Sphinx->getLastWarning();
          if (isset($Sphinx->error)) {
          logMessage(__FILE__, __LINE__, 'SphinxPlugin::SphinxSearchModel', 'Search', 'Error: '.$Sphinx->error);
          } elseif (getValue('warning', $Sphinx)) {
          logMessage(__FILE__, __LINE__, 'SphinxPlugin::SphinxSearchModel', 'Search', 'Warning: '.$Sphinx->warning);
          } else {
          trace($Sphinx);
          trace('Sphinx returned an error', TRACE_ERROR);
          }
          }

          $Result = $this->getDocuments($terms);

          $Total = getValue('total', $terms);
          Gdn::controller()->setData('RecordCount', $Total);

          if (!is_array($Result))
          $Result = [];

          foreach ($Result as $Key => $Value) {
          if (isset($Value['Summary'])) {
          $Value['Summary'] = condense(Gdn_Format::to($Value['Summary'], getValue('Format', $Value, 'Html')));
          $Result[$Key] = $Value;
          }
          }

          return $Result;
         */
    }

    public function setSort($sphinx, $terms, $search) {
        // If there is just one search term then we really want to just sort by date.
        if (val('sort', $search) === 'date' || (count($terms) < 2 && val('sort', $search) !== 'relevance')) {
            $sphinx->setSelect('*, (dateinserted + 1) as sort');
            $sphinx->setSortMode(SPH_SORT_ATTR_DESC, 'sort');
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
                $sort = "(($fullfunc) * $mult + 1) * @weight";
                trace($sort, 'sort');

                $sphinx->setSelect("*, $sort as sort");

                $sphinx->setSortMode(SPH_SORT_ATTR_DESC, 'sort');
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
