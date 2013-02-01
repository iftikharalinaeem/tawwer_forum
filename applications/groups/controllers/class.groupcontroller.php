<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class GroupController extends Gdn_Controller {
   
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
      $this->AddCssFile('groups.css');
      
      parent::Initialize();
   }
   
   /**
    * The homepage for a group.
    * 
    * @param string $Group Url friendly code for the group in the form ID-url-friendly-name
    */
   public function Index($ID) {
      Gdn_Theme::Section('Group');
      
      $Group = $this->GroupModel->GetID($ID);
      if (!$Group)
         throw NotFoundException('Group');
      
      // Force the canonical url.
      if ($ID != GroupSlug($Group))
         Redirect(GroupUrl($Group), 301);
      $this->CanonicalUrl(Url(GroupUrl($Group), '//'));
      
      $this->SetData('Group', $Group);
      
      $this->Title(htmlspecialchars($Group['Name']));
      require_once $this->FetchViewLocation('group_functions');
      $this->CssClass .= ' NoPanel';
      $this->Render('Group');
   }
   
   /**
    * The member list of a group.
    * 
    * @param string $Group
    * @param string $Page
    */
   public function Members($Group, $Page = FALSE) {
   }
}