<?php
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class GroupController extends Gdn_Controller {

   /**
    * @var string The path to the group icons folder.
    */
   const GROUP_ICON_FOLDER = 'groups/icons';

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
    * @var int
    */
   public $HomepageDiscussionCount = 10;

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
      $this->AddJsFile('event.js');
      $this->addCssFile('vanillicon.css', 'static');
      $this->AddCssFile('style.css');

      $this->AddBreadcrumb(T('Groups'), '/groups');
      $this->CountCommentsPerPage = C('Vanilla.Comments.PerPage', 30);

      parent::Initialize();
   }

   /**
    * The homepage for a group.
    *
    * @param string $Group Url friendly code for the group in the form ID-url-friendly-name
    */
   public function Index($ID) {
      Gdn_Theme::Section('Group');
      $this->AllowJSONP(TRUE);

      $Group = $this->GroupModel->GetID($ID);
      if (!$Group)
         throw NotFoundException('Group');

      $GroupID = $Group['GroupID'];

      // Force the canonical url.
      if (rawurlencode($ID) != GroupSlug($Group)) {
         Redirect(GroupUrl($Group), 301);
      }
      $this->CanonicalUrl(Url(GroupUrl($Group), '//'));

      $this->SetData('Group', $Group);
      $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));

      $this->GroupModel->OverridePermissions($Group);

      // Get Discussions
      $discussionModel = new DiscussionModel();
      $discussions = $discussionModel->getWhere(array('d.GroupID' => $GroupID, 'd.Announce' => 0), 0, $this->HomepageDiscussionCount);
      $this->setData('Discussions', $discussions);

      $discussions = $discussionModel->getAnnouncements(array('d.GroupID' => $GroupID), 0, 10);
      $this->setData('Announcements', $discussions);

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
      $Applicants = $this->GroupModel->GetApplicants($GroupID, array('Type' => array('Application', 'Invitation')), 20);
      $this->SetData('Applicants', $Applicants);

      // Get Leaders
      $Users = $this->GroupModel->GetMembers($GroupID, array('Role' => 'Leader'), 10);
      foreach ($Users as &$User) {
         if ($User['UserID'] == $Group['InsertUserID'])
            $User['Role'] = 'Owner';
      }
      $this->SetData('Leaders', $Users);

      // Get Members
      $Users = $this->GroupModel->GetMembers($GroupID, array('Role' => 'Member'), 30);
      $this->SetData('Members', $Users);

      $this->Title(htmlspecialchars($Group['Name']));
      $this->Description(Gdn_Format::PlainText($Group['Description'], $Group['Format']));
      if ($Group['Icon']) {
         $this->Image(Gdn_Upload::Url($Group['Icon']));
      }

      $this->Data['_properties']['newdiscussionmodule'] = array('CssClass' => 'Button Action Primary', 'QueryString' => 'groupid='.$GroupID);

      require_once $this->FetchViewLocation('event_functions', 'event');
      require_once $this->FetchViewLocation('group_functions');
      $this->CssClass .= ' NoPanel';
      $this->AddJsFile('discussions.js', 'vanilla');
      $this->Render('Group');
   }

   public function Add() {
      $this->Title(sprintf(T('New %s'), T('Group')));

      // Check the max groups.
      if ($this->GroupModel->MaxUserGroups > 0 && Gdn::Session()->IsValid()) {
         $this->SetData('MaxUserGroups', $this->GroupModel->MaxUserGroups);
         $this->SetData('CountUserGroups', $this->GroupModel->GetUserCount(Gdn::Session()->UserID));
         $CountRemaining = max(0, $this->Data('MaxUserGroups') - $this->Data('CountUserGroups'));

         $this->SetData('CountRemainingGroups', $CountRemaining);

         if ($CountRemaining <= 0) {
            $this->Form = new Gdn_Form();
            $this->Form->AddError("You've already created the maximum number of groups.");
            $this->Render('AddEditError');
         }
      }

      return $this->AddEdit();
   }

   public function Announcement($Group) {
      $Group = $this->GroupModel->GetID($Group);
      if (!$Group)
         throw NotFoundException('Group');

      // Check leader permission.
      if (!$this->GroupModel->CheckPermission('Moderate', $Group)) {
         throw ForbiddenException('@'.$this->GroupModel->CheckPermission('Moderate.Reason', $Group));
      }

      $this->SetData('Group', $Group);

      $Form = new Gdn_Form();
      $this->Form = $Form;

      if ($Form->AuthenticatedPostBack()) {
         // Let's save the announcement.
         $Form->SetFormValue('CategoryID', $Group['CategoryID']);
         $Form->SetFormValue('GroupID', $Group['GroupID']);
         $Form->SetFormValue('Announce', 2); // Announce within group.


         $Model = new DiscussionModel();
         $Form->SetModel($Model);

         if ($Form->Save()) {
            $this->RedirectUrl = GroupUrl($Group);
         } else {
            $Form->SetValidationResults($Model->ValidationResults());
         }
      }

      $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
      $this->Title(T('New Announcement'));
      $this->Render();
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

   public function Invite($ID) {
      $Group = $this->GroupModel->GetID($ID);
      if (!$Group) {
         throw NotFoundException('Group');
      }

      // Check invite permission.
      if (!$this->GroupModel->CheckPermission('Leader', $Group)) {
         throw ForbiddenException('@'.$this->GroupModel->CheckPermission('Join.Reason', $Group));
      }

      $this->Title(T('Invite'));

      $Form = new Gdn_Form();
      $this->Form = $Form;

      if ($Form->AuthenticatedPostBack()) {
         // If the user posted back then we are going to add them.
         $Data = $Form->FormValues();
         $Data['GroupID'] = $Group['GroupID'];
         $Recipients = explode(',', $Data['Recipients']);
         $UserIDs = array();
         $memberIds = $this->GroupModel->getMemberIds(val('GroupID', $Group));
         $applicantIds = $this->GroupModel->getApplicantIds(val('GroupID', $Group), array('Type' => array('Application', 'Invitation')));
         foreach ($Recipients as $Recipient) {
            $userId = GetValue('UserID', Gdn::UserModel()->GetByUsername($Recipient));
            if (in_array($userId, $memberIds)) {
               $this->InformMessage(t(sprintf("%s is already a member.", $Recipient)));
            } elseif (in_array($userId, $applicantIds)) {
              $this->InformMessage(t(sprintf("%s is already an applicant.", $Recipient)));
            } else {
               $UserIDs[] = $userId;
            }
         }
         if ($UserIDs) {
            $Data['UserID'] = $UserIDs;
            $Saved = $this->GroupModel->Invite($Data);
            if ($Saved) {
               $this->InformMessage(t('Invitation sent.'));
               $Form->SetValidationResults($this->GroupModel->ValidationResults());
            }
         }
      }

      $this->SetData('Group', $Group);
      $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
      $this->Render();
   }

   public function InviteAccept($ID) {
      $Group = $this->GroupModel->GetID($ID);
      if (!$Group)
         throw NotFoundException('Group');

      if (!$this->Request->IsPostBack())
         throw ForbiddenException('GET');

      $Result = $this->GroupModel->JoinInvite($Group['GroupID'], Gdn::Session()->UserID, TRUE);
      $this->SetData('Result', $Result);
      $this->RedirectUrl = GroupUrl($Group);
      $this->Render('Blank', 'Utility', 'Dashboard');
   }

   public function InviteDecline($ID) {
      $Group = $this->GroupModel->GetID($ID);
      if (!$Group)
         throw NotFoundException('Group');

      if (!$this->Request->IsPostBack())
         throw ForbiddenException('GET');

      $Result = $this->GroupModel->JoinInvite($Group['GroupID'], Gdn::Session()->UserID, FALSE);
      $this->SetData('Result', $Result);
      $this->JsonTarget('.GroupUserHeaderModule', '', 'SlideUp');
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
         $this->JsonTarget('', '', 'Refresh');
      }

      $this->SetData('Group', $Group);
      $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
      $this->Render();
   }

   public function Delete($ID) {
      $Group = $this->GroupModel->GetID($ID);
      if (!$Group)
         throw NotFoundException('Group');
      $this->SetData('Group', $Group);

      if (!GroupPermission('Edit'))
         throw ForbiddenException('@'.GroupPermission('Edit.Reason'));

      $Form = new Gdn_Form();
      $this->Form = $Form;

      if ($this->Form->AuthenticatedPostBack()) {
         $GroupModel = new GroupModel();
         $GroupDeleted = $GroupModel->Delete(array('GroupID' => $Group['GroupID']));

         $EventModel = new EventModel();
         $EventDeleted = $EventModel->Delete(array('GroupID' => $Group['EventID']));

         if ($GroupDeleted) {
            $this->InformMessage(FormatString(T('<b>{Name}</b> deleted.'), $Group));
            $this->RedirectUrl = Url('/groups');
         } else {
            $this->InformMessage(T('Failed to delete group.'));
         }
      }

      $this->SetData('Title', T('Delete Group'));

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

    /**
     * Saves the group icon /uploads in two formats:
     *   The thumbnail-sized image, which is constrained and cropped according to Groups.IconSize.
     *   p* : The profile-sized image, which is constrained by Garden.Profile.MaxWidth and Garden.Profile.MaxHeight.
     *
     * @param string $source The path to the local copy of the image.
     * @param array $thumbOptions The options to save the thumbnail-sized icon with.
     * @return bool Whether the saves were successful.
     */
    private function saveIcons($source, $thumbOptions) {
        try {
            $upload = new Gdn_UploadImage();
            // Generate the target image name
            $targetImage = $upload->generateTargetName(PATH_UPLOADS);
            $imageBaseName = pathinfo($targetImage, PATHINFO_BASENAME);

            // Save the profile size image.
            Gdn_UploadImage::saveImageAs(
                $source,
                self::GROUP_ICON_FOLDER."/p$imageBaseName",
                c('Groups.Profile.MaxHeight', 1000),
                c('Groups.Profile.MaxWidth', 550),
                array('SaveGif' => c('Garden.Thumbnail.SaveGif'))
            );

            $thumbnailSize = c('Groups.IconSize', 140);
            // Save the thumbnail size image.
            $parts = Gdn_UploadImage::saveImageAs(
                $source,
                self::GROUP_ICON_FOLDER.'/'."$imageBaseName",
                $thumbnailSize,
                $thumbnailSize,
                $thumbOptions
            );
        } catch (Exception $ex) {
            $this->Form->addError($ex);
            return false;
        }

        $imageBaseName = $parts['SaveName'];
        return $imageBaseName;
    }

   protected function AddEdit($ID = FALSE) {
      $Form = new Gdn_Form();
      $Form->SetModel($this->GroupModel);
      Gdn_Theme::Section('Post');

      if ($ID) {
         $Group = $this->GroupModel->GetID($ID);

         if (!$Group) {
            throw NotFoundException('Group');
         }

         // Make sure the user can edit this group.
         if (!$this->GroupModel->CheckPermission('Edit', $Group))
            throw ForbiddenException('@'.$this->GroupModel->CheckPermission('Edit.Reason', $Group));

         $this->SetData('Group', $Group);
         $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
      } else {

      }

       $icon = val('Icon', $Group);

       //Get the image source so we can manipulate it in the crop module.
       $upload = new Gdn_UploadImage();
       $thumbnailSize = c('Groups.IconSize', 140);
       $this->setData('thumbnailSize', $thumbnailSize);

       // Uploaded icons used to be named 'icon_*' and only had one
       // image saved. This kludge checks to see if this is a new, cropped icon.
       $prefixes[] = 'icon_';
       $this->EventArguments['prefixes'] = &$prefixes;
       $this->fireEvent('beforeIconDisplay');
       $oldIcon = false;
       foreach($prefixes as $prefix) {
           if (strpos($icon, $prefix) !== false) {
               $oldIcon = true;
           }
       }

       if ($icon && $this->isUploadedGroupIcon($icon) && !$oldIcon) {
           //Get the image source so we can manipulate it in the crop module.
           $upload = new Gdn_UploadImage();
           $basename = changeBasename($icon, "p%s");
           $source = $upload->copyLocal($basename);

           //Set up cropping.
           $crop = new CropImageModule($this, $Form, $thumbnailSize, $thumbnailSize, $source);
           $crop->setExistingCropUrl(Gdn_UploadImage::url($icon));
           $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($icon, "p%s")));
           $this->setData('crop', $crop);
           $this->setData('icon', Gdn_UploadImage::url($icon));
       } elseif ($icon && $this->isUploadedGroupIcon($icon)) {
           // old icon, no crop set.
           $this->setData('icon', Gdn_UploadImage::url($icon));
       } elseif ($icon) {
           // not an uploaded icon
           $this->setData('icon', $icon);
       } else {
           // no icon, check for default.
           $this->setData('icon', c('Groups.DefaultIcon', ''));
       }

      // Get a list of categories suitable for the category dropdown.
      $Categories = array_filter(CategoryModel::Categories(), function($Row) { return $Row['AllowGroups']; });
      $Categories = ConsolidateArrayValuesByKey($Categories, 'CategoryID', 'Name');
      $this->SetData('Categories', $Categories);

      if ($Form->AuthenticatedPostBack()) {

          // We need to save the images before saving to the database.
          self::SaveImage($Form, 'Banner', array('Prefix' => 'groups/banners/banner_', 'Size' => C('Groups.BannerSize', '1000x250'), 'Crop' => TRUE, 'OutputType' => 'jpeg'));

          if ($tmpIcon = $upload->validateUpload('Icon_New', false)) {
              // New upload
              $thumbOptions = array('Crop' => true, 'SaveGif' => c('Garden.Thumbnail.SaveGif'));
              $newIcon = $this->saveIcons($tmpIcon, $thumbOptions);
              $Form->SetFormValue('Icon', $newIcon);
          } else if ($icon && $crop && $crop->isCropped()) {
              // New thumbnail
              $tmpIcon = $source;
              $thumbOptions = array('Crop' => true,
                  'SourceX' => $crop->getCropXValue(),
                  'SourceY' => $crop->getCropYValue(),
                  'SourceWidth' => $crop->getCropWidth(),
                  'SourceHeight' => $crop->getCropHeight());
              $newIcon = $this->saveIcons($tmpIcon, $thumbOptions);
              $Form->SetFormValue('Icon', $newIcon);
          }
          if ($Form->errorCount() == 0) {
              if ($newIcon) {
                  $Form->SetFormValue('Icon_New', $newIcon);
                  $icon = $newIcon;

                  // Update crop properties.
                  $basename = changeBasename($icon, "p%s");
                  $source = $upload->copyLocal($basename);
                  $crop = new CropImageModule($this, $Form, $thumbnailSize, $thumbnailSize, $source);
                  $crop->setExistingCropUrl(Gdn_UploadImage::url($icon));
                  $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($icon, "p%s")));
                  $this->setData('crop', $crop);
              }
          }

         // Make sure the group ID can't be spoofed.
         if ($ID)
            $Form->SetFormValue('GroupID', $this->Data('Group.GroupID'));

         try {
            $GroupID = $Form->Save();
         } catch (Exception $Ex) {
            $Form->AddError($Ex);
         }
         if ($GroupID) {
            $Group = $this->GroupModel->GetID($GroupID);
            Redirect(GroupUrl($Group));
         } else {
            Trace($Form->FormValues());
         }
      } else {
         if ($ID) {
            // Load the group.
            $Form->SetData($Group);
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

    /**
     * Settings page for uploading, deleting and cropping a group icon.
     *
     * @throws Exception
     */
    public function groupIcon($id = false) {
        if(!$id) {
            throw NotFoundException();
        }
        $form = new Gdn_Form();
        $form->setModel($this->GroupModel);
        $this->title(t('Group Icon'));
        $this->addJsFile('groupicons.js');
        if ($id) {
            $group = $this->GroupModel->GetID($id);
            if (!$group) {
                throw NotFoundException('Group');
            }

            // Make sure the user can edit this group.
            if (!$this->GroupModel->CheckPermission('Edit', $group)) {
                throw ForbiddenException('@' . $this->GroupModel->CheckPermission('Edit.Reason', $group));
            }
            $this->SetData('Group', $group);
            $this->AddBreadcrumb($group['Name'], GroupUrl($group));
        }
        $thumbnailSize = c('Groups.IconSize', 140);
        $this->setData('thumbnailSize', $thumbnailSize);
        $icon = val('Icon', $group);

        // Uploaded icons used to be named 'icon_*' and only had one
        // image saved. This kludge checks to see if this is a new, cropped icon.
        $oldIcon = strpos($icon, 'icon_');

        if ($icon && $this->isUploadedGroupIcon($icon) && !$oldIcon) {
            //Get the image source so we can manipulate it in the crop module.
            $upload = new Gdn_UploadImage();
            $basename = changeBasename($icon, "p%s");
            $source = $upload->copyLocal($basename);

            //Set up cropping.
            $crop = new CropImageModule($this, $form, $thumbnailSize, $thumbnailSize, $source);
            $crop->setExistingCropUrl(Gdn_UploadImage::url($icon));
            $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($icon, "p%s")));
            $this->setData('crop', $crop);
        } elseif ($icon && $this->isUploadedGroupIcon($icon)) {
            // old icon, no crop set.
            $this->setData('icon', Gdn_UploadImage::url($icon));
        } elseif ($icon) {
            $this->setData('icon', $icon);
        } else {
            $this->setData('icon', c('Groups.DefaultIcon', ''));
        }
        if (!$form->authenticatedPostBack()) {
//            $form->setData($this->GroupModel->Data);
        } else {
            $target = null; // redirect to group home
            $form->setData($group);
            $upload = new Gdn_UploadImage();
            $newIcon = false;
            if ($tmpIcon = $upload->validateUpload('Icon', false)) {
                // New upload
                $thumbOptions = array('Crop' => true, 'SaveGif' => c('Garden.Thumbnail.SaveGif'));
                $newIcon = $this->saveIcons($tmpIcon, $thumbOptions);
                $form->SetFormValue('Icon', $newIcon);
                $target = 'groupicon'; // redirect to groupicon page so user can set thumbnail
            } else if ($icon && $crop && $crop->isCropped()) {
                // New thumbnail
                $tmpIcon = $source;
                $thumbOptions = array('Crop' => true,
                    'SourceX' => $crop->getCropXValue(),
                    'SourceY' => $crop->getCropYValue(),
                    'SourceWidth' => $crop->getCropWidth(),
                    'SourceHeight' => $crop->getCropHeight());
                $newIcon = $this->saveIcons($tmpIcon, $thumbOptions);
                $form->SetFormValue('Icon', $newIcon);
            }
            if ($form->errorCount() == 0) {
                if ($newIcon) {
                    if (!$this->GroupModel->save(array('GroupID' => val('GroupID', $group), 'Icon' => $newIcon))) {
                        $form->setValidationResults($this->GroupModel->validationResults());
                    } else {
                        $this->deleteGroupIcons($icon);
                        $icon = $newIcon;
                        // Update crop properties.
                        $basename = changeBasename($icon, "p%s");
                        $source = $upload->copyLocal($basename);
                        $crop = new CropImageModule($this, $form, $thumbnailSize, $thumbnailSize, $source);
                        $crop->setSize($thumbnailSize, $thumbnailSize);
                        $crop->setExistingCropUrl(Gdn_UploadImage::url($icon));
                        $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($icon, "p%s")));
                        $this->setData('crop', $crop);
                    }
                    $this->informMessage(t("Your settings have been saved."));
                    Redirect(GroupUrl($group, $target));
                }
            }
