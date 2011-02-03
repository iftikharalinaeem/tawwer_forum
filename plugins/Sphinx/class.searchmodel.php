<?php if (!defined('APPLICATION')) exit();

class SearchModel extends Gdn_Model {
	/// PROPERTIES ///

   public $Types = array(1 => 'Discussion', 2 => 'Comment', 3 => 'Page');
   public $TypeInfo = array();

	/// METHODS ///

   public function __construct() {
      $this->AddTypeInfo('Discussion', array($this, 'GetDiscussions'));
      $this->AddTypeInfo('Comment', array($this, 'GetComments'));
      $this->AddTypeInfo('Page', array($this, 'GetPages'));
   }

   public function AddTypeInfo($Type, $GetCallback, $IndexCallback = NULL) {
      if (!is_numeric($Type)) {
         $Type = array_search($Type, $this->Types);
      }

      $this->TypeInfo[$Type] = array('GetCallback' => $GetCallback, 'IndexCallback' => '');
   }

   public function GetComments($IDs) {
      $Result = Gdn::SQL()
			->Select('c.CommentID as PrimaryID, d.Name as Title, c.Body as Summary')
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
			->Select('d.DiscussionID as PrimaryID, d.Name as Title, d.Body as Summary')
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

	public function Search($Search, $Offset = 0, $Limit = 20) {
      // Get the raw results from sphinx.
      $Sphinx = new SphinxClient();
      $Sphinx->setServer(C('Plugin.Sphinx.Server', C('Database.Host', 'localhost')), C('Plugin.Sphinx.Port', 9312));
      $Sphinx->setMatchMode(SPH_MATCH_ANY);
      $Sphinx->setSortMode(SPH_SORT_TIME_SEGMENTS);
      $Sphinx->setLimits($Offset, $Limit);
      $Sphinx->setIndexWeights(array('Page' => 200, 'Discussion' => 150, 'Comment' => 100));
      $Sphinx->setMaxQueryTime(10);

      // Allow the client to be overridden.
      $this->EventArguments['SphinxClient'] = $Sphinx;
      $this->FireEvent('BeforeSphinxSearch');

      $Search = $Sphinx->query($Search, 'Discussion Comment Page');
      $Result = $this->GetDocuments($Search);

//		$Result = array(array(
//          'Relavence' => 1,
//          'PrimaryID' => 1,
//          'Title' => 'Test sphinx',
//          'Summary' => 'Returned from sphinx search.',
//          'Url' => '/',
//          'DateInserted' => Gdn_Format::ToDateTime(),
//          'UserID' => Gdn::Session()->UserID,
//          'Name' => Gdn::Session()->User->Name,
//          'Photo' => '',
//          'Foo' => $Search
//      ));

      return $Result;
	}
}