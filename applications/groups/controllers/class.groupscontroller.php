<?php if (!defined('APPLICATION')) exit();
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
      $this->AddJsFile('jquery-ui-1.8.17.custom.min.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      $this->AddCssFile('style.css');
      
      parent::Initialize();
   }
   
   public function Index() {
      
      // Get popular groups.
      $Groups = $this->GroupModel->Get('CountMembers', 'desc', 10)->ResultArray();
      $this->SetData('Groups', $Groups);
      
      // Get new groups.
      $NewGroups = $this->GroupModel->Get('DateInserted', 'desc', 10)->ResultArray();
      $this->SetData('NewGroups', $NewGroups);
      
      // Get my groups.
      if (Gdn::Session()->IsValid()) {
         $MyGroups = $this->GroupModel->GetByUser(Gdn::Session()->UserID);
         $this->SetData('MyGroups', $MyGroups);
      }
      
      $this->Title(T('Groups'));
      
      require_once $this->FetchViewLocation('group_functions', 'Group');
      $this->Render('Groups');
   }
}