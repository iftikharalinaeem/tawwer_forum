<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

if (!defined('SPH_RANK_SPH04'))
   define('SPH_RANK_SPH04', 7);

// Bit of a kludge, but we need these functions even if advanced search is disabled
require_once PATH_PLUGINS.'/AdvancedSearch/class.search.php';

class SearchModel extends Gdn_Model {
	/// PROPERTIES ///

   public $Types = array();
   public $TypeInfo = array();
   public $DataPath = '/var/data/searchd';
   public $LogPath = '/var/log/searchd';
   public $RunPath = '/var/run/searchd';
   public $UseDeltas;
   
   public static $MaxResults = 1000;
   
   public static $Ranker = array();
   
   public static $RankingMode = SPH_RANK_SPH04; //SPH_RANK_PROXIMITY_BM25;
   
   public static $TypeMap = array(
      'd' => 0,
      'c' => 100,
      'question' => 1,
      'poll' => 2,
      'answer' => 101
      );

   protected $_fp = NULL;

	/// METHODS ///

   public function __construct() {
      $this->UseDeltas = C('Plugins.Sphinx.UseDeltas');
      
      if (array_key_exists("Vanilla", Gdn::ApplicationManager()->EnabledApplications())) {
         $this->Types[1] = 'Discussion';
         $this->AddTypeInfo('Discussion', array($this, 'GetDiscussions'), array($this, 'IndexDiscussions'));
         
         $this->Types[2] = 'Comment';
         $this->AddTypeInfo('Comment', array($this, 'GetComments'), array($this, 'IndexComments'));
      }
      
      if (array_key_exists("Pages", Gdn::ApplicationManager()->EnabledApplications())) {
         $this->Types[3] = 'Page';
         $this->AddTypeInfo('Page', array($this, 'GetPages'), array($this, 'IndexPages'));
      }
      
      self::$Ranker['score'] = array(
         'items' => array(-5, 0, 1, 3, 5, 10, 19), 
         'add' => -1, 
         'weight' => 1);
      
      self::$Ranker['dateinserted'] = array(
         'items' => array(
            strtotime('-2 years'),
            strtotime('-1 year'),
            strtotime('-6 months'),
            strtotime('-3 months'),
            strtotime('-1 month'),
            strtotime('-1 week'),
            strtotime('-1 day')),
         'add' => -4,
         'weight' => 1
            );
      
      parent::__construct();
   }
   
   public static function interval($val, $items) {
      if ($val < $items[0])
         return 0;
      
      foreach ($items as $i => $val2) {
         if ($val < $val2) {
            return $i;
         }
      }
      return $i + 1;
   }
   
   public function AddTypeInfo($Type, $GetCallback, $IndexCallback = NULL) {
      if (!is_numeric($Type)) {
         $Type = array_search($Type, $this->Types);
      }

      $this->TypeInfo[$Type] = array('GetCallback' => $GetCallback, 'IndexCallback' => $IndexCallback);
   }

   public function GetComments($IDs) {
      $Result = Gdn::SQL()
			->Select('c.CommentID as PrimaryID, c.CommentID, d.DiscussionID, d.Name as Title, c.Body as Summary, c.Format, d.CategoryID')
			->Select('c.DateInserted, c.Score, d.Type')
			->Select('c.InsertUserID as UserID')
			->From('Comment c')
			->Join('Discussion d', 'd.DiscussionID = c.DiscussionID')
         ->WhereIn('c.CommentID', $IDs)
         ->Get()->ResultArray();
      
      foreach ($Result as &$Row) {
         $Row['Url'] = CommentUrl($Row, '/');
      }

      return $Result;
   }

   public function GetDiscussions($IDs) {
      $Result = Gdn::SQL()
			->Select('d.DiscussionID as PrimaryID, d.DiscussionID, d.Name as Title, d.Body as Summary, d.Format, d.CategoryID')
			->Select('d.DateInserted, d.Score, d.Type')
			->Select('d.InsertUserID as UserID')
			->From('Discussion d')
         ->WhereIn('d.DiscussionID', $IDs)
         ->Get()->ResultArray();
      
      foreach ($Result as &$Row) {
         $Row['Name'] = $Row['Title'];
         $Row['Url'] = DiscussionUrl($Row, '', '/');
         unset($Row['Name']);
      }

      return $Result;
   }

