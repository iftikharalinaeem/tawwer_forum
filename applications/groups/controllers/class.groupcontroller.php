<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Class GroupController
 */
class GroupController extends Gdn_Controller {

    /** The path to the group icons folder. */
    const GROUP_ICON_FOLDER = 'groups/icons';

    /** @var array  */
    public $Uses = array('GroupModel', 'EventModel');

    /** @var GroupModel */
    public $GroupModel;

    /** @var bool Should the discussions have their options available. */
    public $ShowOptions = true;

    /** @var int */
    public $HomepageDiscussionCount = 10;

    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @access public
     */
    public function initialize() {
        // Set up head
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.livequery.js');
        $this->addJsFile('jquery-ui.js');
        $this->addJsFile('jquery.tokeninput.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('global.js');
        $this->addJsFile('group.js');
        $this->addJsFile('event.js');
        $this->addCssFile('vanillicon.css', 'static');
        $this->addCssFile('style.css');

        $this->addBreadcrumb(t('Groups'), '/groups');
        $this->CountCommentsPerPage = c('Vanilla.Comments.PerPage', 30);

        parent::initialize();
    }

    /**
     * The homepage for a group.
     *
     * @param string $Group Url friendly code for the group in the form ID-url-friendly-name
     */
    public function index($ID) {
        Gdn_Theme::section('Group');
        $this->allowJSONP(true);

        $Group = $this->GroupModel->getID($ID);

        $this->EventArguments['Group'] = &$Group;
        $this->fireEvent('GroupLoaded');

        if (!$Group) {
            throw NotFoundException('Group');
        }

        $GroupID = $Group['GroupID'];

        // Force the canonical url.
        if (rawurlencode($ID) != groupSlug($Group)) {
            redirectTo(GroupUrl($Group), 301, false);
        }
        $this->canonicalUrl(url(groupUrl($Group), '//'));

        $this->setData('Group', $Group);
        $this->addBreadcrumb($Group['Name'], groupUrl($Group));

        $this->GroupModel->overridePermissions($Group);

        // Get Discussions
        $discussionModel = new DiscussionModel();
        $discussions = $discussionModel->getWhereRecent(['d.GroupID' => $GroupID, 'd.Announce' => 0], $this->HomepageDiscussionCount);
        $this->setData('Discussions', $discussions);

        $discussions = $discussionModel->getAnnouncements(['d.GroupID' => $GroupID], 0, 10);
        $this->setData('Announcements', $discussions);

        // Get Events
        $MaxEvents = c('Groups.Events.MaxList', 5);
        $EventModel = new EventModel();
        $Events = $EventModel
            ->getWhere(['GroupID' => $GroupID, 'DateStarts >=' => gmdate('Y-m-d H:i:s')], 'DateStarts', 'asc', $MaxEvents)
            ->resultArray();

        $this->EventArguments['Events'] = &$Events;
        $this->fireEvent('GroupEventsLoaded');

        $this->setData('Events', $Events);

        // Get applicants.
        $Applicants = $this->GroupModel->getApplicants($GroupID, ['Type' => ['Application', 'Invitation']], 20);
        $this->setData('Applicants', $Applicants);

        // Get Leaders
        $Users = $this->GroupModel->getMembers($GroupID, ['Role' => 'Leader'], 10);
        foreach ($Users as &$User) {
            if ($User['UserID'] == $Group['InsertUserID']) {
                $User['Role'] = 'Owner';
            }
        }
        $this->setData('Leaders', $Users);

        // Get Members
        $Users = $this->GroupModel->getMembers($GroupID, ['Role' => 'Member'], 30);
        $this->setData('Members', $Users);

        $this->title(htmlspecialchars($Group['Name']));
        $this->description(Gdn_Format::plainText($Group['Description'], $Group['Format']));
        if ($Group['Icon']) {
            $this->image(Gdn_Upload::url($Group['Icon']));
        }

        $this->Data['_properties']['newdiscussionmodule'] =['CssClass' => 'Button Action Primary', 'QueryString' => 'groupid='.$GroupID];

        require_once $this->fetchViewLocation('event_functions', 'event');
        require_once $this->fetchViewLocation('group_functions');

        $this->CssClass .= ' NoPanel';
        $this->addJsFile('discussions.js', 'vanilla');
        $this->render('Group');
    }

    /**
     *
     *
     * @throws Exception
     */
    public function add() {
        $this->title(sprintf(t('New %s'), t('Group')));

        // Check the max groups.
        if ($this->GroupModel->MaxUserGroups > 0 && Gdn::session()->isValid()) {
            $this->setData('MaxUserGroups', $this->GroupModel->MaxUserGroups);
            $this->setData('CountUserGroups', $this->GroupModel->getUserCount(Gdn::session()->UserID));
            $CountRemaining = max(0, $this->data('MaxUserGroups') - $this->data('CountUserGroups'));
            $this->setData('CountRemainingGroups', $CountRemaining);

            if ($CountRemaining <= 0) {
                $this->Form = new Gdn_Form();
                $this->Form->addError("You've already created the maximum number of groups.");
                $this->render('AddEditError');
            }
        }

        return $this->addEdit();
    }

    /**
     *
     *
     * @param $Group
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function announcement($Group) {
        $Group = $this->GroupModel->getID($Group);
        if (!$Group) {
            throw NotFoundException('Group');
        }

        // Check leader permission.
        if (!$this->GroupModel->checkPermission('Moderate', $Group)) {
            throw ForbiddenException('@'.$this->GroupModel->checkPermission('Moderate.Reason', $Group));
        }

        $this->setData('Group', $Group);

        $Form = new Gdn_Form();
        $this->Form = $Form;

        if ($Form->authenticatedPostBack()) {
            // Let's save the announcement.
            $Form->setFormValue('CategoryID', $Group['CategoryID']);
            $Form->setFormValue('GroupID', $Group['GroupID']);
            $Form->setFormValue('Announce', 2); // Announce within group.

            $Model = new DiscussionModel();
            $Form->setModel($Model);

            if ($Form->save()) {
                $this->RedirectUrl = GroupUrl($Group);
            } else {
                $Form->setValidationResults($Model->validationResults());
            }
        }

        $this->addBreadcrumb($Group['Name'], groupUrl($Group));
        $this->title(t('New Announcement'));
        $this->render();
    }

    /**
     *
     *
     * @param $Group
     * @param $ID
     * @param string $Value
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function approve($Group, $ID, $Value = 'approved') {
        $Group = $this->GroupModel->getID($Group);
        if (!$Group)
            throw NotFoundException('Group');

        // Check leader permission.
        if (!$this->GroupModel->checkPermission('Leader', $Group)) {
            throw ForbiddenException('@'.$this->GroupModel->checkPermission('Leader.Reason', $Group));
        }

        $Value = ucfirst($Value);

        $this->GroupModel->joinApprove(array(
            'GroupApplicantID' => $ID,
            'Type' => $Value
        ));

        $this->jsonTarget("#GroupApplicant_$ID", "", 'SlideUp');
        $this->informMessage(t('Applicant '.$Value));

        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     *
     *
     * @param $ID
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function invite($ID) {
        $Group = $this->GroupModel->getID($ID);
        if (!$Group) {
            throw NotFoundException('Group');
        }

        // Check invite permission.
        if (!$this->GroupModel->checkPermission('Leader', $Group)) {
            throw ForbiddenException('@'.$this->GroupModel->checkPermission('Join.Reason', $Group));
        }

        $this->title(t('Invite'));

        $Form = new Gdn_Form();
        $this->Form = $Form;

        if ($Form->authenticatedPostBack()) {
            // If the user posted back then we are going to add them.
            $Data = $Form->formValues();
            $Data['GroupID'] = $Group['GroupID'];
            $Recipients = explode(',', $Data['Recipients']);
            $UserIDs = [];
            $memberIds = $this->GroupModel->getMemberIds(val('GroupID', $Group));
            $applicantIds = $this->GroupModel->getApplicantIds(val('GroupID', $Group), ['Type' => ['Application', 'Invitation']]);
            foreach ($Recipients as $Recipient) {
                $userId = GetValue('UserID', Gdn::userModel()->getByUsername($Recipient));
                if (in_array($userId, $memberIds)) {
                    $this->informMessage(t(sprintf("%s is already a member.", $Recipient)));
                } elseif (in_array($userId, $applicantIds)) {
                  $this->informMessage(t(sprintf("%s is already an applicant.", $Recipient)));
                } else {
                    $UserIDs[] = $userId;
                }
            }
            if ($UserIDs) {
                $Data['UserID'] = $UserIDs;
                $Saved = $this->GroupModel->invite($Data);
                if ($Saved) {
                    $this->informMessage(t('Invitation sent.'));
                    $Form->setValidationResults($this->GroupModel->validationResults());
                }
            }
        }

        $this->setData('Group', $Group);
        $this->addBreadcrumb($Group['Name'], GroupUrl($Group));
        $this->render();
    }

    /**
     *
     *
     * @param $ID
     * @throws Exception
     */
    public function inviteAccept($ID) {
        $Group = $this->GroupModel->getID($ID);
        if (!$Group) {
            throw NotFoundException('Group');
        }

        if (!$this->Request->isPostBack()) {
            throw ForbiddenException('GET');
        }

        $Result = $this->GroupModel->joinInvite($Group['GroupID'], Gdn::session()->UserID, true);
        $this->setData('Result', $Result);
        $this->RedirectUrl = GroupUrl($Group);
        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     *
     *
     * @param $ID
     * @throws Exception
     */
    public function inviteDecline($ID) {
        $Group = $this->GroupModel->getID($ID);
        if (!$Group) {
            throw NotFoundException('Group');
        }
        if (!$this->Request->IsPostBack()) {
            throw ForbiddenException('GET');
        }
        $Result = $this->GroupModel->joinInvite($Group['GroupID'], Gdn::session()->UserID, false);
        $this->SetData('Result', $Result);

        $this->jsonTarget('.GroupUserHeaderModule', '', 'SlideUp');
        $this->RedirectUrl = GroupUrl($Group);
        $this->informMessage(t('Invitation declined.'));
        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     *
     *
     * @param $ID
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function join($ID) {
        $Group = $this->GroupModel->getID($ID);
        if (!$Group) {
            throw NotFoundException('Group');
        }

        // Check join permission.
        if (!$this->GroupModel->checkPermission('Join', $Group)) {
            throw ForbiddenException('@'.$this->GroupModel->checkPermission('Join.Reason', $Group));
        }

        $this->setData('Title', sprintf(T('Join %s'), htmlspecialchars($Group['Name'])));

        $Form = new Gdn_Form();
        $this->Form = $Form;

        if ($Form->authenticatedPostBack()) {
            // If the user posted back then we are going to add them.
            $Data = $Form->formValues();
            $Data['UserID'] = Gdn::session()->UserID;
            $Data['GroupID'] = $Group['GroupID'];
            $Saved = $this->GroupModel->join($Data);
            $Form->setValidationResults($this->GroupModel->validationResults());

            if ($Saved) {
                $this->RedirectUrl = url(groupUrl($Group));
            }
        }

        $this->setData('Group', $Group);
        $this->addBreadcrumb($Group['Name'], groupUrl($Group));
        $this->render();
    }

    /**
     *
     *
     * @param $ID
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function leave($ID) {
        $Group = $this->GroupModel->getID($ID);
        if (!$Group) {
            throw NotFoundException('Group');
        }

        // Check join permission.
        if (!$this->GroupModel->checkPermission('Leave', $Group)) {
            throw ForbiddenException('@'.$this->GroupModel->checkPermission('Leave.Reason', $Group));
        }

        $this->setData('Title', sprintf(t('Leave %s'), htmlspecialchars($Group['Name'])));

        $Form = new Gdn_Form();
        $this->Form = $Form;

        if ($Form->authenticatedPostBack()) {
            $Data = [
                'UserID' => Gdn::session()->UserID,
                'GroupID' => $Group['GroupID']
            ];
            $this->GroupModel->leave($Data);
            $this->jsonTarget('', '', 'Refresh');
        }

        $this->setData('Group', $Group);
        $this->addBreadcrumb($Group['Name'], groupUrl($Group));
        $this->render();
    }

    /**
     *
     *
     * @param $ID
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function delete($ID) {
        $Group = $this->GroupModel->getID($ID);
        if (!$Group) {
            throw NotFoundException('Group');
        }
        $this->setData('Group', $Group);

        if (!groupPermission('Leader')) {
            throw ForbiddenException('@'.groupPermission('Edit.Reason'));
        }

        $Form = new Gdn_Form();
        $this->Form = $Form;

        if ($this->Form->authenticatedPostBack()) {
            $GroupModel = new GroupModel();
            $GroupDeleted = $GroupModel->delete(array('GroupID' => $Group['GroupID']));

            $EventModel = new EventModel();
            $EventModel->delete(array('GroupID' => $Group['GroupID']));

            if ($GroupDeleted) {
                $this->informMessage(formatString(t('<b>{Name}</b> deleted.'), $Group));
                $this->RedirectUrl = url('/groups');
            } else {
                $this->informMessage(t('Failed to delete group.'));
            }
        }

        $this->setData('Title', t('Delete Group'));
        $this->render();
    }

    /**
     * Save an image from a field and delete any old image that's been uploaded.
     *
     * This method is a canditate for putting on the form object.
     *
     * @param Gdn_Form $Form
     * @param string $Field The name of the field. The image will be uploaded with the _New extension while the current image will be just the field name.
     * @param array $Options
     */
    protected static function saveImage($Form, $Field, $Options = array()) {
        $Upload = new Gdn_UploadImage();

        if (!valr("{$Field}_New.name", $_FILES)) {
            trace("$Field not uploaded, returning.");
            return false;
        }

        // First make sure the file is valid.
        try {
            $TmpName = $Upload->validateUpload($Field.'_New', true);
            if (!$TmpName) {
                return false; // no file uploaded.
            }
        } catch (Exception $Ex) {
            $Form->AddError($Ex);
            return false;
        }

        // Get the file extension of the file.
        $Ext = val('OutputType', $Options, trim($Upload->getUploadedFileExtension(), '.'));
        if ($Ext == 'jpeg') {
            $Ext = 'jpg';
        }
        trace($Ext, 'Ext');

        // The file is valid so let's come up with its new name.
        if (isset($Options['Name'])) {
            $Name = $Options['Name'];
        } elseif (isset($Options['Prefix'])) {
            $Name = $Options['Prefix'].md5(microtime()).'.'.$Ext;
        } else {
            $Name = md5(microtime()).'.'.$Ext;
        }

        // We need to parse out the size.
        $Size = val('Size', $Options);
        if ($Size) {
            if (is_numeric($Size)) {
                touchValue('Width', $Options, $Size);
                touchValue('Height', $Options, $Size);
            } elseif (preg_match('`(\d+)x(\d+)`i', $Size, $M)) {
                touchValue('Width', $Options, $M[1]);
                touchValue('Height', $Options, $M[2]);
            }
        }

        trace($Options, "Saving image $Name.");
        try {
            $Parsed = $Upload->saveImageAs($TmpName, $Name, val('Height', $Options, ''), val('Width', $Options, ''), $Options);
            trace($Parsed, 'Saved Image');

            $Current = $Form->getFormValue($Field);
            if ($Current && val('DeleteOriginal', $Options, true)) {
                // Delete the current image.
                trace("Deleting original image: $Current.");
                if ($Current) {
                    $Upload->delete($Current);
                }
            }
            // Set the current value.
            $Form->setFormValue($Field, $Parsed['SaveName']);
        } catch (Exception $Ex) {
            $Form->addError($Ex);
        }
    }

    /**
     * Saves the group icon /uploads in two formats:
     *    The thumbnail-sized image, which is constrained and cropped according to Groups.IconSize.
     *    p* : The profile-sized image, which is constrained by Garden.Profile.MaxWidth and Garden.Profile.MaxHeight.
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

            // Save the thumbnail size image.
            $thumbnailSize = c('Groups.IconSize', 140);
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

        return $parts['SaveName'];
    }

    /**
     *
     *
     * @param bool $ID
     * @throws Exception
     * @throws Gdn_UserException
     */
    protected function addEdit($ID = false) {
        $Form = new Gdn_Form();
        $Form->setModel($this->GroupModel);
        Gdn_Theme::section('Post');

        if ($ID) {
            $Group = $this->GroupModel->getID($ID);

            if (!$Group) {
                throw NotFoundException('Group');
            }

            // Make sure the user can edit this group.
            if (!$this->GroupModel->checkPermission('Edit', $Group)) {
                throw ForbiddenException('@'.$this->GroupModel->checkPermission('Edit.Reason', $Group));
            }

            $this->setData('Group', $Group);
            $this->addBreadcrumb($Group['Name'], GroupUrl($Group));
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
        $Categories = array_filter(CategoryModel::categories(), function($Row) { return $Row['AllowGroups']; });
        $Categories = array_column($Categories, 'Name', 'CategoryID');
        $this->setData('Categories', $Categories);

        if ($Form->authenticatedPostBack()) {
            // We need to save the images before saving to the database.
            self::saveImage($Form, 'Banner', array('Prefix' => 'groups/banners/banner_', 'Size' => C('Groups.BannerSize', '1000x250'), 'Crop' => true, 'OutputType' => 'jpeg'));

            if ($tmpIcon = $upload->validateUpload('Icon_New', false)) {
                // New upload
                $thumbOptions = array('Crop' => true, 'SaveGif' => c('Garden.Thumbnail.SaveGif'));
                $newIcon = $this->saveIcons($tmpIcon, $thumbOptions);
                $Form->setFormValue('Icon', $newIcon);
            } else if ($icon && $crop && $crop->isCropped()) {
                // New thumbnail
                $tmpIcon = $source;
                $thumbOptions = array('Crop' => true,
                    'SourceX' => $crop->getCropXValue(),
                    'SourceY' => $crop->getCropYValue(),
                    'SourceWidth' => $crop->getCropWidth(),
                    'SourceHeight' => $crop->getCropHeight());
                $newIcon = $this->saveIcons($tmpIcon, $thumbOptions);
                $Form->setFormValue('Icon', $newIcon);
            }
            if ($Form->errorCount() == 0) {
                if ($newIcon) {
                    $Form->setFormValue('Icon_New', $newIcon);
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
            if ($ID) {
                $Form->setFormValue('GroupID', $this->data('Group.GroupID'));
            }

            try {
                $GroupID = $Form->save();
            } catch (Exception $Ex) {
                $Form->addError($Ex);
            }
            if ($GroupID) {
                $Group = $this->GroupModel->getID($GroupID);
                redirectTo(GroupUrl($Group), 302, false);
            } else {
                trace($Form->formValues());
            }
        } else {
            if ($ID) {
                // Load the group.
                $Form->setData($Group);
            } else {
                // Set some default settings.
                $Form->setValue('Registration', 'Public');
                $Form->setValue('Visibility', 'Public');

                if (count($Categories == 1)) {
                    $Form->setValue('CategoryID', array_pop(array_keys($Categories)));
                }
            }
        }
        $this->Form = $Form;
        $this->CssClass .= ' NoPanel NarrowForm';
        $this->render('AddEdit');
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
            $group = $this->GroupModel->getID($id);
            if (!$group) {
                throw NotFoundException('Group');
            }

            // Make sure the user can edit this group.
            if (!$this->GroupModel->checkPermission('Edit', $group)) {
                throw ForbiddenException('@' . $this->GroupModel->checkPermission('Edit.Reason', $group));
            }
            $this->setData('Group', $group);
            $this->addBreadcrumb($group['Name'], GroupUrl($group));
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

        if ($form->authenticatedPostBack()) {
            $target = null; // redirect to group home
            $form->setData($group);
            $upload = new Gdn_UploadImage();
            $newIcon = false;
            if ($tmpIcon = $upload->validateUpload('Icon', false)) {
                 // New upload
                 $thumbOptions = array('Crop' => true, 'SaveGif' => c('Garden.Thumbnail.SaveGif'));
                 $newIcon = $this->saveIcons($tmpIcon, $thumbOptions);
                 $form->setFormValue('Icon', $newIcon);
                 $target = 'groupicon'; // redirect to groupicon page so user can set thumbnail
            } elseif ($icon && $crop && $crop->isCropped()) {
                 // New thumbnail
                 $tmpIcon = $source;
                 $thumbOptions = array('Crop' => true,
                      'SourceX' => $crop->getCropXValue(),
                      'SourceY' => $crop->getCropYValue(),
                      'SourceWidth' => $crop->getCropWidth(),
                      'SourceHeight' => $crop->getCropHeight());
                 $newIcon = $this->saveIcons($tmpIcon, $thumbOptions);
                 $form->setFormValue('Icon', $newIcon);
            }

            if ($form->errorCount() == 0 && $newIcon) {
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
                redirectTo(groupUrl($group, $target), 302, false);
            }
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
        $group = $this->GroupModel->getID($id);
        if ($session->validateTransientKey($transientKey) && $this->GroupModel->checkPermission('Edit', $group)) {
            $icon = val('Icon', $group);
            $this->GroupModel->setField($id, 'Icon', null);
            $this->deleteGroupIcons($icon);
        }
        redirectTo(groupUrl($group, $target), 302, false);
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

    /**
     *
     *
     * @param $ID
     * @param bool $Page
     * @throws Exception
     */
    public function discussions($ID, $Page = false) {
        Gdn_Theme::section('DiscussionList');

        $Group = $this->GroupModel->getID($ID);
        if (!$Group) {
            throw NotFoundException('Group');
        }

        $this->setData('Group', $Group);
        $this->GroupModel->overridePermissions($Group);

        list($Offset, $Limit) = offsetLimit($Page, c('Vanilla.Discussions.PerPage', 30));
        $DiscussionModel = new DiscussionModel();
        $this->DiscussionData = $this->setData('Discussions', $DiscussionModel->getWhereRecent(array('GroupID' => $Group['GroupID']), $Limit, $Offset));
        $this->CountCommentsPerPage = c('Vanilla.Comments.PerPage', 30);
        $this->setData('_ShowCategoryLink', false);

        // Add modules
        $NewDiscussionModule = new NewDiscussionModule();
        $NewDiscussionModule->QueryString = 'groupid='.$Group['GroupID'];
        $this->addModule($NewDiscussionModule);
        $this->addModule('DiscussionFilterModule');
        $this->addModule('CategoriesModule');
        $this->addModule('BookmarkedModule');

        $this->setData('_NewDiscussionProperties', array('CssClass' => 'Button Action Primary', 'QueryString' => $NewDiscussionModule->QueryString));
        $this->Data['_properties']['newdiscussionmodule'] = array('CssClass' => 'Button Action Primary', 'QueryString' => $NewDiscussionModule->QueryString);

        $this->addBreadcrumb($Group['Name'], groupUrl($Group));
        $this->addBreadcrumb(t('Discussions'));

        $Layout = c('Vanilla.Discussions.Layout');
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
            $this->addJsFile('discussions.js', 'vanilla');
            $this->Head->addRss($this->SelfUrl.'/feed.rss', $this->Head->title());
        }

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        $this->Pager = $PagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $Offset,
            $Limit,
            $Group['CountDiscussions'],
            'group/discussions/'.groupSlug($Group).'/%1$s'
        );
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'group/discussions/'.groupSlug($Group).'/{Page}');
        }
        $this->setData('_Page', $Page);
        $this->setData('_Limit', $Limit);
        $this->fireEvent('AfterBuildPager');

        $this->setData("CountDiscussions", $Group['CountDiscussions']);

        $header = new GroupHeaderModule($Group);
        $this->addModule($header);
        $this->render($this->View, 'Discussions', 'Vanilla');
    }

    /**
     *
     *
     * @param $ID
     * @throws Exception
     */
    public function edit($ID) {
        $this->title(sprintf(t('Edit %s'), t('Group')));
        return $this->addEdit($ID);
    }

    /**
     * The member list of a group.
     *
     * @param string $ID
     * @param string $Page
     * @param string $Filter
     * @param string $memberFilter
     */
    public function members($ID, $Page = false, $Filter = '', $memberFilter = '') {
        Gdn_Theme::section('Group');
        Gdn_Theme::section('Members');

        $Group = $this->GroupModel->getID($ID);
        if (!$Group) {
            throw NotFoundException('Group');
        }

        // Check if this person is a member of the group or a moderator
        $viewGroupEvents = GroupPermission('View', $Group);
        if (!$viewGroupEvents) {
            throw PermissionException();
        }

        $this->Form = new Gdn_Form();
        $this->setData('Group', $Group);
        $this->addBreadcrumb($Group['Name'], groupUrl($Group));
        $this->addBreadcrumb(t('GroupMembers', 'Members'), groupUrl($Group, 'members'));

        list($Offset, $Limit) = offsetLimit($Page, $this->GroupModel->MemberPageSize);

        // Don't show the leaders module when filtering.
        if ($memberFilter) {
            $Filter = 'members';
        } elseif ($Offset === 0) {
            $Filter = '';
        }

        $this->setData('_Limit', $Limit);
        $this->setData('_Offset', $Limit);

        // Get Leaders
        if (in_array($Filter, array('', 'leaders'))) {
            $Users = $this->GroupModel->getMembers($Group['GroupID'], ['Role' => 'Leader'], $Limit, $Offset);
            $this->setData('Leaders', $Users);
        }

        // Get Members
        if (in_array($Filter, array('', 'members'))) {
            // Filter only by name (not by roles) when filtering
            if ($memberFilter) {
                $where = ['u.Name like' => $memberFilter.'%'];
            } else {
                $where = ['Role' => 'Member'];
            }
            $Users = $this->GroupModel->getMembers($Group['GroupID'], $where, $Limit, $Offset);
            $this->setData('Members', $Users);
        }

        $this->Data['_properties']['newdiscussionmodule'] = array('CssClass' => 'Button Action Primary', 'QueryString' => 'groupid='.$Group['GroupID']);
        $this->setData('Filter', $Filter);
        $this->title(t('Members').' - '.htmlspecialchars($Group['Name']));
        require_once $this->fetchViewLocation('group_functions');
        $this->CssClass .= ' NoPanel';
        $this->render('Members');
    }

    /**
     *
     *
     * @param $ID
     * @param $UserID
     * @param $Role
     * @throws Exception
     */
    public function setRole($ID, $UserID, $Role) {
        $Group = $this->GroupModel->getID($ID);
        if (!$Group) {
            throw NotFoundException('Group');
        }

        $User = Gdn::userModel()->getID($UserID, DATASET_TYPE_ARRAY);
        if (!$User) {
            throw NotFoundException('User');
        }

        if (!$this->GroupModel->checkPermission('Edit', $Group)) {
            throw ForbiddenException('@'.$this->GroupModel->checkPermission('Edit.Reason', $Group));
        }

        $GroupID = $Group['GroupID'];

        $Member = $this->GroupModel->getMembers($Group['GroupID'], array('UserID' => $UserID));
        $Member = array_pop($Member);
        if (!$Member) {
            throw NotFoundException('Member');
        }

        // You can't demote the user that started the group.
        if ($UserID == $Group['InsertUserID']) {
            throw ForbiddenException('@'.t("The user that started the group has to be a leader."));
        }

        if ($this->Request->isPostBack()) {
            $Role = ucfirst($Role);
            $this->GroupModel->setRole($GroupID, $UserID, $Role);

            $this->informMessage(sprintf(t('%s is now a %s.'), htmlspecialchars($User['Name']), $Role));
        }

        $this->setData('Group', $Group);
        $this->setData('User', $User);
        $this->title(t('Group Role'));
        $this->render();
    }

    /**
     *
     *
     * @param $ID
     * @param $UserID
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function removeMember($ID, $UserID) {
        $Group = $this->GroupModel->getID($ID);
        if (!$Group)
            throw NotFoundException('Group');

        $User = Gdn::userModel()->getID($UserID, DATASET_TYPE_ARRAY);
        if (!$User) {
            throw NotFoundException('User');
        }

        if ($UserID == Gdn::session()->UserID) {
            Gdn::dispatcher()->dispatch(groupUrl($Group, 'leave'));
            return;
        }

        if (!$this->GroupModel->checkPermission('Moderate', $Group)) {
            throw ForbiddenException('@'.$this->GroupModel->checkPermission('Moderate.Reason', $Group));
        }

        $GroupID = $Group['GroupID'];

        $Member = $this->GroupModel->getMembers($Group['GroupID'], array('UserID' => $UserID));
        $Member = array_pop($Member);
        if (!$Member) {
            throw NotFoundException('Member');
        }

        // You can't remove the user that started the group.
        if ($UserID == $Group['InsertUserID']) {
            throw ForbiddenException('@'.t("You can't remove the creator of the group."));
        }

        // Only users that can edit the group can remove leaders.
        if ($Member['Role'] == 'Leader' && !groupPermission('Edit')) {
            throw forbiddenException('@'.t("You can't remove another leader of the group."));
        }

        $Form = new Gdn_Form();
        $this->Form = $Form;

        if ($Form->authenticatedPostBack()) {
            $this->GroupModel->removeMember($GroupID, $UserID, $this->Form->getFormValue('Type'));
            $this->jsonTarget("#Member_$UserID", null, "Remove");
        } else {
            $Form->setValue('Type', 'Removed');
        }

        $this->setData('Group', $Group);
        $this->setData('User', $User);
        $this->title(t('Remove Member'));
        $this->render();
    }
}
