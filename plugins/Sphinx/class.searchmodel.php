<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

class SearchModel extends Gdn_Model {
	/// PROPERTIES ///

   public $Types = array();
   public $TypeInfo = array();
   public $DataPath = '/var/data/searchd';
   public $LogPath = '/var/log/searchd';
   public $RunPath = '/var/run/searchd';
   public $UseDeltas;

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
      
      parent::__construct();
   }

   public function AddTypeInfo($Type, $GetCallback, $IndexCallback = NULL) {
      if (!is_numeric($Type)) {
         $Type = array_search($Type, $this->Types);
      }

      $this->TypeInfo[$Type] = array('GetCallback' => $GetCallback, 'IndexCallback' => $IndexCallback);
   }

   public function GetComments($IDs) {
      $Result = Gdn::SQL()
			->Select('c.CommentID as PrimaryID, c.CommentID, d.Name as Title, c.Body as Summary, c.Format, d.CategoryID')
			->Select('c.DateInserted, c.Score')
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
			->Select('d.DateInserted, d.Score')
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
         $Result[] = $Row;
      }
      return $Result;
   }
   
   public function AdvancedSearch($Search, $Offset = 0, $Limit = 10) {
      $Sphinx = $this->SphinxClient();
      $Sphinx->setMatchMode(SPH_MATCH_EXTENDED); // Default match mode.
      
      // Make the search terms case-insensitive.
      $Search = AdvancedSearchPlugin::MassageSearch($Search);
      $DoSearch = $Search['dosearch'];
      $Filtered = FALSE;
      $Indexes = $this->Indexes();
      $Query = '';
      $Terms = array();

      if (isset($Search['search'])) {
         list($Query, $Terms) = $this->splitTags($Search['search']);
      }
      
      // Set the filters based on the search.
      if (isset($Search['categoryid'])) {
         $Sphinx->setFilter('CategoryID', (array)$Search['categoryid']);
         $Filtered &= count($Search['categoryid']) == 1;
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
      
      if ($DoSearch) {
         if ($Filtered && empty($Query))
            $Sphinx->setMatchMode(SPH_MATCH_ALL);
         $Results = $this->DoSearch($Sphinx, $Query, $Indexes);
         $Results['SearchTerms'] = array_unique($Terms);
      } else {
         $Results = array('SearchResults' => array(), 'RecordCount' => 0, 'SearchTerms' => $Terms);
      }
      $Results['CalculatedSearch'] = $Search;
      return $Results;
   }
   
   public function autoComplete($str) {
      $sphinx = $this->SphinxClient();
      $indexes = $this->Indexes('Discussion');
      list ($query, $terms) = $this->splitTags($str);
      
      $categories = array_keys(CategoryModel::GetByPermission('Discussions.View', NULL, array('Archived' => 0)));
      if ($categories !== true)
         $sphinx->setFilter('CategoryID', (array)$categories);
      
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

      if ($Type !== NULL) {
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
         $Sphinx->setSortMode(SPH_SORT_ATTR_DESC, 'DateInserted');
         $Sphinx->setMaxQueryTime(5000);
      }
      return $Sphinx;
   }
   
	public function Search($Search, $Offset = 0, $Limit = 20) {
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
      $Sphinx->setLimits($Offset, $Limit, 1000);
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
      $Search = $Sphinx->query($Search, implode(' ', $Indexes));
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
      }
      
      $Result = $this->GetDocuments($Search);
      
      $Total = GetValue('total', $Search);
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
   
   public function splitTags($search) {
      $sphinx = $this->SphinxClient();
      $search = preg_replace('`\s`', ' ', $search);
      $tokens = preg_split('`([\s"+-])`', $search, -1, PREG_SPLIT_DELIM_CAPTURE);
      $tokens = array_filter($tokens);
      $inquote = false;

      $queries = array();
      $terms = array();
      $query = array('', '', '');

      foreach ($tokens as $c) {
         // Figure out where to push the token.
         switch ($c) {
            case '+':
            case '-':
               if ($inquote) {
                  $query[1] .= $c;
               } elseif(!$query[0]) {
                  $query[0] .= $c;
               } else {
                  $query[1] .= $c;
               }
               break;
            case '"':
               if ($inquote) {
                  $query[2] = $c;
                  $inquote = false;
               } else {
                  $query[0] .= $c;
                  $inquote = true;
               }

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
      foreach ($queries as $i => &$query) {
         $c = substr($query, 0, 1);
         if ($c == '+') {
            $query = substr($query, 1);
            $firstmod = $i == 0;
         } elseif ($c == '-') {
            $firstmod = $i == 0;
         } elseif (!$firstmod && $i > 0) {
            $query = '| '.$query;
         }
      }

      return array(implode(' ', $queries), array_unique($terms));
   }
   
   public function WriteConf($String, $Newline = TRUE) {
      fwrite($this->_fp, $String.($Newline ? "\n" : ''));
   }

   public function WriteConfValue($Name, $Value) {
      $this->WriteConf("  $Name = ".str_replace("\n", "\\\n", $Value));
   }

   public function WriteConfSourceBegin($Name) {
      $this->WriteConf("source $Name {");
      $this->WriteConfValue('type', 'mysql');
      $this->WriteConfValue('sql_host', C('Database.Host'));
      $this->WriteConfValue('sql_user', C('Database.User'));
      $this->WriteConfValue('sql_pass', C('Database.Password'));
      $this->WriteConfValue('sql_db', C('Database.Name'));
   }

   public function WriteConfSourceEnd() {
      $this->WriteConf("}\n");
   }
}