   public function GetPages($IDs) {
      $Result = Gdn::SQL()
			->Select('p.*')
			->Select('u.UserID, u.Name as Username, u.Photo')
			->From('Page p')
			->Join('User u', 'p.InsertUserID = u.UserID', 'left')
         ->WhereIn('p.PageID', $IDs)
         ->Get()->ResultArray();

      $PageModel = new PageModel();
      $PageModel->UrlRoot = C('Pages.UrlRoot', '/page');
      if (!$PageModel->UrlRoot)
         $PageModel->UrlRoot = '/page';
      foreach ($Result as &$Page) {
         $PageModel->Calculate($Page);
         $Page['PrimaryID'] = $Page['PageID'];
         
         // Change the html a little to make it look nice as a summary.
         $Html = $Page['Html'];
         unset($Page['Html']);
         $i = strpos($Html, '<!-- End of Template -->');
         if ($i !== FALSE)
            $Html = substr($Html, $i);
         $Html = preg_replace('`<h1.*?>.*?</h1>`i', '', $Html, 1);
         $Page['Summary'] = Gdn_Format::Text($Html);

         $Page['Url'] = $PageModel->UrlRoot.'/'.PageModel::UrlName($Page['Name']);
         $Page['Name'] = $Page['Username'];
      }

      return $Result;
   }

   public function GetDocuments($Search) {
      $Result = array();

      // Loop through the matches to figure out which IDs we have to grab.
      $IDs = array();
      if (!is_array($Search) || !isset($Search['matches']))
         return array();
         
      foreach ($Search['matches'] as $DocumentID => $Info) {
         $ID = (int)($DocumentID / 10);
         $Type = $DocumentID % 10;

         $IDs[$Type][] = $ID;
      }

      // Grab all of the documents.
      $Documents = array();
      foreach ($IDs as $Type => $IDIn) {
         if (!isset($this->TypeInfo[$Type]))
            continue;
         $Docs = call_user_func($this->TypeInfo[$Type]['GetCallback'], $IDIn);
         // Index the document according to the type again.
         foreach ($Docs as $Row) {
            $ID = $Row['PrimaryID'] * 10 + $Type;
            $Documents[$ID] = $Row;
         }
      }

      // Join them with the search results.
      $Result = array();
      foreach ($Search['matches'] as $DocumentID => $Info) {
         $Row = GetValue($DocumentID, $Documents);
         if ($Row === FALSE)
            continue;

         $Row['Relevance'] = $Info['weight'];
         $Row['Score'] = $Info['attrs']['score'];
         $Row['Count'] = GetValue('@count', $Info['attrs'], 1);
         $Row['sort'] = GetValue('sort', $Info['attrs']);
         
         if (!isset($Row['DiscussionID']))
            $Row['DiscussionID'] = GetValue('discussionid', $Info['attrs']);
         
         $Result[] = $Row;
      }
      return $Result;
   }
   
