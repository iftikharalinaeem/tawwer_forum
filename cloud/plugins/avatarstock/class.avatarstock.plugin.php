<?php if (!defined('APPLICATION')) {
    die();
}

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
        $this->database_prefix = Gdn::database()->DatabasePrefix;
        $this->file_destination_dir = 'avatarstock';
    }

    /**
     * Setup the plugin upon first enabling it.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Allow utility/structure calls to restructure plugin.
     */
    public function structure() {
        Gdn::structure()
            ->table($this->table_name)
            ->primaryKey('AvatarID')
            ->column('Name', 'varchar(100)', true)
            ->column('OriginalFileName', 'varchar(255)', true)
            ->column('Path', 'varchar(191)', false, 'index')
            ->column('InsertUserID', 'int', true)
            ->column('TimestampAdded', 'int(10)', true)
            ->column('Deleted', 'tinyint(1)', 1, 'index')
            ->set();
    }

    /**
     * Create settings page in the dashboard.
     *
     * @param SettingsController $sender The settings controller.
     * @param array $args Arguments passed to method.
     */
    public function settingsController_avatarStock_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');

        // Load some assets
        $sender->addCssFile('avatarstock.css', 'plugins/avatarstock');
        $sender->addJsFile('avatarstock.js', 'plugins/avatarstock');

        if (in_array('upload', $args)) {
            // Handle upload and quickly parse file into DB.
            $results = $this->handleUploadInsert($sender);
            if ($results) {
                // This might interfere with API endpoint. Keep for now, adjust
                // when becomes a real concern.
                redirectTo('/settings/avatars');
            } else {
                $sender->render('upload', '', 'plugins/avatarstock');
            }
        } else if (in_array('modify', $args)) {
            $results = $this->deleteSelectedAvatars($sender);
            if ($results) {
                redirectTo('/settings/avatars');
            } else {
                $sender->render('upload', '', 'plugins/avatarstock');
            }
        }
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
        $sender->permission('Garden.Settings.Manage');

        if (!$sender->Request->isAuthenticatedPostBack()) {
            throw forbiddenException('GET');
        }

        $post = Gdn::request()->post();
        $avatar_ids = array_values($post['avatar_delete']);
        if (empty($avatar_ids)) {
           return 0;
        }

        Gdn::sql()->put(
            'AvatarStock',
            [
                'Deleted' => 1
            ],
            [
                'AvatarID' => $avatar_ids
            ]
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
     * @param ProfileController $sender The profile controller.
     * @param string $userReference The user reference, if available.
     * @param string $username The user's name, if available.
     * @param string $tk The transient key.
     */
    public function profileController_removePicture_create($sender, $userReference = '', $username = '', $tk = '') {
        $sender->permission('Garden.SignIn.Allow');

        $session = Gdn::session();
        if (!$session->isValid())
            $sender->Form->addError('You must be authenticated in order to use this form.');

        // Get user data & another permission check
        $sender->getUserInfo($userReference, $username, '', TRUE);
        $redirectUrl = userUrl($sender->User, '', 'picture');
        if ($session->validateTransientKey($tk) && is_object($sender->User)) {
            $hasRemovePermission = checkPermission('Garden.Users.Edit') || checkPermission('Moderation.Profiles.Edit');
            if ($sender->User->UserID == $session->UserID || $hasRemovePermission) {
                if (strpos($sender->User->Photo, $this->file_destination_dir) === false) {
                    // Do removal, set message, redirect
                    Gdn::userModel()->removePicture($sender->User->UserID);
                } else {
                    // "Remove" for avatarstock. This just means the column in
                    // the User table gets set to the default. The photo itself
                    // should not be removed.
                    $userModel = Gdn::userModel();
                    $user = $userModel->getID($sender->User->UserID, DATASET_TYPE_ARRAY);
                    if ($photo = $user['Photo']) {
                        $userModel->setField($sender->User->UserID, 'Photo', NULL);
                    }
                }
                $sender->informMessage(t('Your picture has been removed.'));
            }
        }

        if (Gdn::controller()->deliveryType() == DELIVERY_TYPE_ALL) {
            redirectTo($redirectUrl);
        } else {
            $sender->ControllerName = 'Home';
            $sender->View = 'FileNotFound';
            $sender->setRedirectTo($redirectUrl);
            $sender->render();
        }
    }

    /**
     * Get payload of avatar stock photos for view.
     */
    public function getStockAvatarPayload() {
        $stock_avatar_payload = [];
        $avatarstock_model = new Gdn_Model('AvatarStock');

        $payload = $avatarstock_model->getWhere(
            [
                'Deleted' => 0
            ]
        )->resultArray();

        $total_stock_images = count($payload);
        if ($total_stock_images) {
            $upload = new Gdn_Upload();

            for ($i = 0; $i < $total_stock_images; $i++) {

                // Load array with real URL paths for each thumbnail.
                $payload[$i]['_path'] = $upload->url(
                    changeBasename(
                        $payload[$i]['Path'],
                        $this->prefix_thumbnails . '%s'
                    )
                );
                $payload[$i]['_path_crop'] = $upload->url(
                    changeBasename(
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
        $sender->permission('Garden.Settings.Manage');

        if (!$sender->Request->isAuthenticatedPostBack()) {
            throw forbiddenException('GET');
        }

        $success = false;
        $destination_dir = PATH_UPLOADS . '/' . $this->file_destination_dir;
        touchFolder($destination_dir);
        $upload_image = new Gdn_UploadImage();

        if ($upload_image->canUploadImages()
            && $upload_image->canUpload($destination_dir)
        ) {

            $tmp_file = $upload_image->validateUpload($this->file_input_name);
            $original_filename = $upload_image->getUploadedFileName();

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
            $target_name_thumbs = $upload_image->generateTargetName(
                $destination_dir,
                '',
                false
            );
            $target_parsed = $upload_image->parse($target_name_thumbs);

            // Database and filesystem paths are not the same.
            // ChangeBasename takes care of adding prefixes.
            $path_thumb = $target_parsed['SaveName'];
            $path_thumb_p = changeBasename(
                $path_thumb,
                $this->prefix_thumbnails . '%s'
            );
            $path_thumb_n = changeBasename(
                $path_thumb,
                $this->prefix_thumbnails_cropped . '%s'
            );

            // Create p thumbnail
            $thumb_parsed = $upload_image->saveImageAs(
                $tmp_file,
                $path_thumb_p,
                c('Garden.Profile.MaxHeight'),
                c('Garden.Profile.MaxWidth'),
                [
                    'SaveGif' => c('Garden.Thumbnail.SaveGif')
                ]
            );

            // Create n thumbnail (cropped)
            $crop_dimensions = c('Garden.Thumbnail.Size');
            $crop_parsed = $upload_image->saveImageAs(
                $tmp_file,
                $path_thumb_n,
                $crop_dimensions,
                $crop_dimensions,
                [
                    'Crop' => true,
                    'SaveGif' => c('Garden.Thumbnail.SaveGif')
                ]
            );

            // make sure both p & n thumbnails are valid before saving to the db.
            if ($thumb_parsed['Url'] === false || $crop_parsed['Url'] === false) {
                return false;
            }
            // Generate correct save path for db
            $path_thumb_db = sprintf($thumb_parsed['SaveFormat'], $path_thumb);
            $post = Gdn::request()->post();

            // Insert into DB
            $avatarstock_model = new Gdn_Model('AvatarStock');
            $avatar_id = $avatarstock_model->save(
                [
                    'Name' => val('name', $post, NULL),
                    'OriginalFileName' => $original_filename,
                    'Path' => $path_thumb_db,
                    'InsertUserID' => Gdn::session()->UserID,
                    'TimestampAdded' => time(),
                    'Deleted' => 0 // Change default to 0 to make active.
                ]
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
        $sender->addCssFile('avatarstock.css', 'plugins/avatarstock');
        $sender->addJsFile('avatarstock.js', 'plugins/avatarstock');
    }

    /**
     * Create endpoint for posting user-selected avatar from pool.
     *
     * In addition, should a user have the right permissions, they can upload
     * an avatar of their own.
     *
     * @param profileController $sender The profile controller.
     * @param string $userReference The user reference.
     * @param string $username The username.
     * @param string $userID The userID.
     *
     * @throws Exception User cannot edit photos.
     */
    public function profileController_picture_create($sender, $userReference = '', $username = '', $userID = '') {
        if (!c('Garden.Profile.EditPhotos', true)) {
            throw forbiddenException('@Editing user photos has been disabled.');
        }

        // Permission checks
        $sender->permission(
            [
                'Garden.Profiles.Edit',
                'Moderation.Profiles.Edit',
                'Garden.ProfilePicture.Edit'
            ],
            false
        );
        $session = Gdn::session();
        if (!$session->isValid()) {
            $sender->Form->addError(
                'You must be authenticated in order to use this form.'
            );
        }

        $sender->getUserInfo($userReference, $username, $userID, true);
        $user_model = Gdn::userModel();
        $avatarstock_model = new Gdn_Model('AvatarStock');
        $user_id = $sender->User->UserID;
        $sender->Form->setModel('User');

        // When posted to self
        if ($sender->Form->authenticatedPostBack() === true) {
            $sender->Form->setFormValue('UserID', $sender->User->UserID);

            // Determine if image being uploaded. Only privileged users can
            // do this. Otherwise, choose from avatar pool.
            $uploadImage = new Gdn_UploadImage();
            $tmpImage = $uploadImage->validateUpload('Picture', false);

            // Selecting from avatar pool.
            if (!$tmpImage) {
                // If there were no errors, associate the image with the user
                if ($sender->Form->errorCount() == 0) {
                    $post = Gdn::request()->post();
                    $avatar_id = $post['AvatarID'];

                    if (!validateInteger($avatar_id)) {
                        $sender->Form->addError('Invalid Avatar ID.');
                    }

                    if ($sender->Form->errorCount() == 0) {

                        // Get avatar stock data
                        $avatarstock_row = $avatarstock_model->getWhere(
                            [
                                'AvatarID' => $avatar_id
                            ]
                        )->firstRow(DATASET_TYPE_ARRAY);

                        $user_photo = $avatarstock_row['Path'];

                        // Save it to User table
                        if (!$user_model->save(
                            ['UserID' => $user_id, 'Photo' => $user_photo],
                            ['CheckExisting' => true]
                        )
                        ) {
                            $sender->Form->setValidationResults(
                                $user_model->validationResults()
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
                $sender->permission([
                    'Garden.Settings.Manage',
                    'AvatarPool.CustomUpload.Allow'
                ], false);

                // Generate the target image name.
                $targetImage = $uploadImage->generateTargetName(PATH_UPLOADS, '', TRUE);
                $basename = pathinfo($targetImage, PATHINFO_BASENAME);
                $subdir = stringBeginsWith(dirname($targetImage), PATH_UPLOADS.'/', FALSE, TRUE);

                // Delete any previously uploaded image.
                $uploadImage->delete(changeBasename($sender->User->Photo, 'p%s'));

                // Save the uploaded image in profile size.
                $props = $uploadImage->saveImageAs(
                    $tmpImage,
                    "userpics/$subdir/p$basename",
                    c('Garden.Profile.MaxHeight'),
                    c('Garden.Profile.MaxWidth'),
                    ['SaveGif' => c('Garden.Thumbnail.SaveGif')]
                );
                $userPhoto = sprintf($props['SaveFormat'], "userpics/$subdir/$basename");

                // Save the uploaded image in thumbnail size
                $thumbSize = c('Garden.Thumbnail.Size');
                $uploadImage->saveImageAs(
                    $tmpImage,
                    "userpics/$subdir/n$basename",
                    $thumbSize,
                    $thumbSize,
                    ['Crop' => TRUE, 'SaveGif' => c('Garden.Thumbnail.SaveGif')]
                );

                // If there were no errors, associate the image with the user
                if ($sender->Form->errorCount() == 0) {
                    if (!$user_model->save(['UserID' => $sender->User->UserID, 'Photo' => $userPhoto], ['CheckExisting' => TRUE])) {
                        $sender->Form->setValidationResults($user_model->validationResults());
                    } else {
                        $sender->User->Photo = $userPhoto;
                    }
                }
            }

            // If there were no problems, redirect back to the user account
            if ($sender->Form->errorCount() === 0) {
                $sender->informMessage(
                    sprite('Check', 'InformSprite').t('Your changes have been saved.'),
                    'Dismissable AutoDismiss HasSprite'
                );

                // If there were no problems, redirect back to the user account
                if ($sender->deliveryType() === DELIVERY_TYPE_VIEW) {
                    $sender->setRedirectTo(userUrl($sender->User));
                } else {
                    $sender->setRedirectTo(userUrl($sender->User, '', 'picture'));
                }
            }
        }

        if ($sender->Form->errorCount() > 0) {
            $sender->deliveryType(DELIVERY_TYPE_ALL);
        }

        // Current avatar URL
        $user_stockavatar_id = false; // none

        if (validateInteger($user_id)) {
            $current_user_data = $user_model->getID(
                $user_id,
                DATASET_TYPE_ARRAY
            );
            $current_user_photo = $current_user_data['Photo'];

            if (strlen($current_user_photo)) {
                // If in future you decide to add a second table to keep track of
                // AvatarID and UserID relationships, change this. For now, because
                // there really won't be a lot of stock photos to choose from,
                // it's probably okay to do where against the path.
                $relevant_stockavatar_row = $avatarstock_model->getWhere(
                    [
                        'Path' => $current_user_photo
                    ]
                )->firstRow(DATASET_TYPE_ARRAY);

                if (!empty($relevant_stockavatar_row['AvatarID'])) {
                    $user_stockavatar_id = $relevant_stockavatar_row['AvatarID'];
                }
            }
        }

        $sender->setData('_current_stockavatar_id', $user_stockavatar_id);

        // Render
        $stock_avatar_payload = $this->getStockAvatarPayload();
        $sender->setData('_stock_avatar_payload', $stock_avatar_payload);
        $sender->title(t('Choose Avatar'));
        $sender->_SetBreadcrumbs(
            t('Choose Avatar'),
            userUrl($sender->User, '', 'picture')
        );
        $sender->render('picture', '', 'plugins/avatarstock');
    }

    /**
     * Overrides allowing admins to set the default avatar, since it has no effect when Vanillicon is on.
     *
     * @param SettingsController $sender
     */
    public function settingsController_avatarSettings_handler($sender) {
        $sender->addCssFile('avatarstock.css', 'plugins/avatarstock');
        $sender->addJsFile('avatarstock.js', 'plugins/avatarstock');
        $sender->setData('_file_input_name', $this->file_input_name);
        $sender->setData('_input_name', 'name');
        $stock_avatar_payload = $this->getStockAvatarPayload();
        $sender->setData('_payload', $stock_avatar_payload);
        $sender->setData('AvatarSelectionOptions', $sender->fetchView('settings', '', 'plugins/avatarstock'));
    }

    /**
     * Prevent all users from accessing the thumbnail editing page.
     *
     * @param string $userReference The user reference.
     * @param string $username The username.
     *
     * @throws Exception Never allow thumbnail editing.
     */
    public function profileController_thumbnail_create(
        $userReference = '',
        $username = ''
    ) {
        throw forbiddenException('@Editing user photos has been disabled.');
    }

    /**
     * Remove the edit thumbnail link from the user edit preferences page.
     *
     * @param profileController &$sender The profile controller.
     */
    public function profileController_afterAddSideMenu_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->removeLink('Options', '/profile/thumbnail');
    }
}
