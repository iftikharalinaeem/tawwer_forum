<?php if (!defined('APPLICATION')) {
    die();
}

$PluginInfo['avatarstock'] = array(
    'Name' => 'Avatar Pool',
    'Description' => 'Create a limited stock of default avatars that members can choose between.',
    'Version' => '1.2.2',
    'Author' => 'Dane MacMillan',
    'AuthorEmail' => 'dane@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.org/profile/dane',
    'RequiredApplications' => array('Vanilla' => '>=2.2'),
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'HasLocale' => false,
    'RegisterPermissions' => array(
        'AvatarPool.CustomUpload.Allow' => 0
    ),
    'SettingsUrl' => '/settings/avatarstock',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true
);

/**
 * Class AvatarStockPlugin.
 *
 * Provide users with a limited pool of avatars to choose from. Admins populate
 * the pool by uploading images in the dashboard.
 */
class AvatarStockPlugin extends Gdn_Plugin {

    private $database_prefix;
    private $table_name = 'AvatarStock';
    private $file_input_name = 'avatarstock_images';
    private $file_destination_dir;
    private $prefix_thumbnails = 'p';
    private $prefix_thumbnails_cropped = 'n';

    /**
     * Class constructor.
     */
    public function __construct() {
        $this->database_prefix = Gdn::Database()->DatabasePrefix;
        $this->file_destination_dir = 'avatarstock';
    }

    /**
     * Setup the plugin upon first enabling it.
     */
    public function setup() {
        $this->Structure();
    }

    /**
     * Allow utility/structure calls to restructure plugin.
     */
    public function structure() {
        Gdn::Structure()
            ->Table($this->table_name)
            ->PrimaryKey('AvatarID')
            ->Column('OriginalFileName', 'varchar(255)', true)
            ->Column('Path', 'varchar(255)', false, 'index')
            ->Column('InsertUserID', 'int', true)
            ->Column('TimestampAdded', 'int(10)', true)
            ->Column('Deleted', 'tinyint(1)', 1, 'index')
            ->Set();
    }

    /**
     * Create settings page in the dashboard.
     *
     * @param settingsController $sender The settings controller.
     * @param array $args Arguments passed to method.
     */
    public function settingsController_avatarStock_create($sender, $args) {
        $sender->Permission('Garden.Settings.Manage');

        // Load some assets
        $sender->AddCssFile('avatarstock.css', 'plugins/avatarstock');
        $sender->AddJsFile('avatarstock.js', 'plugins/avatarstock');

        // Render components pertinent to all views.
        $sender->SetData('Title', T('Avatar Pool'));
        $sender->AddSideMenu();

        // Render specific component views.
        switch (true) {
            case in_array('upload', $args):
                // Handle upload and quickly parse file into DB.
                $results = $this->handleUploadInsert($sender);
                if ($results) {
                    // This might interfere with API endpoint. Keep for now, adjust
                    // when becomes a real concern.
                    Redirect('/settings/avatarstock');
                } else {
                    $sender->Render('upload', '', 'plugins/avatarstock');
                }
                break;

            case in_array('modify', $args):
                $results = $this->deleteSelectedAvatars($sender);
                if ($results) {
                    Redirect('/settings/avatarstock');
                } else {
                    $sender->Render('upload', '', 'plugins/avatarstock');
                }
                break;

            default:
                $sender->SetData('_file_input_name', $this->file_input_name);
                $stock_avatar_payload = $this->getStockAvatarPayload();
                $sender->SetData('_payload', $stock_avatar_payload);
                $sender->Render('settings', '', 'plugins/avatarstock');
                break;
        }
    }

    /**
     * Adds menu option to the left in dashboard.
     *
     * @param Base &$sender The base controller.
     */
    public function base_getAppSettingsMenuItems_handler(&$sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->AddItem('Users', T('Users'));
        $menu->AddLink(
            'Users',
            T('Avatar Pool'),
            '/settings/avatarstock',
            'Garden.Settings.Manage',
            array('class' => 'nav-avatars')
        );
    }