   public function AdvancedSearch($Search, $Offset = 0, $Limit = 10, $clean = true) {
      $Sphinx = $this->SphinxClient();
      $Sphinx->setLimits($Offset, $Limit, self::$MaxResults);
      $Sphinx->setMatchMode(SPH_MATCH_EXTENDED); // Default match mode.
      
      // Filter the search into proper terms.
      if ($clean)
         $Search = Search::cleanSearch($Search);
      $DoSearch = $Search['dosearch'];
      $Filtered = FALSE;
      $Indexes = $this->Indexes();
      $Query = '';
      $Terms = array();

      if (isset($Search['search'])) {
         list($Query, $Terms) = $this->splitTags($Search['search']);
      }
      $this->setSort($Sphinx, $Terms, $Search);
      
      // Set the filters based on the search.
      if (isset($Search['cat'])) {
         $Sphinx->setFilter('CategoryID', (array)$Search['cat']);
         $Filtered &= (count($Search['cat']) > 0);
      }
      
      if (isset($Search['timestamp-from'])) {
         $Sphinx->setFilterRange('DateInserted', $Search['timestamp-from'], $Search['timestamp-to']);
         $Filtered = TRUE;
      }
      
      if (isset($Search['title'])) {
         $Indexes = $this->Indexes('Discussion');
         list($TitleQuery, $TitleTerms) = $this->splitTags($Search['title']);
         $Query .= ' @name '.$TitleQuery;
         $Terms = array_merge($Terms, $TitleTerms);
      }
      
      if (isset($Search['users'])) {
         $Sphinx->setFilter('InsertUserID', ConsolidateArrayValuesByKey($Search['users'], 'UserID'));
         $Filtered = TRUE;
      }
      
      if (isset($Search['tags'])) {
         if (GetValue('tags-op', $Search) == 'and') {
            foreach ($Search['tags'] as $Row) {
               $Sphinx->setFilter('Tags', (array)$Row['TagID']);
            }
         } else {
            $Sphinx->setFilter('Tags', ConsolidateArrayValuesByKey($Search['tags'], 'TagID'));
         }
           
         $Filtered = TRUE;
      }
      
      if (isset($Search['types'])) {
         $Indexes = array();
         $values = array();
         
         foreach ($Search['types'] as $table => $types) {
            $Indexes[] = ucfirst($table);
            
            foreach ($types as $t) {
               $v = GetValue($t, self::$TypeMap);
               if ($v !== false)
                  $values[] = $v;
            }
         }
         Trace($values, "dtype");
         $Sphinx->setFilter('dtype', $values);
         $Indexes = $this->Indexes($Indexes);
      }
      
      if (isset($Search['discussionid'])) {
         $Sphinx->setFilter('DiscussionID', (array)$Search['discussionid']);
      }
      
      if ($Search['group'])
         $Sphinx->setGroupBy('DiscussionID', SPH_GROUPBY_ATTR, 'sort DESC');

      $Results['Search'] = $Search;
      
      if ($DoSearch) {
         if ($Filtered && empty($Query))
            $Sphinx->setMatchMode(SPH_MATCH_ALL);
         $Results = $this->DoSearch($Sphinx, $Query, $Indexes);
         $Results['SearchTerms'] = array_unique($Terms);
         
//         if ($Search['group']) {
//            // This was a grouped search so join the sub-documents.
//            $Subsearch = $Search;
//            $Subsearch['group'] = false;
//            $Subsearch['discussionid'] = ConsolidateArrayValuesByKey($Results['SearchResults'], 'DiscussionID');
//            unset($Subsearch['cat']);
//            $Sphinx->resetFilters();
//            $Sphinx->resetGroupBy();
//            $ChildResults = $this->AdvancedSearch($Subsearch, 0, 50, false);
//            $Results['ChildResults'] = $ChildResults['SearchResults'];
//         }
      } else {
         $Results = array('SearchResults' => array(), 'RecordCount' => 0, 'SearchTerms' => $Terms);
      }
      $Results['CalculatedSearch'] = $Search;
      return $Results;
   }
   
   public function autoComplete($search, $limit = 10) {
      $sphinx = $this->SphinxClient();
      $sphinx->setLimits(0, $limit, 100);
      $indexes = $this->Indexes('Discussion');
      
      $search = Search::cleanSearch($search);
      
      $str = $search['search'];
      list ($query, $terms) = $this->splitTags($str);
      
      if (isset($search['cat']))
         $sphinx->setFilter('CategoryID', (array)$search['cat']);
      if (isset($search['discussionid'])) {
         $indexes = $this->Indexes(array('Discussion', 'Comment'));
         $sphinx->setFilter('DiscussionID', (array)$search['discussionid']);
      }
      
      $this->setSort($sphinx, $terms, $search);
      $results = $this->DoSearch($sphinx, $query, $indexes);
      $results['SearchTerms'] = $terms;
      
      return $results;
   }
   
