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
    * @var int The page size of groups when browsing.
    */
   public $PageSize = 24;


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
      $this->AddJsFile('jquery-ui.js');
      $this->AddJsFile('jquery.tokeninput.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      $this->AddJsFile('group.js');
      $this->AddCssFile('style.css');

      $this->AddBreadcrumb(T('Groups'), '/groups');

      parent::Initialize();
   }

   public function Index($Limit = 9) {
      Gdn_Theme::Section('GroupList');

      if (!is_numeric($Limit))
          $Limit = 9;
      elseif ($Limit > 30)
          $Limit = 30;
      elseif ($Limit < 0)
          $Limit = 9;

      // Get popular groups.
      $Groups = $this->GroupModel->Get('CountMembers', 'desc', $Limit)->ResultArray();
      $this->SetData('Groups', $Groups);

      // Get new groups.
      $NewGroups = $this->GroupModel->Get('DateInserted', 'desc', $Limit)->ResultArray();
      $this->SetData('NewGroups', $NewGroups);

      // Get my groups.
      if (Gdn::Session()->IsValid()) {
         $MyGroups = $this->GroupModel->GetByUser(Gdn::Session()->UserID, $Limit);
         $this->SetData('MyGroups', $MyGroups);
      }

      if ($this->DeliveryType() !== DELIVERY_TYPE_DATA) {
         $this->Title(T('Groups'));

         require_once $this->FetchViewLocation('group_functions', 'Group');
         $this->CssClass .= ' NoPanel';
      }
      $this->Render('Groups');
   }

   public function Browse($Sort = 'newest', $Page = '') {
      Gdn_Theme::Section('GroupList');
      $Sort = strtolower($Sort);

      $Sorts = array(
         'new' => array('Title' => T('New Groups'), 'OrderBy' => 'DateInserted'),
         'popular' => array('Title' => T('Popular Groups'), 'OrderBy' => 'CountMembers'),
         'updated' => array('Title' => T('Recently Updated Groups'), 'OrderBy' => 'DateLastComment'),
         'mine' => array('Title' => T('My Groups'), 'OrderBy' => 'DateInserted')
      );

      if (!array_key_exists($Sort, $Sorts)) {
         $Sort = array_pop(array_keys($Sorts));
      }

      $SortRow = $Sorts[$Sort];
      $PageSize = $this->PageSize; // good size for 4, 3, 2 columns.
      list($Offset, $Limit) = OffsetLimit($Page, $PageSize);
      $PageNumber = PageNumber($Offset, $Limit);

      if (Gdn::Session()->UserID && $Sort == 'mine') {
          $Groups = $this->GroupModel->GetByUser(Gdn::Session()->UserID, '', 'desc', $Limit, $Offset);
      } else {
          $Groups = $this->GroupModel->Get($SortRow['OrderBy'], 'desc', $Limit, $PageNumber)->ResultArray();
          $TotalRecords = $this->GroupModel->GetCount();
      }
      $this->SetData('Groups', $Groups);

      // Set the pager data.
      $this->SetData('_Limit', $Limit);
      $this->SetData('_CurrentRecords', count($Groups));

      $Pager = PagerModule::Current();
      // Use simple pager for 'mine'
      if (Gdn::Session()->UserID && $Sort != 'mine') {
         $Pager->Configure($Offset, $Limit, $TotalRecords, "groups/browse/$Sort/{Page}");
      }

      $this->Title($SortRow['Title']);
      $this->AddBreadcrumb($this->Title(), "/groups/browse/$Sort");

      require_once $this->FetchViewLocation('group_functions', 'Group');
      $this->Render();
   }
}