    /**
     * Delete the selected avatar in the dashboard.
     *
     * @param settingsController $sender The settings controller.
     *
     * @return int
     * @throws Exception No authenticated postback.
     */
    public function deleteSelectedAvatars($sender) {
        $sender->Permission('Garden.Settings.Manage');

        if (!$sender->Request->IsAuthenticatedPostBack()) {
            throw ForbiddenException('GET');
        }

        $post = Gdn::Request()->Post();
        $avatar_ids = array_values($post['avatar_delete']);
        if (empty($avatar_ids)) {
           return 0;
        }

        Gdn::SQL()->Put(
            'AvatarStock',
            array(
                'Deleted' => 1
            ),
            array(
                'AvatarID' => $avatar_ids
            )
        );

        return count($avatar_ids);
    }

    /**
     * Handle removing photo action for stock avatars.
     *
     * Check for the 'avatarstock' string. Without this, when a user chooses
     * to remove a picture that is part of the avatar pool, that picture
     * (its large version) will actually be deleted. Regularly uploaded avatars
     * can be removed using the core functionality. If there is a match against
     * the mentioned string, then consider the delete handled, which will
     * prevent the core logic from calling unlink against the stock avatar file.
     *
     * @param ProfileController $Sender The profile controller.
     * @param string $UserReference The user reference, if available.
     * @param string $Username The user's name, if available.
     * @param string $tk The transient key.
     */
    public function ProfileController_RemovePicture_Create($Sender, $UserReference = '', $Username = '', $tk = '') {
        $Sender->Permission('Garden.SignIn.Allow');
        $Session = Gdn::Session();
        if (!$Session->IsValid())
            $Sender->Form->AddError('You must be authenticated in order to use this form.');

        // Get user data & another permission check
        $Sender->GetUserInfo($UserReference, $Username, '', TRUE);
        $RedirectUrl = UserUrl($Sender->User, '', 'picture');
        if ($Session->ValidateTransientKey($tk) && is_object($Sender->User)) {
            $HasRemovePermission = CheckPermission('Garden.Users.Edit') || CheckPermission('Moderation.Profiles.Edit');
            if ($Sender->User->UserID == $Session->UserID || $HasRemovePermission) {
                if (strpos($Sender->User->Photo, $this->file_destination_dir) === false) {
                    // Do removal, set message, redirect
                    Gdn::UserModel()->RemovePicture($Sender->User->UserID);
                } else {
                    // "Remove" for avatarstock. This just means the column in
                    // the User table gets set to the default. The photo itself
                    // should not be removed.
                    $UserModel = Gdn::UserModel();
                    $User = $UserModel->GetID($Sender->User->UserID, DATASET_TYPE_ARRAY);
                    if ($Photo = $User['Photo']) {
                        $UserModel->SetField($Sender->User->UserID, 'Photo', NULL);
                    }
                }
                $Sender->InformMessage(T('Your picture has been removed.'));
            }
        }

        if (Gdn::Controller()->DeliveryType() == DELIVERY_TYPE_ALL) {
            Redirect($RedirectUrl);
        } else {
            $Sender->ControllerName = 'Home';
            $Sender->View = 'FileNotFound';
            $Sender->RedirectUrl = Url($RedirectUrl);
            $Sender->Render();
        }
    }