   protected function DoSearch($Sphinx, $Query, $Indexes) {
      $this->EventArguments['SphinxClient'] = $Sphinx;
      $this->FireEvent('BeforeSphinxSearch');
      Trace($Query, 'Query');
      Trace($Indexes, 'indexes');
      
      $Search = $Sphinx->query($Query, implode(' ', $Indexes));
      if (!$Search) {
         Trace($Sphinx->getLastError(), TRACE_ERROR);
         Trace($Sphinx->getLastWarning(), TRACE_WARNING);
         $Warning = $Sphinx->getLastWarning();
         if (isset($Sphinx->error)) {
            LogMessage(__FILE__, __LINE__, 'SphinxPlugin::SearchModel', 'Search', 'Error: '.$Sphinx->error);
         } elseif (GetValue('warning', $Sphinx)) {
            LogMessage(__FILE__, __LINE__, 'SphinxPlugin::SearchModel', 'Search', 'Warning: '.$Sphinx->warning);
         } else {
            Trace($Sphinx);
            Trace('Sphinx returned an error', TRACE_ERROR);
         }
//      } else {
//         Trace($Search, 'search');
      }
      
      $Results = $this->GetDocuments($Search);
      $Total = $Total = GetValue('total', $Search);
      $SearchTerms = GetValue('words', $Search);
      if (is_array($SearchTerms))
         $SearchTerms = array_keys($SearchTerms);
      else
         $SearchTerms = array();
      
      return array(
         'SearchResults' => $Results,
         'RecordCount' => $Total,
         'SearchTerms' => $SearchTerms);
   }
   
   public function Indexes($Type = NULL) {
      $Indexes = $this->Types;

      if (!empty($Type)) {
         $Indexes = array_intersect($this->Types, (array)$Type);
      }


      $Prefix = C('Database.Name').'_';
      foreach ($Indexes as &$Name) {
         $Name = $Prefix.$Name;
      }
      unset($Name);

      if ($this->UseDeltas) {
         foreach ($Indexes as $Name) {
            $Indexes[] = $Name.'_Delta';
         }
      }
      return $Indexes;
   }
   
   protected $_SphinxClient = null;
   /**
    * @return SphinxClient
    */
   public function SphinxClient() {
      if ($this->_SphinxClient === null) {
         $SphinxHost = C('Plugins.Sphinx.Server', C('Database.Host', 'localhost'));
         $SphinxPort = C('Plugins.Sphinx.Port', 9312);

         $Sphinx = new SphinxClient();
         $Sphinx->setServer($SphinxHost, $SphinxPort);
         
         // Set some defaults.
         $Sphinx->setMatchMode(SPH_MATCH_EXTENDED);
         $Sphinx->setSortMode(SPH_SORT_TIME_SEGMENTS, 'DateInserted');
//         $Sphinx->setRankingMode(SPH_RANK_SPH04);
//         $Sphinx->setRankingMode(SPH_RANK_PROXIMITY_BM25);
//         $Sphinx->setRankingMode(SPH_RANK_BM25);
         $Sphinx->setRankingMode(self::$RankingMode);
         $Sphinx->setMaxQueryTime(5000);
         $Sphinx->setFieldWeights(array('name' => 3, 'body' => 1));
      }
      return $Sphinx;
   }
   
