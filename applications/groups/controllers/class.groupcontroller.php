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
    * Should the discussions have their options available.
    * 
    * @since 2.0.0
    * @access public
    * @var bool
    */
   public $ShowOptions = TRUE;
      
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
      $Discussions = $DiscussionModel->GetWhere(array('d.GroupID' => $GroupID, 'd.Announce' => 0), 0, 10)->ResultArray();
      $this->SetData('Discussions', $Discussions);
      
      $Discussions = $DiscussionModel->GetWhere(array('d.GroupID' => $GroupID, 'd.Announce >' => 0), 0, 10)->ResultArray();
      $this->SetData('Announcements', $Discussions);
      
      // Get Events
      $MaxEvents = C('Groups.Events.MaxList', 5);
      $EventModel = new EventModel();
      $Events = $EventModel->GetWhere(array(
         'GroupID'      => $GroupID,
         'DateEnds >=' => gmdate('Y-m-d H:i:s')
         ),
         'DateStarts', 'asc', $MaxEvents)->ResultArray();
      $this->SetData('Events', $Events);
      
      // Get applicants.
      $Applicants = $this->GroupModel->GetApplicants($GroupID, array('Type' => 'Application'), 20);
      $this->SetData('Applicants', $Applicants);
      
      // Get Leaders
      $Users = $this->GroupModel->GetMembers($GroupID, array('Role' => 'Leader'));
      $this->SetData('Leaders', $Users);
      
      // Get Members
      $Users = $this->GroupModel->GetMembers($GroupID, array('Role' => 'Member'), 30);
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
   
   public function Approve($Group, $ID, $Value = 'approved') {
      $Group = $this->GroupModel->GetID($Group);
      if (!$Group)
         throw NotFoundException('Group');
      
      // Check leader permission.
      if (!$this->GroupModel->CheckPermission('Leader', $Group)) {
         throw ForbiddenException('@'.$this->GroupModel->CheckPermission('Leader.Reason', $Group));
      }
      
      $Value = ucfirst($Value);
      
      $this->GroupModel->JoinApprove(array(
         'GroupApplicantID' => $ID,
         'Type' => $Value
      ));
      
      if ($Value == 'Approved') {
         $this->JsonTarget("#GroupApplicant_$ID", "", 'SlideUp');
      } else {
         $this->JsonTarget("#GroupApplicant_$ID", "Read Join-Denied", 'AddClass');
      }
      
      $this->Render('Blank', 'Utility', 'Dashboard');
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
      $Ext = GetValue('OutputType', $Options, trim($Upload->GetUploadedFileExtension(), '.'));
      if ($Ext == 'jpeg')
         $Ext = 'jpg';
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
      
      // Get a list of categories suitable for the category dropdown.
      $Categories = array_filter(CategoryModel::Categories(), function($Row) { return $Row['AllowGroups']; });
      $Categories = ConsolidateArrayValuesByKey($Categories, 'CategoryID', 'Name');
      $this->SetData('Categories', $Categories);
      
      if ($Form->AuthenticatedPostBack()) {
         // We need to save the images before saving to the database.
         self::SaveImage($Form, 'Icon', array('Prefix' => 'groups/icons/icon_', 'Size' => C('Groups.IconSize', 100), 'Crop' => TRUE));
         self::SaveImage($Form, 'Banner', array('Prefix' => 'groups/banners/banner_', 'Size' => C('Groups.BannerSize', '1000x250'), 'Crop' => TRUE, 'OutputType' => 'jpeg'));
         
         $GroupID = $Form->Save();
         if ($GroupID) {
            $Group = $this->GroupModel->GetID($GroupID);
            Redirect(GroupUrl($Group));
         } else {
            Trace($Form->FormValues());
            $Form->AddError('What!?!?');
         }
      } else {
         if ($ID) {
            // Load the group.
            $Form->SetData($Group);
            $Form->AddHidden('GroupID', $Group['GroupID']);
         } else {
            // Set some default settings.
            $Form->SetValue('Registration', 'Public');
            $Form->SetValue('Visibility', 'Public');
            
            if (Count($Categories == 1)) {
               $Form->SetValue('CategoryID', array_pop(array_keys($Categories)));
            }
         }
      }
      $this->Form = $Form;
      $this->CssClass .= ' NoPanel NarrowForm';
      $this->Render('AddEdit');
   }
   
   public function Discussions($ID, $Page = FALSE) {
      Gdn_Theme::Section('DiscussionList');
      
      $Group = $this->GroupModel->GetID($ID);
      if (!$Group)
         throw NotFoundException('Group');
      
      $this->SetData('Group', $Group);
      
      list($Offset, $Limit) = OffsetLimit($Page, C('Vanilla.Discussions.PerPage', 30));
      $DiscussionModel = new DiscussionModel();
      $this->DiscussionData = $this->SetData('Discussions', $DiscussionModel->GetWhere(array('GroupID' => $Group['GroupID']), $Offset, $Limit));
      $this->CountCommentsPerPage = C('Vanilla.Comments.PerPage', 30);
      $this->SetData('_ShowCategoryLink', FALSE);
      
      // Add modules
      $NewDiscussionModule = new NewDiscussionModule();
      $NewDiscussionModule->QueryString = 'groupid='.$Group['GroupID'];
      $this->AddModule($NewDiscussionModule);
      $this->AddModule('DiscussionFilterModule');
      $this->AddModule('CategoriesModule');
      $this->AddModule('BookmarkedModule');
      
      $this->SetData('_NewDiscussionProperties', array('CssClass' => 'Button Action Primary', 'QueryString' => $NewDiscussionModule->QueryString));
      
      $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
      $this->AddBreadcrumb(T('Discussions'));
      
      $Layout = C('Vanilla.Discussions.Layout');
      switch($Layout) {
         case 'table':
            if ($this->SyndicationMethod == SYNDICATION_NONE)
               $this->View = 'table';
            break;
         default:
             $this->View = 'index';
            break;
      }
      
      if ($this->Head) {
         $this->AddJsFile('discussions.js');
         $this->Head->AddRss($this->SelfUrl.'/feed.rss', $this->Head->Title());
      }
      
      $this->Title(GetValue('Name', $Group, ''));
      $this->Description(GetValue('Description', $Group), TRUE);
      $this->Render($this->View, 'Discussions', 'Vanilla');
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
      $this->AddBreadcrumb(T('GroupMembers', 'Members'));
      
      // Get Leaders
      $UserModel = new UserModel();
      $Users = $this->GroupModel->GetMembers($Group['GroupID'], array('Role' => 'Leader'));
      $this->SetData('Leaders', $Users);
      
      // Get Members
      $Users = $this->GroupModel->GetMembers($Group['GroupID'], array('Role' => 'Member'));
      $this->SetData('Members', $Users);
      
      $this->Title(T('Members').' - '.htmlspecialchars($Group['Name']));
      require_once $this->FetchViewLocation('group_functions');
      $this->CssClass .= ' NoPanel';
      $this->Render('Members');
   }
   
   public function SetRole($ID, $UserID, $Role) {
      $Group = $this->GroupModel->GetID($ID);
      if (!$Group)
         throw NotFoundException('Group');
      
      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
      if (!$User)
         throw NotFoundException('User');
      
      if (!$this->GroupModel->CheckPermission('Edit', $Group))
         throw ForbiddenException('@'.$this->GroupModel->CheckPermission('Edit.Reason', $Group));
      
      $GroupID = $Group['GroupID'];
      
      $Member = $this->GroupModel->GetMembers($Group['GroupID'], array('UserID' => $UserID));
      $Member = array_pop($Member);
      if (!$Member)
         throw NotFoundException('Member');
      
      // You can't demote the user that started the group.
      if ($UserID == $Group['InsertUserID']) {
         throw ForbiddenException('@'.T("The user that started the group has to be a leader."));
      }
      
      if ($this->Request->IsPostBack()) {
         $Role = ucfirst($Role);
         $this->GroupModel->SetRole($GroupID, $UserID, $Role);
         
         $this->InformMessage(sprintf(T('%s is now a %s.'), htmlspecialchars($User['Name']), $Role));
      }
      
      $this->SetData('Group', $Group);
      $this->SetData('User', $User);
      $this->Title(T('Group Role'));
      $this->Render();
   }
   
   public function RemoveMember($ID, $UserID) {
      $Group = $this->GroupModel->GetID($ID);
      if (!$Group)
         throw NotFoundException('Group');
      
      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
      if (!$User)
         throw NotFoundException('User');
      
      if ($UserID == Gdn::Session()->UserID) {
         Gdn::Dispatcher()->Dispatch(GroupUrl($Group, 'leave'));
         return;
      }
      
      if (!$this->GroupModel->CheckPermission('Moderate', $Group))
         throw ForbiddenException('@'.$this->GroupModel->CheckPermission('Moderate.Reason', $Group));
      
      $GroupID = $Group['GroupID'];
      
      $Member = $this->GroupModel->GetMembers($Group['GroupID'], array('UserID' => $UserID));
      $Member = array_pop($Member);
      if (!$Member)
         throw NotFoundException('Member');
      
      // You can't remove the user that started the group.
      if ($UserID == $Group['InsertUserID']) {
         throw ForbiddenException('@'.T("You can't remove the creator of the group."));
      }
      
      // Only users that can edit the group can remove leaders.
      if ($Member['Role'] == 'Leader' && !GroupPermission('Edit')) {
         throw ForbiddenException('@'.T("You can't remove another leader of the group."));
      }
      
      $Form = new Gdn_Form();
      $this->Form = $Form;
      
      if ($Form->AuthenticatedPostBack()) {
         $this->GroupModel->RemoveMember($GroupID, $UserID, $this->Form->GetFormValue('Type'));
         
         $this->JsonTarget("#Member_$UserID", NULL, "Remove");
      } else {
         $Form->SetValue('Type', 'Removed');
      }
      
      $this->SetData('Group', $Group);
      $this->SetData('User', $User);
      $this->Title(T('Remove Member'));
      $this->Render();
   }
}