//            $this->informMessage(t("Your settings have been saved."));
        }
        $this->Form = $form;
        $this->render();
    }

    /**
     * Remove the icon from db & delete it.
     *
     * @since 2.0.0
     * @access public
     * @param string $transientKey Security token.
     */
    public function removeGroupIcon($id, $transientKey = '', $target = 'groupicon') {
        $session = Gdn::session();
        $group = $this->GroupModel->GetID($id);
        if ($session->validateTransientKey($transientKey) && $this->GroupModel->CheckPermission('Edit', $group)) {
            $icon = val('Icon', $group);
            $this->GroupModel->setField($id, 'Icon', null);
            $this->deleteGroupIcons($icon);
        }
        redirectUrl(GroupUrl($group, $target));
    }

    /**
     * Test whether a path is a relative path to the proper uploads directory.
     *
     * @param string $icon The path to the icon image to test.
     * @return bool Whether the icon has been uploaded from the dashboard.
     */
    private function isUploadedGroupIcon($icon) {
        return (strpos($icon, self::GROUP_ICON_FOLDER.'/') !== false);
    }

    /**
     * Deletes uploaded icons.
     *
     * @param string $icon The icon to delete.
     */
    private function deleteGroupIcons($icon = '') {
        if ($icon && $this->isUploadedGroupIcon($icon)) {
            $upload = new Gdn_Upload();
            $upload->delete(self::GROUP_ICON_FOLDER.'/'.basename($icon));
            $upload->delete(self::GROUP_ICON_FOLDER.'/'.basename(changeBasename($icon, 'p%s')));
        }
    }

   public function Discussions($ID, $Page = FALSE) {
      Gdn_Theme::Section('DiscussionList');

      $Group = $this->GroupModel->GetID($ID);
      if (!$Group)
         throw NotFoundException('Group');

      $this->SetData('Group', $Group);
      $this->GroupModel->OverridePermissions($Group);

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
      $this->Data['_properties']['newdiscussionmodule'] = array('CssClass' => 'Button Action Primary', 'QueryString' => $NewDiscussionModule->QueryString);

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
         $this->AddJsFile('discussions.js', 'vanilla');
         $this->Head->AddRss($this->SelfUrl.'/feed.rss', $this->Head->Title());
      }

      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
      $this->EventArguments['PagerType'] = 'Pager';
      $this->FireEvent('BeforeBuildPager');
      $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $Offset,
         $Limit,
         $Group['CountDiscussions'],
         'group/discussions/'.GroupSlug($Group).'/%1$s'
      );
      if (!$this->Data('_PagerUrl')) {
         $this->SetData('_PagerUrl', 'group/discussions/'.GroupSlug($Group).'/{Page}');
      }
      $this->SetData('_Page', $Page);
      $this->SetData('_Limit', $Limit);
      $this->FireEvent('AfterBuildPager');

      $this->SetData("CountDiscussions", $Group['CountDiscussions']);

      $header = new GroupHeaderModule($Group);
      $this->addModule($header);
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
   public function Members($ID, $Page = FALSE, $Filter = '') {
      Gdn_Theme::Section('Group');
      Gdn_Theme::Section('Members');

      $Group = $this->GroupModel->GetID($ID);
      if (!$Group)
         throw NotFoundException('Group');

      // Check if this person is a member of the group or a moderator
      $viewGroupEvents = GroupPermission('View', $Group);
      if (!$viewGroupEvents) {
         throw PermissionException();
      }

      $this->SetData('Group', $Group);
      $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
      $this->AddBreadcrumb(T('GroupMembers', 'Members'));

      list($Offset, $Limit) = OffsetLimit($Page, $this->GroupModel->MemberPageSize);
      if ($Offset === 0) {
         $Filter = '';
      }

      // Get Leaders
      if (in_array($Filter, array('', 'leaders'))) {
         $Users = $this->GroupModel->GetMembers($Group['GroupID'], array('Role' => 'Leader'), $Limit, $Offset);
         $this->SetData('Leaders', $Users);
      }

      // Get Members
      if (in_array($Filter, array('', 'members'))) {
         $Users = $this->GroupModel->GetMembers($Group['GroupID'], array('Role' => 'Member'), $Limit, $Offset);
         $this->SetData('Members', $Users);
      }

      $this->Data['_properties']['newdiscussionmodule'] = array('CssClass' => 'Button Action Primary', 'QueryString' => 'groupid='.$Group['GroupID']);
      $this->SetData('Filter', $Filter);
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