	public function Search($terms, $Offset = 0, $Limit = 20) {
      $search = array('search' => $terms, 'group' => false);
      if ($CategoryID = Gdn::Controller()->Request->Get('CategoryID'))
         $search['cat'] = $CategoryID;
      
      $results = $this->AdvancedSearch($search, $Offset, $Limit);
      return $results['SearchResults'];
      
//      $search = Search::cleanSearch($search);
//      Trace($search, 'calc search');
      
      
      $Indexes = $this->Types;
      $Prefix = C('Database.Name').'_';
      foreach ($Indexes as &$Name) {
         $Name = $Prefix.$Name;
      }
      unset($Name);
      
      if ($this->UseDeltas) {
         foreach ($Indexes as $Name) {
            $Indexes[] = $Name.'_Delta';
         }
      }
      
      $SphinxHost = C('Plugins.Sphinx.Server', C('Database.Host', 'localhost'));
      $SphinxPort = C('Plugins.Sphinx.Port', 9312);

      // Get the raw results from sphinx.
      $Sphinx = new SphinxClient();
      $Sphinx->setServer($SphinxHost, $SphinxPort);
      $Sphinx->setMatchMode(SPH_MATCH_EXTENDED);
//      $Sphinx->setSortMode(SPH_SORT_TIME_SEGMENTS, 'DateInserted');
      $Sphinx->setSortMode(SPH_SORT_ATTR_DESC, 'DateInserted');
      $Sphinx->setLimits($Offset, $Limit, self::$MaxResults);
      $Sphinx->setMaxQueryTime(5000);

      // Allow the client to be overridden.
      $this->EventArguments['SphinxClient'] = $Sphinx;
      $this->FireEvent('BeforeSphinxSearch');

      $Cats = DiscussionModel::CategoryPermissions();
      if ($CategoryID = Gdn::Controller()->Request->Get('CategoryID')) {
         $Cats2 = CategoryModel::GetSubtree($CategoryID);
         Gdn::Controller()->SetData('Categories', $Cats2);
         $Cats2 = ConsolidateArrayValuesByKey($Cats2, 'CategoryID');
         if (is_array($Cats))
            $Cats = array_intersect($Cats, $Cats2);
         elseif ($Cats)
            $Cats = $Cats2;
      }
//      $Cats = CategoryModel::CategoryWatch();
//      var_dump($Cats);
      if ($Cats !== TRUE)
         $Sphinx->setFilter('CategoryID', (array)$Cats);
      $terms = $Sphinx->query($terms, implode(' ', $Indexes));
      if (!$terms) {
         Trace($Sphinx->getLastError(), TRACE_ERROR);
         Trace($Sphinx->getLastWarning(), TRACE_WARNING);
         $Warning = $Sphinx->getLastWarning();
         if (isset($Sphinx->error)) {
            LogMessage(__FILE__, __LINE__, 'SphinxPlugin::SearchModel', 'Search', 'Error: '.$Sphinx->error);
         } elseif (GetValue('warning', $Sphinx)) {
            LogMessage(__FILE__, __LINE__, 'SphinxPlugin::SearchModel', 'Search', 'Warning: '.$Sphinx->warning);
         } else {
            Trace($Sphinx);
            Trace('Sphinx returned an error', TRACE_ERROR);
         }
      }
      
      $Result = $this->GetDocuments($terms);
      
      $Total = GetValue('total', $terms);
      Gdn::Controller()->SetData('RecordCount', $Total);

      if (!is_array($Result))
         $Result = array();
      
      foreach ($Result as $Key => $Value) {
			if (isset($Value['Summary'])) {
            $Value['Summary'] = Condense(Gdn_Format::To($Value['Summary'], GetValue('Format', $Value, 'Html')));
				$Result[$Key] = $Value;
			}
		}
      
      return $Result;
	}
   
   public function setSort($sphinx, $terms, $search) {
      // If there is just one search term then we really want to just sort by date.
      if (count($terms) < 2) {
         $sphinx->setSelect('*, dateinserted as sort');
         $sphinx->setSortMode(SPH_SORT_ATTR_DESC, 'sort');
      } else {
         $funcs = array();
         foreach (self::$Ranker as $field => $row) {
            $items = $row['items'];
            $weight = $row['weight'];
            $add = $row['add'];

            $func = "interval($field, ".implode(', ', $items).")";
            if ($add > 0)
               $func = "($func +$add)";
            elseif ($add < 0)
               $func = "($func $add)";

            if ($weight != 1)
               $func .= " * $weight";

            $funcs[] = "$func";
         }
         $maxScore = self::maxScore();

         if ($maxScore > 0) {
            $mult = 1 / $maxScore;

            $fullfunc = implode(' + ', $funcs);
            $sort = "(($fullfunc) * $mult + 1) * @weight";
            Trace($sort, 'sort');

            $sphinx->setSelect("*, $sort as sort");

            $sphinx->setSortMode(SPH_SORT_ATTR_DESC, 'sort');
         }
      }
   }
   