    /**
     * Get payload of avatar stock photos for view.
     */
    public function getStockAvatarPayload() {
        $stock_avatar_payload = array();
        $avatarstock_model = new Gdn_Model('AvatarStock');

        $payload = $avatarstock_model->GetWhere(
            array(
                'Deleted' => 0
            )
        )->ResultArray();

        $total_stock_images = count($payload);
        if ($total_stock_images) {
            $upload = new Gdn_Upload();

            for ($i = 0; $i < $total_stock_images; $i++) {

                // Load array with real URL paths for each thumbnail.
                $payload[$i]['_path'] = $upload->Url(
                    ChangeBasename(
                        $payload[$i]['Path'],
                        $this->prefix_thumbnails . '%s'
                    )
                );
                $payload[$i]['_path_crop'] = $upload->Url(
                    ChangeBasename(
                        $payload[$i]['Path'],
                        $this->prefix_thumbnails_cropped . '%s'
                    )
                );
            }

            $stock_avatar_payload = $payload;
        }

        return $stock_avatar_payload;
    }

    /**
     * Handle upload of stock avatars.
     *
     * @param settingsController $sender The settings controller.
     *
     * @return bool
     * @throws Exception Not authenticated postback.
     */
    public function handleUploadInsert($sender) {
        $sender->Permission('Garden.Settings.Manage');

        if (!$sender->Request->IsAuthenticatedPostBack()) {
            throw ForbiddenException('GET');
        }

        $success = false;
        $destination_dir = PATH_UPLOADS . '/' . $this->file_destination_dir;
        TouchFolder($destination_dir);
        $upload_image = new Gdn_UploadImage();

        if ($upload_image->CanUploadImages()
            && $upload_image->CanUpload($destination_dir)
        ) {

            $tmp_file = $upload_image->ValidateUpload($this->file_input_name);
            $original_filename = $upload_image->GetUploadedFileName();

            // The custom seems to be that 'p' represents a large profile picture,
            // while 'n' represents a cropped thumbnail. This is not saved in
            // the path in the table, but added later, but the file is saved with
            // those prefixes.
            //
            // For now, follow this custom, and when a user selects a stock avatar,
            // simply input that path in the Photo column of the User table. If
            // later you decide to create another DB table for keeping a
            // relationship between the AvatarID and UserID, you can revert back
            // to random thumnail and cropped thumbnails.

            // Generate the target image name.
            $target_name_thumbs = $upload_image->GenerateTargetName(
                $destination_dir,
                '',
                false
            );
            $target_parsed = $upload_image->Parse($target_name_thumbs);

            // Database and filesystem paths are not the same.
            // ChangeBasename takes care of adding prefixes.
            $path_thumb = $target_parsed['SaveName'];
            $path_thumb_p = ChangeBasename(
                $path_thumb,
                $this->prefix_thumbnails . '%s'
            );
            $path_thumb_n = ChangeBasename(
                $path_thumb,
                $this->prefix_thumbnails_cropped . '%s'
            );

            // Create p thumbnail
            $thumb_parsed = $upload_image->SaveImageAs(
                $tmp_file,
                $path_thumb_p,
                C('Garden.Profile.MaxHeight'),
                C('Garden.Profile.MaxWidth'),
                array(
                    'SaveGif' => C('Garden.Thumbnail.SaveGif')
                )
            );

            // Create n thumbnail (cropped)
            $crop_dimensions = C('Garden.Thumbnail.Size', 40);
            $crop_parsed = $upload_image->SaveImageAs(
                $tmp_file,
                $path_thumb_n,
                $crop_dimensions,
                $crop_dimensions,
                array(
                    'Crop' => true,
                    'SaveGif' => C('Garden.Thumbnail.SaveGif')
                )
            );

            // Generate correct save path for db
            $path_thumb_db = sprintf($thumb_parsed['SaveFormat'], $path_thumb);

            // Insert into DB
            $avatarstock_model = new Gdn_Model('AvatarStock');
            $avatar_id = $avatarstock_model->Save(
                array(
                    'OriginalFileName' => $original_filename,
                    'Path' => $path_thumb_db,
                    'InsertUserID' => Gdn::Session()->UserID,
                    'TimestampAdded' => time(),
                    'Deleted' => 0 // Change default to 0 to make active.
                )
            );

            if ($avatar_id) {
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Insert files on every page.
     *
     * @param Base $sender The base controller.
     */
    public function base_render_before($sender) {
        $sender->AddCssFile('avatarstock.css', 'plugins/avatarstock');
        $sender->AddJsFile('avatarstock.js', 'plugins/avatarstock');
    }

    /**
     * Create endpoint for posting user-selected avatar from pool.
     *
     * In addition, should a user have the right permissions, they can upload
     * an avatar of their own.
     *
     * @param profileController $sender The profile controller.
     * @param string $UserReference The user reference.
     * @param string $Username The username.
     * @param string $UserID The userID.
     *
     * @throws Exception User cannot edit photos.
     */
    public function profileController_picture_create(
        $sender,
        $UserReference = '',
        $Username = '',
        $UserID = ''
    ) {
        if (!C('Garden.Profile.EditPhotos', true)) {
            throw ForbiddenException('@Editing user photos has been disabled.');
        }

        // Permission checks
        $sender->Permission(
            array(
                'Garden.Profiles.Edit',
                'Moderation.Profiles.Edit',
                'Garden.ProfilePicture.Edit'
            ),
            false
        );
        $session = Gdn::Session();
        if (!$session->IsValid()) {
            $sender->Form->AddError(
                'You must be authenticated in order to use this form.'
            );
        }

        $sender->GetUserInfo($UserReference, $Username, $UserID, true);
        $user_model = Gdn::UserModel();
        $avatarstock_model = new Gdn_Model('AvatarStock');
        $user_id = $sender->User->UserID;
        $sender->Form->SetModel('User');

        // When posted to self
        if ($sender->Form->AuthenticatedPostBack() === true) {
            $sender->Form->SetFormValue('UserID', $sender->User->UserID);

            // Determine if image being uploaded. Only privileged users can
            // do this. Otherwise, choose from avatar pool.
            $UploadImage = new Gdn_UploadImage();
            $TmpImage = $UploadImage->ValidateUpload('Picture', false);

            // Selecting from avatar pool.
            if (!$TmpImage) {
                // If there were no errors, associate the image with the user
                if ($sender->Form->ErrorCount() == 0) {
                    $post = Gdn::Request()->Post();
                    $avatar_id = $post['AvatarID'];

                    if (!ValidateInteger($avatar_id)) {
                        $sender->Form->AddError('Invalid Avatar ID.');
                    }

                    if ($sender->Form->ErrorCount() == 0) {

                        // Get avatar stock data
                        $avatarstock_row = $avatarstock_model->GetWhere(
                            array(
                                'AvatarID' => $avatar_id
                            )
                        )->FirstRow(DATASET_TYPE_ARRAY);

                        $user_photo = $avatarstock_row['Path'];

                        // Save it to User table
                        if (!$user_model->Save(
                            array('UserID' => $user_id, 'Photo' => $user_photo),
                            array('CheckExisting' => true)
                        )
                        ) {
                            $sender->Form->SetValidationResults(
                                $user_model->ValidationResults()
                            );
                        } else {
                            $sender->User->Photo = $user_photo;
                        }
                    }
                }
            } else {
                // Handle the image upload like it was originally part of the
                // profile controller.

                // Only admins and users with the custom permission can upload
                // their own avatars.
                $sender->Permission(array(
                    'Garden.Settings.Manage',
                    'AvatarPool.CustomUpload.Allow'
                ), false);

                // Generate the target image name.
                $TargetImage = $UploadImage->GenerateTargetName(PATH_UPLOADS, '', TRUE);
                $Basename = pathinfo($TargetImage, PATHINFO_BASENAME);
                $Subdir = StringBeginsWith(dirname($TargetImage), PATH_UPLOADS.'/', FALSE, TRUE);

                // Delete any previously uploaded image.
                $UploadImage->Delete(ChangeBasename($sender->User->Photo, 'p%s'));

                // Save the uploaded image in profile size.
                $Props = $UploadImage->SaveImageAs(
                    $TmpImage,
                    "userpics/$Subdir/p$Basename",
                    C('Garden.Profile.MaxHeight', 1000),
                    C('Garden.Profile.MaxWidth', 250),
                    array('SaveGif' => C('Garden.Thumbnail.SaveGif'))
                );
                $UserPhoto = sprintf($Props['SaveFormat'], "userpics/$Subdir/$Basename");

                // Save the uploaded image in thumbnail size
                $ThumbSize = Gdn::Config('Garden.Thumbnail.Size', 40);
                $UploadImage->SaveImageAs(
                    $TmpImage,
                    "userpics/$Subdir/n$Basename",
                    $ThumbSize,
                    $ThumbSize,
                    array('Crop' => TRUE, 'SaveGif' => C('Garden.Thumbnail.SaveGif'))
                );

                // If there were no errors, associate the image with the user
                if ($sender->Form->ErrorCount() == 0) {
                    if (!$user_model->Save(array('UserID' => $sender->User->UserID, 'Photo' => $UserPhoto), array('CheckExisting' => TRUE))) {
                        $sender->Form->SetValidationResults($user_model->ValidationResults());
                    } else {
                        $sender->User->Photo = $UserPhoto;
                    }
                }
            }

            // If there were no problems, redirect back to the user account
            if ($sender->Form->ErrorCount() == 0) {
                $sender->InformMessage(
                    Sprite('Check', 'InformSprite') . T(
                        'Your changes have been saved.'
                    ),
                    'Dismissable AutoDismiss HasSprite'
                );
                Redirect(
                    $sender->DeliveryType() == DELIVERY_TYPE_VIEW ? UserUrl(
                        $sender->User
                    ) : UserUrl($sender->User, '', 'picture')
                );
            }
        }

        if ($sender->Form->ErrorCount() > 0) {
            $sender->DeliveryType(DELIVERY_TYPE_ALL);
        }

        // Current avatar URL
        $user_stockavatar_id = false; // none

        if (ValidateInteger($user_id)) {
            $current_user_data = $user_model->GetID(
                $user_id,
                DATASET_TYPE_ARRAY
            );
            $current_user_photo = $current_user_data['Photo'];

            if (strlen($current_user_photo)) {
                // If in future you decide to add a second table to keep track of
                // AvatarID and UserID relationships, change this. For now, because
                // there really won't be a lot of stock photos to choose from,
                // it's probably okay to do where against the path.
                $relevant_stockavatar_row = $avatarstock_model->GetWhere(
                    array(
                        'Path' => $current_user_photo
                    )
                )->FirstRow(DATASET_TYPE_ARRAY);

                if (!empty($relevant_stockavatar_row['AvatarID'])) {
                    $user_stockavatar_id = $relevant_stockavatar_row['AvatarID'];
                }
            }
        }

        $sender->SetData('_current_stockavatar_id', $user_stockavatar_id);

        // Render
        $stock_avatar_payload = $this->getStockAvatarPayload();
        $sender->SetData('_stock_avatar_payload', $stock_avatar_payload);
        $sender->Title(T('Choose Avatar'));
        $sender->_SetBreadcrumbs(
            T('Choose Avatar'),
            UserUrl($sender->User, '', 'picture')
        );
        $sender->Render('picture', '', 'plugins/avatarstock');
    }

    /**
     * Prevent all users from accessing the thumbnail editing page.
     *
     * @param string $UserReference The user reference.
     * @param string $Username The username.
     *
     * @throws Exception Never allow thumbnail editing.
     */
    public function profileController_thumbnail_create(
        $UserReference = '',
        $Username = ''
    ) {
        throw ForbiddenException('@Editing user photos has been disabled.');
    }

    /**
     * Remove the edit thumbnail link from the user edit preferences page.
     *
     * @param profileController &$sender The profile controller.
     */
    public function profileController_afterAddSideMenu_handler(&$sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->RemoveLink('Options', '/profile/thumbnail');
    }
}
