<?php

if (!defined('APPLICATION'))
   exit();

/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */
class GroupsController extends Gdn_Controller {

   public $Uses = array('GroupModel');

   /**
    * @var GroupModel
    */
   public $GroupModel;

   /**
    * Include JS, CSS, and modules used by all methods.
    *
    * Always called by dispatcher before controller's requested method.
    * 
    * @access public
    */
   public function Initialize() {
      // Set up head
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery-ui-1.8.17.custom.min.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      $this->AddCssFile('style.css');

      $this->AddBreadcrumb(T('Groups'), Url('/groups'));

      parent::Initialize();
   }

   public function Index() {
      Gdn_Theme::Section('GroupList');

      // Get popular groups.
      $Groups = $this->GroupModel->Get('CountMembers', 'desc', 9)->ResultArray();
      $this->SetData('Groups', $Groups);

      // Get new groups.
      $NewGroups = $this->GroupModel->Get('DateInserted', 'desc', 9)->ResultArray();
      $this->SetData('NewGroups', $NewGroups);

      // Get my groups.
      if (Gdn::Session()->IsValid()) {
         $MyGroups = $this->GroupModel->GetByUser(Gdn::Session()->UserID);
         $this->SetData('MyGroups', $MyGroups);
      }

      $this->Title(T('Groups'));

      require_once $this->FetchViewLocation('group_functions', 'Group');
      $this->CssClass .= ' NoPanel';
      $this->Render('Groups');
   }
   
   public function Browse($Sort = 'newest', $Page = '') {
      Gdn_Theme::Section('GroupList');
      $Sort = strtolower($Sort);
      
      $Sorts = array(
         'newest' => array('Title' => T('Newest Groups'), 'OrderBy' => 'DateInserted'),
         'popular' => array('Title' => T('Popular Groups'), 'OrderBy' => 'CountMembers'),
         'updated' => array('Title' => T('Recently Updated Groups'), 'OrderBy' => 'DateLastComment'));
      
      if (!array_key_exists($Sort, $Sorts)) {
         $Sort = array_pop(array_keys($Sorts));
      }
      
      $SortRow = $Sorts[$Sort];
      $PageSize = 24; // good size for 4, 3, 2 columns.
      list($Offset, $Limit) = OffsetLimit($Page, $PageSize);
      $PageNumber = PageNumber($Offset, $Limit);
      
      $Groups = $this->GroupModel->Get($SortRow['OrderBy'], 'desc', $Limit, $PageNumber)->ResultArray();
      $this->SetData('Groups', $Groups);
      
      // Set the pager data.
      $this->SetData('_Limit', $Limit);
      $this->SetData('_CurrentRecords', count($Groups));
      $TotalRecords = $this->GroupModel->GetCount();
      
      $Pager = PagerModule::Current();
      $Pager->Configure($Offset, $Limit, $TotalRecords, "groups/browse/$Sort/{Page}");
      
      $this->Title($SortRow['Title']);
      $this->AddBreadcrumb($this->Title(), Url("/groups/browse/$Sort"));
      
      require_once $this->FetchViewLocation('group_functions', 'Group');
      $this->Render();
   }
}