   public static function addNotes(&$row, $terms) {
      $map = array('score' => array('Score', 'score'), 'dateinserted' => array('DateInserted', 'date'));
      $maxscore = self::maxScore();
      
      if ($maxscore == 0)
         return '';
      
      $notes = array('rank: '.$row['Relevance']);
      $mult = 1 / $maxscore;
      $totalInt = 0;
     
      
      foreach (self::$Ranker as $name => $info) {
         list($field, $label) = $map[$name];
         
         $val = $row[$field];
         if ($name == 'dateinserted')
            $val = strtotime($val);
         
         $int = (self::interval($val, $info['items']) + $info['add']);
         $totalInt += $int;
         $notes[] = sprintf('%s(%s): %+d%%', $label, $val, $int*100 * $mult);
      }
      
      $calcRank = (1 + $mult * $totalInt) * $row['Relevance'];
      $notes[] = sprintf('total: %d', $calcRank);
      $notes[] = 'expr: '.round($row['sort']);
      
      $notes[] = "mult: ".round($mult);
      return implode(' ', $notes);
   }
   
   protected static function maxScore() {
      $maxScore = 0;
      foreach (self::$Ranker as $field => $row) {
         $items = $row['items'];
         $weight = $row['weight'];
         $add = $row['add'];
         $maxScore += $weight * (count($items) + $add);
      }
      
      return $maxScore;
   }
   
   public function splitTags($search) {
      $sphinx = $this->SphinxClient();
      $search = preg_replace('`\s`', ' ', $search);
      $tokens = preg_split('`([\s"+=-])`', $search, -1, PREG_SPLIT_DELIM_CAPTURE);
      $tokens = array_filter($tokens);
      $inquote = false;

      $queries = array();
      $terms = array();
      $query = array('', '', '');
      
      $hasops = false; // whether or not search has operators

      foreach ($tokens as $c) {
         // Figure out where to push the token.
         switch ($c) {
            case '+':
            case '-':
            case '=':
               if ($inquote) {
                  $query[1] .= $c;
               } elseif(!$query[0]) {
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
               } else {
                  $query[0] .= $c;
                  $inquote = true;
               }
               $hasops = true;
               break;
            case ' ':
               if ($inquote) {
                  $query[1] .= $c;
               }
               break;
            default:
               $query[1] .= $c;
               break;
         }

         // Now split the query into terms and move on.
         if ($query[2] || ($query[1] && !$inquote)) {
            $queries[] = $query[0].$sphinx->escapeString($query[1]).$query[2];
            $terms[] = $query[1];
            $query = array('', '', '');
         }
      }
      // Account for someone missing their last quote.
      if ($inquote && $query[1]) {
         $queries[] = $query[0].$sphinx->escapeString($query[1]).'"';
         $terms[] = $query[1];
      }
      
      // Now we need to convert the queries into sphinx syntax.
      $firstmod = false; // whether first term had a modifier.
      $finalqueries = array();
      $quorums = array();
      
      foreach ($queries as $i => $query) {
         $c = substr($query, 0, 1);
         if ($c == '+') {
            $finalqueries[] = substr($query, 1);
            $firstmod = $i == 0;
         } elseif ($c == '-' || $c == '=') {
            $finalqueries[] = $c.substr($query, 1);
            $firstmod = $i == 0;
         } elseif ($c == '"') {
            if (!$firstmod && count($finalqueries) > 0)
               $query = '| '.$query;
            $finalqueries[] = $query;
         } else {
            // Collect this term into a list for the quorum operator.
            $quorums[] = $query;
         }
      }
      // Calculate the quorum.
      if (count($quorums) <= 2)
         $quorum = implode(' ', $quorums);
      else
         $quorum = '"'.implode(' ', $quorums).'"/'.round(count($quorums) * .6); // must have at least 60% of search terms

      $finalquery = implode(' ', $finalqueries).' '.$quorum;
      
      
//      return array($search, array_unique($terms));
      return array($finalquery, array_unique($terms));
      
   }
}