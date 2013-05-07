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

   public function GenerateConfig($fp) {
      $this->_fp = $fp;
      $UseDeltas = C('Plugins.Sphinx.UseDeltas');
      $Px = $this->Database->DatabasePrefix;

      $Types = $this->Types;
      if (!Gdn::Structure()->TableExists('Page')) {
         unset($Types[3]);
      }

      $DeltaTypes = $Types;
      foreach ($DeltaTypes as &$Type) {
         $Type = C('Database.Name').'_'.$Type.'_Delta';
      }

      // Write the basic header.
      fwrite($fp, '# sphinx.conf automatically generated '.Gdn_Format::ToDateTime()."\n\n");

      // Cron help.
      fwrite($fp, "# You'll need to set up a cron job to reindex the database with the following commands.\n");
      fwrite($fp, "# indexer --all --rotate\n");
      if ($UseDeltas) {
         fwrite($fp, "# indexer ".implode(' ', $DeltaTypes)." --rotate\n");
      }
      fwrite($fp, "\n");

      // Write each datasource and index definition.
      foreach ($this->Types as $Index => $Type) {
         $Info = $this->TypeInfo[$Index];
         $Callback = $Info['IndexCallback'];
         $Name = C('Database.Name').'_'.$Type;
         call_user_func($Callback, $this, $Name, $UseDeltas);
      }

      // Write the searchd section.
      $Server = C('Plugins.Sphinx.Server', C('Databse.Host', 'localhost'));
      $Port = C('Plugins.Sphinx.Port', 9312);
      
      $Searchd = "
# This searchd section is default searchd section. You can replace it with your own.
searchd {
  listen = {$Server}:{$Port}
  pid_file = {$this->RunPath}/searchd.pid
}";
      $this->WriteConf($Searchd);

      $this->_fp = NULL;
   }

   public function GetComments($IDs) {
      $Result = Gdn::SQL()
			->Select('c.CommentID as PrimaryID, d.Name as Title, c.Body as Summary, c.Format, d.CategoryID')
			->Select("'/discussion/comment/', c.CommentID, '/#Comment_', c.CommentID", "concat", 'Url')
			->Select('c.DateInserted')
			->Select('c.InsertUserID as UserID, u.Name, u.Photo')
			->From('Comment c')
			->Join('Discussion d', 'd.DiscussionID = c.DiscussionID')
			->Join('User u', 'u.UserID = c.InsertUserID', 'left')
         ->WhereIn('c.CommentID', $IDs)
         ->Get()->ResultArray();

      return $Result;
   }

   public function GetDiscussions($IDs) {
      $Result = Gdn::SQL()
			->Select('d.DiscussionID as PrimaryID, d.Name as Title, d.Body as Summary, d.Format, d.CategoryID')
			->Select('d.DiscussionID', "concat('/discussion/', %s)", 'Url')
			->Select('d.DateInserted')
			->Select('d.InsertUserID as UserID, u.Name, u.Photo')
			->From('Discussion d')
			->Join('User u', 'd.InsertUserID = u.UserID', 'left')
         ->WhereIn('d.DiscussionID', $IDs)
         ->Get()->ResultArray();

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

   /**
    *
    * @param SearchModel $SearchModel
    * @param string $Name
    * @param mixed $Delta
    */
   public function IndexComments($SearchModel, $Name, $Delta) {
      $Px = $SearchModel->Database->DatabasePrefix;
      $ID = 2;
      $SelectSql = "
    select c.CommentID * 10 + $ID, d.CategoryID, c.InsertUserID, unix_timestamp(c.DateInserted) as DateInserted, c.Body
    from {$Px}Comment c join {$Px}Discussion d on c.DiscussionID = d.DiscussionID";

      // Write the general datasource.
      $SearchModel->WriteConfSourceBegin($Name);
      $SearchModel->WriteConfValue('sql_query_pre', 'set names utf8');
      if ($Delta) {
         $SearchModel->WriteConfValue('sql_query_pre', "replace into {$Px}SphinxCounter select $ID, max(CommentID) from {$Px}Comment");
         $SearchModel->WriteConfValue('sql_query',
            "{$SelectSql}\n    where CommentID <= (select MaxID from {$Px}SphinxCounter where CounterID = $ID)");
      } else {
         $SearchModel->WriteConfValue('sql_query', $SelectSql);
      }
      $SearchModel->WriteConfValue('sql_attr_uint', 'CategoryID');
      $SearchModel->WriteConfValue('sql_attr_uint', 'InsertUserID');
      $SearchModel->WriteConfValue('sql_attr_timestamp', 'DateInserted');
      $SearchModel->WriteConfSourceEnd();

      if ($Delta) {
         // Write the delta datasource.
         $SearchModel->WriteConf("source {$Name}_Delta: $Name {");
         $SearchModel->WriteConfValue('sql_query_pre', 'set names utf8');
         $SearchModel->WriteConfValue('sql_query',
            "{$SelectSql}\n    where CommentID > (select MaxID from {$Px}SphinxCounter where CounterID = $ID)");
         $SearchModel->WriteConfSourceEnd();
      }

      // Write the general index.
      $SearchModel->WriteConf("index $Name {");
      $SearchModel->WriteConfValue('source', $Name);
      $SearchModel->WriteConfValue('path', "{$this->DataPath}/$Name");
      $SearchModel->WriteConfValue('morphology', 'stem_en');
      $SearchModel->WriteConfValue('charset_type', 'utf-8');
      $SearchModel->WriteConfSourceEnd();

      // Write the delta index.
      if ($Delta) {
         $SearchModel->WriteConf("index {$Name}_Delta: $Name {");
         $SearchModel->WriteConfValue('source', $Name.'_Delta');
         $SearchModel->WriteConfValue('path', "{$this->DataPath}/{$Name}_Delta");
         $SearchModel->WriteConfSourceEnd();
      }
   }

   /**
    *
    * @param SearchModel $SearchModel
    * @param string $Name
    * @param mixed $Delta
    */
   public function IndexDiscussions($SearchModel, $Name, $Delta) {
      $Px = $SearchModel->Database->DatabasePrefix;
      $ID = 1;
      $SelectSql = "
    select DiscussionID * 10 + $ID, CategoryID, InsertUserID, unix_timestamp(DateInserted) as DateInserted, unix_timestamp(DateLastComment) as DateLastComment, Name, Body
    from {$Px}Discussion";

      // Write the general datasource.
      $SearchModel->WriteConfSourceBegin($Name);
      $SearchModel->WriteConfValue('sql_query_pre', 'set names utf8');
      if ($Delta) {
         $SearchModel->WriteConfValue('sql_query_pre', "replace into {$Px}SphinxCounter select $ID, max(DiscussionID) from {$Px}Discussion");
         $SearchModel->WriteConfValue('sql_query',
            "{$SelectSql}\n    where DiscussionID <= (select MaxID from {$Px}SphinxCounter where CounterID = $ID)");
      } else {
         $SearchModel->WriteConfValue('sql_query', $SelectSql);
      }
      $SearchModel->WriteConfValue('sql_attr_uint', 'CategoryID');
      $SearchModel->WriteConfValue('sql_attr_uint', 'InsertUserID');
      $SearchModel->WriteConfValue('sql_attr_timestamp', 'DateInserted');
      $SearchModel->WriteConfValue('sql_attr_timestamp', 'DateLastComment');
      $SearchModel->WriteConfSourceEnd();

      if ($Delta) {
         // Write the delta datasource.
         $SearchModel->WriteConf("source {$Name}_Delta: $Name {");
         $SearchModel->WriteConfValue('sql_query_pre', 'set names utf8');
         $SearchModel->WriteConfValue('sql_query',
            "{$SelectSql}\n    where DiscussionID > (select MaxID from {$Px}SphinxCounter where CounterID = $ID)");
         $SearchModel->WriteConfSourceEnd();
      }

      // Write the general index.
      $SearchModel->WriteConf("index $Name {");
      $SearchModel->WriteConfValue('source', $Name);
      $SearchModel->WriteConfValue('path', "{$this->DataPath}/$Name");
      $SearchModel->WriteConfValue('morphology', 'stem_en');
      $SearchModel->WriteConfValue('charset_type', 'utf-8');
      $SearchModel->WriteConfSourceEnd();
      
      // Write the delta index.
      if ($Delta) {
         $SearchModel->WriteConf("index {$Name}_Delta: $Name {");
         $SearchModel->WriteConfValue('source', $Name.'_Delta');
         $SearchModel->WriteConfValue('path', "{$this->DataPath}/{$Name}_Delta");
         $SearchModel->WriteConfSourceEnd();
      }
   }

   /**
    *
    * @param SearchModel $SearchModel
    * @param string $Name
    * @param mixed $Delta
    */
   public function IndexPages($SearchModel, $Name, $Delta) {
      $Px = $SearchModel->Database->DatabasePrefix;
      $ID = 3;
      $SelectSql = "
    select PageID * 10 + $ID, 0 as CategoryID, InsertUserID, unix_timestamp(DateInserted) as DateInserted, Content
    from {$Px}Page
    where Current = 1";

      // Write the general datasource.
      $SearchModel->WriteConfSourceBegin($Name);
      $SearchModel->WriteConfValue('sql_query_pre', 'set names utf8');
      if ($Delta) {
         $SearchModel->WriteConfValue('sql_query_pre', "replace into {$Px}SphinxCounter select $ID, max(PageID) from {$Px}Page where Current = 1");
         $SearchModel->WriteConfValue('sql_query',
            "{$SelectSql}\n      and PageID <= (select MaxID from {$Px}SphinxCounter where CounterID = $ID)");
      } else {
         $SearchModel->WriteConfValue('sql_query', $SelectSql);
      }
      $SearchModel->WriteConfValue('sql_attr_uint', 'CategoryID');
      $SearchModel->WriteConfValue('sql_attr_uint', 'InsertUserID');
      $SearchModel->WriteConfValue('sql_attr_timestamp', 'DateInserted');
      $SearchModel->WriteConfSourceEnd();

      if ($Delta) {
         // Write the delta datasource.
         $SearchModel->WriteConf("source {$Name}_Delta: $Name {");
         $SearchModel->WriteConfValue('sql_query_pre', 'set names utf8');
         $SearchModel->WriteConfValue('sql_query',
            "{$SelectSql}\n      and PageID > (select MaxID from {$Px}SphinxCounter where CounterID = $ID)");
         $SearchModel->WriteConfSourceEnd();
      }

      // Write the general index.
      $SearchModel->WriteConf("index $Name {");
      $SearchModel->WriteConfValue('source', $Name);
      $SearchModel->WriteConfValue('path', "{$this->DataPath}/$Name");
      $SearchModel->WriteConfValue('morphology', 'stem_en');
      $SearchModel->WriteConfValue('charset_type', 'utf-8');
      $SearchModel->WriteConfSourceEnd();

      // Write the delta index.
      if ($Delta) {
         $SearchModel->WriteConf("index {$Name}_Delta: $Name {");
         $SearchModel->WriteConfValue('source', $Name.'_Delta');
         $SearchModel->WriteConfValue('path', "{$this->DataPath}/{$Name}_Delta");
         $SearchModel->WriteConfSourceEnd();
      }
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