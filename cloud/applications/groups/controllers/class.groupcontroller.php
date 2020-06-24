<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

use Vanilla\Forum\Navigation\GroupRecordType;

/**
 * Class GroupController
 */
class GroupController extends Gdn_Controller {

    /** The path to the group icons folder. */
    const GROUP_ICON_FOLDER = 'groups/icons';

    /** @var array  */
    public $Uses = ['GroupModel', 'EventModel'];

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
        $this->addJsFile('jquery-ui.min.js');
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
     * Verify a user can access a group.
     *
     * @param array $group A group row.
     * @param bool $throw Throw an exception on access or not-found errors?
     * @throws Exception If $throw is true and an access or not-found error is encountered.
     * @return void
     */
    private function verifyAccess($group) {
        if (!$group || !$this->GroupModel->checkPermission('Access', $group)) {
            throw notFoundException('Group');
        }
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

        $this->verifyAccess($Group);

        $GroupID = $Group['GroupID'];

        // Force the canonical url.
        if (rawurlencode($ID) != groupSlug($Group)) {
            redirectTo(groupUrl($Group), 301);
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
        $maxEvents = c('Groups.Events.MaxList', 5);
        $EventModel = new EventModel();
        $upcomingRange = c('Groups.Events.UpcomingRange', '+365 days');
        $Events = $EventModel->getUpcoming(
            $upcomingRange,
            [
                'ParentRecordType' => GroupRecordType::TYPE,
                'ParentRecordID' => $GroupID
            ],
            null,
            $maxEvents
        );

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
        $this->setData('Category.CategoryID', $Group['CategoryID']);
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
            $countRemaining = max(0, $this->data('MaxUserGroups') - $this->data('CountUserGroups'));
            $this->setData('CountRemainingGroups', $countRemaining);

            if ($countRemaining <= 0) {
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
     * @param $group
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function announcement($group) {
        $group = $this->GroupModel->getID($group);
        $this->verifyAccess($group);

        // Check leader permission.
        if (!$this->GroupModel->checkPermission('Moderate', $group)) {
            throw forbiddenException('@'.$this->GroupModel->checkPermission('Moderate.Reason', $group));
        }

        $this->setData('Group', $group);

        $form = new Gdn_Form();
        $this->Form = $form;

        if ($form->authenticatedPostBack()) {
            // Let's save the announcement.
            $form->setFormValue('CategoryID', $group['CategoryID']);
            $form->setFormValue('GroupID', $group['GroupID']);
            $form->setFormValue('Announce', 2); // Announce within group.

            $model = new DiscussionModel();
            $form->setModel($model);

            if ($form->save()) {
                $this->setRedirectTo(groupUrl($group));
            } else {
                $form->setValidationResults($model->validationResults());
            }
        }

        $this->addBreadcrumb($group['Name'], groupUrl($group));
        $this->title(t('New Announcement'));
        $this->render();
    }

    /**
     *
     *
     * @param $group
     * @param $id ApplicantID
     * @param string $value
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function approve($group, $id, $value = 'approved') {
        $form = new Gdn_Form();
        if (!$form->authenticatedPostBack()) {
             throw new Gdn_UserException(t('Invalid CSRF token.', 'Invalid CSRF token. Please try again.'), 403);
        }

        $group = $this->GroupModel->getID($group);
        $this->verifyAccess($group);

        // Check leader permission.
        if (!$this->GroupModel->checkPermission('Leader', $group)) {
            throw forbiddenException('@'.$this->GroupModel->checkPermission('Leader.Reason', $group));
        }

        $value = ucfirst($value);

        $applicants = $this->GroupModel->getApplicants($group['GroupID'], ['GroupApplicantID' => $id]);
        if (!$applicants) {
            throw notFoundException('Applicant not found');
        }

        $userID = reset($applicants)['UserID'];

        if ($this->GroupModel->processApplicant($group['GroupID'], $userID, $value === 'Approved')) {
            $this->jsonTarget("#GroupApplicant_$id", "", 'SlideUp');
            $this->informMessage(t('Applicant '.$value));
        } else {
            $this->informMessage(t('An error occurred.'));
        }

        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     *
     *
     * @param $iD
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function invite($iD) {
        $group = $this->GroupModel->getID($iD);
        $this->verifyAccess($group);

        // Check invite permission.
        if (!$this->GroupModel->checkPermission('Leader', $group)) {
            throw forbiddenException('@'.$this->GroupModel->checkPermission('Join.Reason', $group));
        }

        $this->title(t('Invite'));

        $form = new Gdn_Form();
        $this->Form = $form;

        if ($form->authenticatedPostBack()) {
            // If the user posted back then we are going to add them.
            $data = $form->formValues();
            $data['GroupID'] = $group['GroupID'];
            $recipients = explode(',', $data['Recipients']);
            $userIDs = [];
            $memberIds = $this->GroupModel->getMemberIds(val('GroupID', $group));
            $applicantIds = $this->GroupModel->getApplicantIds(val('GroupID', $group), ['Type' => ['Application', 'Invitation']]);
            foreach ($recipients as $recipient) {
                $userId = getValue('UserID', Gdn::userModel()->getByUsername($recipient));
                if (in_array($userId, $memberIds)) {
                    $this->informMessage(t(sprintf("%s is already a member.", htmlspecialchars($recipient))));
                } elseif (in_array($userId, $applicantIds)) {
                  $this->informMessage(t(sprintf("%s is already an applicant.", htmlspecialchars($recipient))));
                } else {
                    $userIDs[] = $userId;
                }
            }
            if ($userIDs) {
                $data['UserID'] = $userIDs;
                $saved = $this->GroupModel->invite($data);
                if ($saved) {
                    $this->informMessage(t('Invitation sent.'));
                    $form->setValidationResults($this->GroupModel->validationResults());
                }
            }
        }

        $this->setData('Group', $group);
        $this->addBreadcrumb($group['Name'], groupUrl($group));
        $this->render();
    }

    /**
     *
     *
     * @param $iD
     * @throws Exception
     */
    public function inviteAccept($iD) {
        $group = $this->GroupModel->getID($iD);
        $this->verifyAccess($group);

        if (!$this->Request->isPostBack()) {
            throw forbiddenException('GET');
        }

        $result = $this->GroupModel->joinInvite($group['GroupID'], Gdn::session()->UserID, true);
        $this->setData('Result', $result);
        $this->setRedirectTo(groupUrl($group));
        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     *
     *
     * @param $iD
     * @throws Exception
     */
    public function inviteDecline($iD, $refresh = false) {
        $group = $this->GroupModel->getID($iD);
        $this->verifyAccess($group);

        if (!$this->Request->isPostBack()) {
            throw forbiddenException('GET');
        }
        $result = $this->GroupModel->joinInvite($group['GroupID'], Gdn::session()->UserID, false);
        $this->setData('Result', $result);
        $this->jsonTarget('.GroupUserHeaderModule', '', 'SlideUp');
        $this->jsonTarget(".group-invites #Group_{$group['GroupID']}", '', 'Remove');
        $this->jsonTarget(".group-invites", 'checkIfGroupInvitesAreEmpty', 'Callback');
        $this->jsonTarget(".Group-Header .Group-Buttons", '', 'Remove');
        $this->informMessage(t('Invitation declined.'));

        if ($refresh) {
            $this->setRedirectTo('/groups');
        }
        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     *
     *
     * @param $iD
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function join($iD) {
        $group = $this->GroupModel->getID($iD);
        $this->verifyAccess($group);

        $userID = Gdn::session()->UserID;
        if ($this->GroupModel->isMember($userID, $group['GroupID'])) {
            redirectTo(groupUrl($group));
        }
        $this->groupPermission('Join', $group);

        $this->setData('Title', sprintf(t('Join %s'), htmlspecialchars($group['Name'])));
        $form = new Gdn_Form();
        $this->Form = $form;

        if ($form->authenticatedPostBack()) {
            // If the user posted back then we are going to add them.
            $data = $form->formValues();
            $data['UserID'] = Gdn::session()->UserID;
            $data['GroupID'] = $group['GroupID'];
            $saved = $this->GroupModel->join($data);
            $form->setValidationResults($this->GroupModel->validationResults());

            if ($saved) {
                $this->setRedirectTo(groupUrl($group));
            }
        }

        $this->setData('Group', $group);
        $this->addBreadcrumb($group['Name'], groupUrl($group));
        $this->render();
    }

    /**
     *
     *
     * @param $iD
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function leave($iD) {
        $group = $this->GroupModel->getID($iD);
        $this->verifyAccess($group);

        // Check join permission.
        if (!$this->GroupModel->checkPermission('Leave', $group)) {
            throw forbiddenException('@'.$this->GroupModel->checkPermission('Leave.Reason', $group));
        }

        $this->setData('Title', sprintf(t('Leave %s'), htmlspecialchars($group['Name'])));

        $form = new Gdn_Form();
        $this->Form = $form;

        if ($form->authenticatedPostBack()) {
            $data = [
                'UserID' => Gdn::session()->UserID,
                'GroupID' => $group['GroupID']
            ];
            $this->GroupModel->leave($data);
            $this->setRedirectTo('/groups');
        }

        $this->setData('Group', $group);
        $this->addBreadcrumb($group['Name'], groupUrl($group));
        $this->render();
    }

    /**
     *
     *
     * @param $iD
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function delete($iD) {
        $group = $this->GroupModel->getID($iD);
        $this->verifyAccess($group);
        $this->setData('Group', $group);

        if (!groupPermission('Leader')) {
            throw forbiddenException('@'.groupPermission('Edit.Reason'));
        }

        $form = new Gdn_Form();
        $this->Form = $form;

        if ($this->Form->authenticatedPostBack()) {
            $groupModel = new GroupModel();
            $groupDeleted = $groupModel->delete(['GroupID' => $group['GroupID']]);

            $eventModel = new EventModel();
            $eventModel->delete(['GroupID' => $group['GroupID']]);

            if ($groupDeleted) {
                $this->informMessage(formatString(t('<b>{Name}</b> deleted.'), $group));
                $this->setRedirectTo('/groups');
            } else {
                $this->informMessage(t('Failed to delete group.'));
            }
        }

        $this->setData('Title', t('Delete Group'));
        $this->render();
    }


    /**
     * Add a user as a member to a group.
     *
     * @param int $userID The UserID of the user to be added.
     * @param int $groupID The GroupID of the group to receive the user.
     * @param string $role accepts either Leader or Member.
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function addUserToGroup($userID, $groupID, $role = 'Member') {
        if (!Gdn::session()->checkRankedPermission('Garden.Moderation.Manage')) {
            throw permissionException();
        }

        $group = $this->GroupModel->getID($groupID);
        $this->verifyAccess($group);

        $userModel = new UserModel();
        $user = $userModel->getID($userID);
        if (!$user) {
            throw notFoundException('User');
        }

        $data['GroupID'] = $groupID;
        $data['UserID'] = $userID;
        $data['Role'] = $role;

        $form = new Gdn_Form();
        $this->Form = $form;
        if ($form->authenticatedPostBack()) {
            $userGroupModel = new Gdn_Model('UserGroup');
            $userExists = $userGroupModel->getWhere(['GroupID' => $groupID, 'UserID' => $userID])->resultArray();
            if (count($userExists)) {
                $this->setData('Warning', sprintf('User %1$s is already part of group %2$s', $userID, $groupID));
            } else {
                $userGroupID = $userGroupModel->insert($data);
                $this->GroupModel->updateCount($groupID, 'CountMembers');
                $this->setData('UserGroupID', $userGroupID);
                if (count($userGroupModel->validationResults()) === 0) {
                    $this->setData('Success', true);
                } else {
                    $this->setData('Errors', $userGroupModel->validationResults());
                }
            }
            $this->render();
        }
    }

    /**
     * Save an image from a field and delete any old image that's been uploaded.
     *
     * This method is a canditate for putting on the form object.
     *
     * @param Gdn_Form $form
     * @param string $field The name of the field. The image will be uploaded with the _New extension while the current image will be just the field name.
     * @param array $options
     */
    protected static function saveImage($form, $field, $options = []) {
        $upload = new Gdn_UploadImage();

        if (!valr("{$field}_New.name", $_FILES)) {
            trace("$field not uploaded, returning.");
            return false;
        }

        // First make sure the file is valid.
        try {
            $tmpName = $upload->validateUpload($field.'_New', true);
            if (!$tmpName) {
                return false; // no file uploaded.
            }
        } catch (Exception $ex) {
            $form->addError($ex);
            return false;
        }

        // Get the file extension of the file.
        $ext = val('OutputType', $options, trim($upload->getUploadedFileExtension(), '.'));
        if ($ext == 'jpeg') {
            $ext = 'jpg';
        }
        trace($ext, 'Ext');

        // The file is valid so let's come up with its new name.
        if (isset($options['Name'])) {
            $name = $options['Name'];
        } elseif (isset($options['Prefix'])) {
            $name = $options['Prefix'].md5(microtime()).'.'.$ext;
        } else {
            $name = md5(microtime()).'.'.$ext;
        }

        // We need to parse out the size.
        $size = val('Size', $options);
        if ($size) {
            if (is_numeric($size)) {
                touchValue('Width', $options, $size);
                touchValue('Height', $options, $size);
            } elseif (preg_match('`(\d+)x(\d+)`i', $size, $m)) {
                touchValue('Width', $options, $m[1]);
                touchValue('Height', $options, $m[2]);
            }
        }

        trace($options, "Saving image $name.");
        try {
            $parsed = $upload->saveImageAs($tmpName, $name, val('Height', $options, ''), val('Width', $options, ''), $options);
            trace($parsed, 'Saved Image');

            $current = $form->getFormValue($field);
            if ($current && val('DeleteOriginal', $options, true)) {
                // Delete the current image.
                trace("Deleting original image: $current.");
                if ($current) {
                    $upload->delete($current);
                }
            }
            // Set the current value.
            $form->setFormValue($field, $parsed['SaveName']);
        } catch (Exception $ex) {
            $form->addError($ex);
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
                ['SaveGif' => c('Garden.Thumbnail.SaveGif')]
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
     * @param bool $iD
     * @throws Exception
     * @throws Gdn_UserException
     */
    protected function addEdit($iD = false) {
        $form = new Gdn_Form();
        $form->setModel($this->GroupModel);
        Gdn_Theme::section('Post');

        $group = null;

        if ($iD) {
            $group = $this->GroupModel->getID($iD);

            if (!$group) {
                throw notFoundException('Group');
            }

            // Make sure the user can edit this group.
            if (!$this->GroupModel->checkPermission('Edit', $group)) {
                throw forbiddenException('@'.$this->GroupModel->checkPermission('Edit.Reason', $group));
            }

            $this->setData('Group', $group);
            $this->addBreadcrumb($group['Name'], groupUrl($group));
        }

        $icon = val('Icon', $group);

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
            $crop = new CropImageModule($this, $form, $thumbnailSize, $thumbnailSize, $source);
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
        $categories = array_filter(CategoryModel::categories(), function($row) { return $row['AllowGroups']; });
        $categories = array_column($categories, 'Name', 'CategoryID');
        $this->setData('Categories', $categories);

        if ($form->authenticatedPostBack()) {
            // We need to save the images before saving to the database.
            self::saveImage($form, 'Banner', ['Prefix' => 'groups/banners/banner_', 'Size' => c('Groups.BannerSize', '1000x250'), 'Crop' => true, 'OutputType' => 'jpeg']);

            if ($tmpIcon = $upload->validateUpload('Icon_New', false)) {
                // New upload
                $thumbOptions = ['Crop' => true, 'SaveGif' => c('Garden.Thumbnail.SaveGif')];
                $newIcon = $this->saveIcons($tmpIcon, $thumbOptions);
                $form->setFormValue('Icon', $newIcon);
            } else if ($icon && $crop && $crop->isCropped()) {
                // New thumbnail
                $tmpIcon = $source;
                $thumbOptions = ['Crop' => true,
                    'SourceX' => $crop->getCropXValue(),
                    'SourceY' => $crop->getCropYValue(),
                    'SourceWidth' => $crop->getCropWidth(),
                    'SourceHeight' => $crop->getCropHeight()];
                $newIcon = $this->saveIcons($tmpIcon, $thumbOptions);
                $form->setFormValue('Icon', $newIcon);
            }
            if ($form->errorCount() == 0) {
                if ($newIcon) {
                    $form->setFormValue('Icon_New', $newIcon);
                    $icon = $newIcon;

                    // Update crop properties.
                    $basename = changeBasename($icon, "p%s");
                    $source = $upload->copyLocal($basename);
                    $crop = new CropImageModule($this, $form, $thumbnailSize, $thumbnailSize, $source);
                    $crop->setExistingCropUrl(Gdn_UploadImage::url($icon));
                    $crop->setSourceImageUrl(Gdn_UploadImage::url(changeBasename($icon, "p%s")));
                    $this->setData('crop', $crop);
                }
            }

            // Make sure the group ID can't be spoofed.
            if ($iD) {
                $form->setFormValue('GroupID', $this->data('Group.GroupID'));
            }

            try {
                $groupID = $form->save();
            } catch (Exception $ex) {
                $form->addError($ex);
            }
            if ($groupID) {
                $group = $this->GroupModel->getID($groupID);
                redirectTo(groupUrl($group));
            } else {
                trace($form->formValues());
            }
        } else {
            if ($iD) {
                // Load the group.
                $form->setData($group);
            } else {
                // Set some default settings.
                $form->setValue('Registration', 'Public');
                $form->setValue('Visibility', 'Public');

                if (count($categories) == 1) {
                    $form->setValue('CategoryID', array_keys($categories)[0]);
                }
            }
        }
        $this->Form = $form;
        $this->CssClass .= ' NoPanel';
        $this->render('AddEdit');
    }

    /**
     * Settings page for uploading, deleting and cropping a group icon.
     *
     * @throws Exception
     */
    public function groupIcon($id = false) {
        if(!$id) {
            throw notFoundException();
        }
        $form = new Gdn_Form();
        $form->setModel($this->GroupModel);
        $this->title(t('Group Icon'));
        $this->addJsFile('groupicons.js');
        if ($id) {
            $group = $this->GroupModel->getID($id);
            $this->verifyAccess($group);

            // Make sure the user can edit this group.
            if (!$this->GroupModel->checkPermission('Edit', $group)) {
                throw forbiddenException('@' . $this->GroupModel->checkPermission('Edit.Reason', $group));
            }
            $this->setData('Group', $group);
            $this->addBreadcrumb($group['Name'], groupUrl($group));
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
                 $thumbOptions = ['Crop' => true, 'SaveGif' => c('Garden.Thumbnail.SaveGif')];
                 $newIcon = $this->saveIcons($tmpIcon, $thumbOptions);
                 $form->setFormValue('Icon', $newIcon);
                 $target = 'groupicon'; // redirect to groupicon page so user can set thumbnail
            } elseif ($icon && $crop && $crop->isCropped()) {
                 // New thumbnail
                 $tmpIcon = $source;
                 $thumbOptions = ['Crop' => true,
                      'SourceX' => $crop->getCropXValue(),
                      'SourceY' => $crop->getCropYValue(),
                      'SourceWidth' => $crop->getCropWidth(),
                      'SourceHeight' => $crop->getCropHeight()];
                 $newIcon = $this->saveIcons($tmpIcon, $thumbOptions);
                 $form->setFormValue('Icon', $newIcon);
            }

            if ($form->errorCount() == 0 && $newIcon) {
                if (!$this->GroupModel->save(['GroupID' => val('GroupID', $group), 'Icon' => $newIcon])) {
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
                redirectTo(groupUrl($group, $target));
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
        redirectTo(groupUrl($group, $target));
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
     * @param $iD
     * @param bool $page
     * @throws Exception
     */
    public function discussions($iD, $page = false) {
        Gdn_Theme::section('DiscussionList');

        $group = $this->GroupModel->getID($iD);
        $this->verifyAccess($group);

        $this->setData('Group', $group);
        $this->GroupModel->overridePermissions($group);

        [$offset, $limit] = offsetLimit($page, c('Vanilla.Discussions.PerPage', 30));
        $discussionModel = new DiscussionModel();
        $this->DiscussionData = $this->setData('Discussions', $discussionModel->getWhereRecent(['GroupID' => $group['GroupID']], $limit, $offset));
        $this->CountCommentsPerPage = c('Vanilla.Comments.PerPage', 30);
        $this->setData('_ShowCategoryLink', false);

        // Add modules
        $newDiscussionModule = new NewDiscussionModule();
        $newDiscussionModule->QueryString = 'groupid='.$group['GroupID'];
        $this->addModule($newDiscussionModule);
        $this->addModule('DiscussionFilterModule');
        $this->addModule('CategoriesModule');
        $this->addModule('BookmarkedModule');

        $this->setData('_NewDiscussionProperties', ['CssClass' => 'Button Action Primary', 'QueryString' => $newDiscussionModule->QueryString]);
        $this->Data['_properties']['newdiscussionmodule'] = ['CssClass' => 'Button Action Primary', 'QueryString' => $newDiscussionModule->QueryString];

        $this->addBreadcrumb($group['Name'], groupUrl($group));
        $this->addBreadcrumb(t('Discussions'));

        $layout = c('Vanilla.Discussions.Layout');
        switch($layout) {
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
        $pagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        $this->Pager = $pagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure(
            $offset,
            $limit,
            $group['CountDiscussions'],
            'group/discussions/'.groupSlug($group).'/%1$s'
        );
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'group/discussions/'.groupSlug($group).'/{Page}');
        }
        $this->setData('_Page', $page);
        $this->setData('_Limit', $limit);
        $this->fireEvent('AfterBuildPager');

        $this->setData("CountDiscussions", $group['CountDiscussions']);

        $header = new GroupHeaderModule($group);
        $this->addModule($header);
        $this->render($this->View, 'Discussions', 'Vanilla');
    }

    /**
     *
     *
     * @param $iD
     * @throws Exception
     */
    public function edit($iD) {
        $this->title(sprintf(t('Edit %s'), t('Group')));
        return $this->addEdit($iD);
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
        $this->verifyAccess($Group);

        // Check if this person is a member of the group or a moderator
        $viewGroupEvents = groupPermission('View', $Group);
        if (!$viewGroupEvents) {
            throw permissionException();
        }

        $this->Form = new Gdn_Form();
        $this->setData('Group', $Group);
        $this->addBreadcrumb($Group['Name'], groupUrl($Group));
        $this->addBreadcrumb(t('GroupMembers', 'Members'), groupUrl($Group, 'members'));

        [$Offset, $Limit] = offsetLimit($Page, $this->GroupModel->MemberPageSize);

        // Don't show the leaders module when filtering.
        if ($memberFilter) {
            $Filter = 'members';
        } elseif ($Offset === 0) {
            $Filter = '';
        }

        $this->setData('_Limit', $Limit);
        $this->setData('_Offset', $Limit);

        // Get Leaders
        if (in_array($Filter, ['', 'leaders'])) {
            $Users = $this->GroupModel->getMembers($Group['GroupID'], ['Role' => 'Leader'], $Limit, $Offset);
            $this->setData('Leaders', $Users);
        }

        // Get Members
        if (in_array($Filter, ['', 'members'])) {
            // Filter only by name (not by roles) when filtering
            if ($memberFilter) {
                $where = ['u.Name like' => $memberFilter.'%'];
            } else {
                $where = ['Role' => 'Member'];
            }
            $Users = $this->GroupModel->getMembers($Group['GroupID'], $where, $Limit, $Offset);
            $this->setData('Members', $Users);
        }

        $this->Data['_properties']['newdiscussionmodule'] = ['CssClass' => 'Button Action Primary', 'QueryString' => 'groupid='.$Group['GroupID']];
        $this->setData('Filter', $Filter);
        $this->title(t('Members').' - '.htmlspecialchars($Group['Name']));
        require_once $this->fetchViewLocation('group_functions');
        $this->CssClass .= ' NoPanel';
        $this->render('Members');
    }

    /**
     *
     *
     * @param $iD
     * @param $userID
     * @param $role
     * @throws Exception
     */
    public function setRole($iD, $userID, $role) {
        $group = $this->GroupModel->getID($iD);
        $this->verifyAccess($group);

        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
        if (!$user) {
            throw notFoundException('User');
        }

        if (!$this->GroupModel->checkPermission('Edit', $group)) {
            throw forbiddenException('@'.$this->GroupModel->checkPermission('Edit.Reason', $group));
        }

        $groupID = $group['GroupID'];

        $member = $this->GroupModel->getMembers($group['GroupID'], ['UserID' => $userID]);
        $member = array_pop($member);
        if (!$member) {
            throw notFoundException('Member');
        }

        // You can't demote the user that started the group.
        if ($userID == $group['InsertUserID']) {
            throw forbiddenException('@'.t("The user that started the group has to be a leader."));
        }

        if ($this->Request->isPostBack()) {
            $role = ucfirst($role);
            $this->GroupModel->setRole($groupID, $userID, $role);

            $this->informMessage(sprintf(t('%s is now a %s.'), htmlspecialchars($user['Name']), $role));
        }

        $this->setData('Group', $group);
        $this->setData('User', $user);
        $this->title(t('Group Role'));
        $this->render();
    }

    /**
     *
     *
     * @param $iD
     * @param $userID
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function removeMember($iD, $userID) {
        $group = $this->GroupModel->getID($iD);
        $this->verifyAccess($group);

        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
        if (!$user) {
            throw notFoundException('User');
        }

        if ($userID == Gdn::session()->UserID) {
            Gdn::dispatcher()->dispatch(groupUrl($group, 'leave'));
            return;
        }

        if (!$this->GroupModel->checkPermission('Moderate', $group)) {
            throw forbiddenException('@'.$this->GroupModel->checkPermission('Moderate.Reason', $group));
        }

        $groupID = $group['GroupID'];

        $member = $this->GroupModel->getMembers($group['GroupID'], ['UserID' => $userID]);
        $member = array_pop($member);
        if (!$member) {
            throw notFoundException('Member');
        }

        // You can't remove the user that started the group.
        if ($userID == $group['InsertUserID']) {
            throw forbiddenException('@'.t("You can't remove the creator of the group."));
        }

        // Only users that can edit the group can remove leaders.
        if ($member['Role'] == 'Leader' && !groupPermission('Edit')) {
            throw forbiddenException('@'.t("You can't remove another leader of the group."));
        }

        $form = new Gdn_Form();
        $this->Form = $form;

        if ($form->authenticatedPostBack()) {
            $this->GroupModel->removeMember($groupID, $userID, $this->Form->getFormValue('Type'));
            $this->jsonTarget("#Member_$userID", null, "Remove");
        } else {
            $form->setValue('Type', 'Removed');
        }

        $this->setData('Group', $group);
        $this->setData('User', $user);
        $this->title(t('Remove Member'));
        $this->render();
    }

    /**
     * Checks user permission on group and redirects accordingly
     *
     * @param string $permission
     * @param array $group
     * @throws Exception
     */
    private function groupPermission($permission, $group) {
        $session = Gdn::session();

        // Check group permission.
        if (!$this->GroupModel->checkPermission($permission, $group)) {
            //if group permission check fails redirect them accordingly
            if (!$session->isValid()) {
                redirectTo('/entry/signin?Target='.urlencode($this->Request->pathAndQuery()));
            } else {
                throw forbiddenException('@'.$this->GroupModel->checkPermission($permission.'.Reason', $group));
            }
        }
    }
}
