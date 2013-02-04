<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class GroupController extends Gdn_Controller {
   
   public $Uses = array('GroupModel', 'EventModel');
   
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
      $this->AddCssFile('groups.css');
      
      $this->AddBreadcrumb(T('Groups'), Url('/groups'));
      
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
      
      $GroupID = $Group['GroupID'];
      
      // Force the canonical url.
      if ($ID != GroupSlug($Group))
         Redirect(GroupUrl($Group), 301);
      $this->CanonicalUrl(Url(GroupUrl($Group), '//'));
      
      $this->SetData('Group', $Group);
      $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
      
      // Get Discussions
      $DiscussionModel = new DiscussionModel();
      $Discussions = $DiscussionModel->GetWhere(array('DiscussionID <' => 10))->ResultArray(); // FAKE IT
      $this->SetData('Discussions', $Discussions);
      
      $Discussions = $DiscussionModel->GetWhere(array('DiscussionID <' => 6))->ResultArray(); // FAKE IT
      $this->SetData('Announcements', $Discussions);
      
      // Get Events
      $EventModel = new EventModel();
      $Events = $EventModel->GetWhere(array('EventID <' => 5))->ResultArray(); // FAKE IT
      $this->SetData('Events', $Events);
      
      // Get Leaders
      $Users = $this->GroupModel->GetMembers($GroupID, array('Role' => 'Leader'));
      $this->SetData('Leaders', $Users);
      
      // Get Members
      $Users = $this->GroupModel->GetMembers($GroupID, array('Role' => 'Member'));
      $this->SetData('Members', $Users);
      
      $this->Title(htmlspecialchars($Group['Name']));
      $this->Description(Gdn_Format::PlainText($Group['Description'], $Group['Format']));
      if ($Group['Icon']) {
         $this->Image(Gdn_Upload::Url($Group['Icon']));
      }
      require_once $this->FetchViewLocation('group_functions');
      $this->CssClass .= ' NoPanel';
      $this->Render('Group');
   }
   
   public function Add() {
      $this->Title(sprintf(T('New %s'), T('Group')));
      return $this->AddEdit();
   }
   
   public function Join($ID) {
      $Group = $this->GroupModel->GetID($ID);
      if (!$Group)
         throw NotFoundException('Group');
      
      // Check join permission.
      if (!$this->GroupModel->CheckPermission('Join', $Group)) {
         throw ForbiddenException('@'.$this->GroupModel->CheckPermission('Join.Reason', $Group));
      }
      
      $this->SetData('Title', sprintf(T('Join %s'), htmlspecialchars($Group['Name'])));
      
      $Form = new Gdn_Form();
      $this->Form = $Form;
      
      if ($Form->AuthenticatedPostBack()) {
         // If the user posted back then we are going to add them.
         $Data = $Form->FormValues();
         $Data['UserID'] = Gdn::Session()->UserID;
         $Data['GroupID'] = $Group['GroupID'];
         $Saved = $this->GroupModel->Join($Data);
         $Form->SetValidationResults($this->GroupModel->ValidationResults());
         
         if ($Saved)
            $this->RedirectUrl = Url(GroupUrl($Group));
      }
      
      $this->SetData('Group', $Group);
      $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
      $this->Render();
   }
   
   public function Leave($ID) {
      $Group = $this->GroupModel->GetID($ID);
      if (!$Group)
         throw NotFoundException('Group');
      
      // Check join permission.
      if (!$this->GroupModel->CheckPermission('Leave', $Group)) {
         throw ForbiddenException('@'.$this->GroupModel->CheckPermission('Leave.Reason', $Group));
      }
      
      $this->SetData('Title', sprintf(T('Leave %s'), htmlspecialchars($Group['Name'])));
      
      $Form = new Gdn_Form();
      $this->Form = $Form;
      
      if ($Form->AuthenticatedPostBack()) {
         $Data = array(
            'UserID' => Gdn::Session()->UserID,
            'GroupID' => $Group['GroupID']);
         $this->GroupModel->Leave($Data);
         $this->RedirectUrl = Url(GroupUrl($Group));
      }
      
      $this->SetData('Group', $Group);
      $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
      $this->Render();
   }
   
   /**
    * Save an image from a field and delete any old image that's been uploaded.
    * This method is a canditate for putting on the form object.
    * 
    * @param Gdn_Form $Form
    * @param string $Field The name of the field. The image will be uploaded with the _New extension while the current image will be just the field name.
    * @param array $Options
    */
   protected static function SaveImage($Form, $Field, $Options = array()) {
      $Upload = new Gdn_UploadImage();
      
      if (!GetValueR("{$Field}_New.name", $_FILES)) {
         Trace("$Field not uploaded, returning.");
         return FALSE;
      }
      
      // First make sure the file is valid.
      try {
         $TmpName = $Upload->ValidateUpload($Field.'_New', TRUE);
         
         if (!$TmpName)
            return FALSE; // no file uploaded.
      } catch (Exception $Ex) {
         $Form->AddError($Ex);
         return FALSE;
      }
      
      // Get the file extension of the file.
      $Ext = trim($Upload->GetUploadedFileExtension(), '.');
      Trace($Ext, 'Ext');
      
      // The file is valid so let's come up with its new name.
      if (isset($Options['Name']))
         $Name = $Options['Name'];
      elseif (isset($Options['Prefix']))
         $Name = $Options['Prefix'].md5(microtime()).'.'.$Ext;
      else
         $Name = md5(microtime()).'.'.$Ext;
      
      // We need to parse out the size.
      $Size = GetValue('Size', $Options);
      if ($Size) {
         if (is_numeric($Size)) {
            TouchValue('Width', $Options, $Size);
            TouchValue('Height', $Options, $Size);
         } elseif (preg_match('`(\d+)x(\d+)`i', $Size, $M)) {
            TouchValue('Width', $Options, $M[1]);
            TouchValue('Height', $Options, $M[2]);
         }
      }
      
      Trace($Options, "Saving image $Name.");
      try {
         $Parsed = $Upload->SaveImageAs($TmpName, $Name, GetValue('Height', $Options, ''), GetValue('Width', $Options, ''), $Options);
         Trace($Parsed, 'Saved Image');
         
         $Current = $Form->GetFormValue($Field);
         if ($Current && GetValue('DeleteOriginal', $Options, TRUE)) {
            // Delete the current image.
            Trace("Deleting original image: $Current.");
            if ($Current)
               $Upload->Delete($Current);
         }
         
         // Set the current value.
         $Form->SetFormValue($Field, $Parsed['SaveName']);
      } catch (Exception $Ex) {
         $Form->AddError($Ex);
      }
   }
   
   protected function AddEdit($ID = FALSE) {
      $Form = new Gdn_Form();
      $Form->SetModel($this->GroupModel);
      
      if ($ID) {
         $Group = $this->GroupModel->GetID($ID);
         $this->SetData('Group', $Group);
         $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
      }
      
      if ($Form->AuthenticatedPostBack()) {
         // We need to save the images before saving to the database.
         self::SaveImage($Form, 'Icon', array('Prefix' => 'groups/icons/icon_', 'Size' => C('Groups.IconSize', 100), 'Crop' => TRUE));
         self::SaveImage($Form, 'Banner', array('Prefix' => 'groups/banners/banner_', 'Size' => C('Groups.BannerSize', '1000x250'), 'Crop' => TRUE));
         
         $GroupID = $Form->Save();
         if ($GroupID) {
            $Group = $this->GroupModel->GetID($GroupID);
            Redirect(GroupUrl($Group));
         }
         // If we're here then there was some error and the form has to be rendered again.
         $Form->AddHidden('GroupID');
      } else {
         if ($ID) {
            // Load the group.
            $Form->SetData($Group);
            $Form->AddHidden('GroupID', $Group['GroupID']);
         } else {
            // Set some default settings.
            $Form->SetValue('Registration', 'Public');
            $Form->SetValue('Visibility', 'Public');
         }
      }
      
      $this->Form = $Form;
      $this->CssClass .= ' NoPanel';
      $this->Render('AddEdit');
   }
   
   public function Edit($ID) {
      $this->Title(sprintf(T('Edit %s'), T('Group')));
      return $this->AddEdit($ID);
   }
   
   /**
    * The member list of a group.
    * 
    * @param string $ID
    * @param string $Page
    */
   public function Members($ID, $Page = FALSE) {
      Gdn_Theme::Section('Group');
      Gdn_Theme::Section('Members');
      
      $Group = $this->GroupModel->GetID($ID);
      if (!$Group)
         throw NotFoundException('Group');
      
      $this->SetData('Group', $Group);
      $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
      
      // Get Leaders
      $UserModel = new UserModel();
      $Users = $UserModel->GetWhere(array('UserID <' => 5))->ResultArray(); // FAKE IT
      $this->SetData('Leaders', $Users);
      
      // Get Members
      $Users = $UserModel->GetWhere(array('UserID <' => 50))->ResultArray(); // FAKE IT
      $this->SetData('Members', $Users);
      
      $this->Title(htmlspecialchars($Group['Name']));
      require_once $this->FetchViewLocation('group_functions');
      $this->CssClass .= ' NoPanel';
      $this->Render('Members');
   }
}