<?php if(!defined('APPLICATION')) die();

$PluginInfo['avatarstock'] = array(
   'Name' => 'Avatar Stock',
   'Description' => 'Create a limited stock of default avatars that members can choose between.',
   'Version' => '1.0.0',
   'Author' => 'Dane MacMillan',
   'AuthorEmail' => 'dane@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/dane',
   'RequiredApplications' => array('Vanilla' => '>=2.2'),
   'RequiredTheme' => false,
   'RequiredPlugins' => false,
   'HasLocale' => false,
   'RegisterPermissions' => false,
   'SettingsUrl' => '/settings/avatarstock',
   'SettingsPermission' => 'Garden.Setttings.Manage'
);

class AvatarStockPlugin extends Gdn_Plugin {

   private $database_prefix;
   private $table_name = 'AvatarStock';
   private $file_input_name = 'avatarstock_images';
   private $file_destination_dir;
   private $prefix_thumbnails = 'p';
   private $prefix_thumbnails_cropped = 'n';

   public function __construct() {
      $this->database_prefix = Gdn::Database()->DatabasePrefix;
      $this->file_destination_dir = 'avatarstock';
   }

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      Gdn::Structure()
         ->Table($this->table_name)
         ->PrimaryKey('AvatarID')
         ->Column('OriginalFileName', 'varchar(255)', true)
         ->Column('Caption', 'varchar(50)', true)
         ->Column('Path', 'varchar(255)', false, 'index')
         ->Column('SaveFormat', 'varchar(24)', true)
         ->Column('InsertUserID', 'int', true, 'index')
         ->Column('TimestampAdded', 'int(10)', true)
         ->Column('Deleted', 'tinyint(1)', 1, 'index')
         ->Set();
   }

   /**
    *
    * @param SettingsController $sender
    * @param array $args
    */
   public function SettingsController_AvatarStock_Create($sender, $args) {
      $sender->Permission('Garden.Settings.Manage');

      // Load some assets
      $sender->AddCssFile('avatarstock.css', 'plugins/avatarstock');
      $sender->AddJsFile('avatarstock.js', 'plugins/avatarstock');

      // Render components pertinent to all views.
      $sender->SetData('Title', T('Avatar Stock'));
      $sender->AddSideMenu();

      // Render specific component views.
      switch (true) {
         case in_array('upload', $args):
            // Handle upload and quickly parse file into DB.
            $results = $this->handleUploadInsert($sender);
            if ($results) {
               // This might interfere with API endpoint. Keep for now, adjust
               // when becomes a real concern.
               Redirect(Url('/settings/avatarstock'));
            } else {
               $sender->Render('upload', '', 'plugins/avatarstock');
            }
            break;

         case in_array('modify', $args):
               $this->deleteSelectedAvatars($sender);
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
    */
   public function Base_GetAppSettingsMenuItems_Handler(&$sender) {
      $menu = $sender->EventArguments['SideMenu'];
      $menu->AddItem('Users', T('Users'));
      $menu->AddLink('Users', T('Avatar Stock'), '/settings/avatarstock', 'Garden.Settings.Manage');
   }

   public function deleteSelectedAvatars($sender) {
      echo 'This has not been implemented yet.';
      exit;
   }

   /**
    * Get payload of avatar stock photos for view.
    */
   public function getStockAvatarPayload() {
      $stock_avatar_payload = array();
      $avatarstock_model = new Gdn_Model('AvatarStock');

      $payload = $avatarstock_model->GetWhere(array(
          'Deleted' => 0
      ))->ResultArray();

      $total_stock_images = count($payload);
      if ($total_stock_images) {
         $upload = new Gdn_Upload();

         for ($i = 0; $i < $total_stock_images; $i++) {

            // Load array with real URL paths for each thumbnail.
            $payload[$i]['_path'] = $upload->Url(ChangeBasename($payload[$i]['Path'], $this->prefix_thumbnails . '%s'));
            $payload[$i]['_path_crop'] = $upload->Url(ChangeBasename($payload[$i]['Path'], $this->prefix_thumbnails_cropped . '%s'));

            // Make sure caption is clean.
            $payload[$i]['Caption'] = Gdn_Format::PlainText($payload[$i]['Caption']);
         }

         $stock_avatar_payload = $payload;
      }

      return $stock_avatar_payload;
   }

   /**
    * Handle upload of stock avatars.
    *
    * @param SettingsController $sender
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
      $avatar_caption = Gdn::Request()->Post('avatar_caption');

      if (strlen($avatar_caption)) {
         $avatar_caption = substr($avatar_caption, 0, 50);
      } else {
         $avatar_caption = '';
      }

      if ($upload_image->CanUploadImages()
      && $upload_image->CanUpload($destination_dir)) {

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
         $target_name_thumbs = $upload_image->GenerateTargetName($destination_dir, '', false);
         $target_parsed = $upload_image->Parse($target_name_thumbs);

         // Database and filesystem paths are not the same.
         // ChangeBasename takes care of adding prefixes.
         $path_thumb_db = $target_parsed['SaveName'];
         $path_thumb_p = ChangeBasename($path_thumb_db, $this->prefix_thumbnails . '%s');
         $path_thumb_n = ChangeBasename($path_thumb_db, $this->prefix_thumbnails_cropped . '%s');

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
            array('Crop' => TRUE, 'SaveGif' => C('Garden.Thumbnail.SaveGif'))
         );

         // Insert into DB
         $avatarstock_model = new Gdn_Model('AvatarStock');
         $avatar_id = $avatarstock_model->Save(array(
            'OriginalFileName' => $original_filename,
            'Caption' => $avatar_caption,
            'Path' => $path_thumb_db,
            'SaveFormat' => $target_parsed['SaveFormat'],
            'InsertUserID' => Gdn::Session()->UserID,
            'TimestampAdded' => time(),
            'Deleted' => 0 // Change default to 0 to make active.
         ));

         if ($avatar_id) {
            $success = true;
         }
      }

      return $success;
   }

   public function Base_Render_Before($sender) {
      $sender->AddCssFile('avatarstock.css', 'plugins/avatarstock');
   }

   /**
    * Note, this may have an issue with session vs profile user. What is a good
    * way to grab current user on page.
    *
    *
    * @param type $sender
    * @throws type
    */
   public function ProfileController_Picture_Create($sender, $args) {

      if (!C('Garden.Profile.EditPhotos', true)) {
         throw ForbiddenException('@Editing user photos has been disabled.');
      }

      // Permission checks
      $sender->Permission(array('Garden.Profiles.Edit', 'Moderation.Profiles.Edit', 'Garden.ProfilePicture.Edit'), false);
      $session = Gdn::Session();
      if (!$session->IsValid()) {
         $sender->Form->AddError('You must be authenticated in order to use this form.');
      }

      $user_model = new UserModel();
      $avatarstock_model = new Gdn_Model('AvatarStock');
      $get = Gdn::Request()->Get();

      $sender->Form->SetModel('User');
      $sender->Form->AddHidden('UserID', $session->User->UserID);

      // When posted to self
      if ($sender->Form->AuthenticatedPostBack() === true) {

        // If there were no errors, associate the image with the user
         if ($sender->Form->ErrorCount() == 0) {
            //$user_id = $session->User->UserID;
            $post = Gdn::Request()->Post();
            $avatar_id = $post['AvatarID'];
            // Obviously not good, as users can change this, but need to make
            // sure mods and other admins can change this as well, unless it's
            // okay to check against session.
            $user_id = $post['UserID'];

            if (!ValidateInteger($avatar_id)) {
               $sender->Form->AddError('Invalid Avatar ID.');
            }

            if (!ValidateInteger($user_id) && $user_id != $session->User->UserID) {
               $sender->Form->AddError('You cannot modify this users avatar.');
            }

            if ($sender->Form->ErrorCount() == 0) {
               // Get avatar stock data
               $avatarstock_row = $avatarstock_model->GetWhere(array(
                   'AvatarID' => $avatar_id
               ))->FirstRow(DATASET_TYPE_ARRAY);
               $user_photo = $avatarstock_row['Path'];

               // Save it to User table
               if (!$user_model->Save(array('UserID' => $user_id, 'Photo' => $user_photo), array('CheckExisting' => true))) {
                  $sender->Form->SetValidationResults($user_model->ValidationResults());
               } else {
                  $session->User->Photo = $user_photo;
               }
            }
         }

         // If there were no problems, redirect back to the user account
         if ($sender->Form->ErrorCount() == 0) {
            $sender->InformMessage(Sprite('Check', 'InformSprite').T('Your changes have been saved.'), 'Dismissable AutoDismiss HasSprite');
            Redirect($sender->DeliveryType() == DELIVERY_TYPE_VIEW ? UserUrl($session->User) : UserUrl($session->User, '', 'picture'));
         }
      }

      if ($sender->Form->ErrorCount() > 0) {
         $sender->DeliveryType(DELIVERY_TYPE_ALL);
      }

      // Current avatar URL
      $user_stockavatar_id = false; // none

      // Don't like this at all. Bad.
      $current_user_id = (isset($args[0]))
         ? $args[0]
         : $get['userid'];

      if (ValidateInteger($current_user_id)) {
         $current_user_data = $user_model->GetWhere(array(
            'UserID' => $current_user_id
         ))->FirstRow(DATASET_TYPE_ARRAY);

         $current_user_photo = $current_user_data['Photo'];
         if (strlen($current_user_photo)) {
            // If in future you decide to add a second table to keep track of
            // AvatarID and UserID relationships, change this. For now, because
            // there really won't be a lot of stock photos to choose from,
            // it's probably okay to do where against the path.
            $relevant_stockavatar_row = $avatarstock_model->GetWhere(array(
                'Path' => $current_user_photo
            ))->FirstRow(DATASET_TYPE_ARRAY);

            if (isset($relevant_stockavatar_row['AvatarID'])) {
               $user_stockavatar_id = $relevant_stockavatar_row['AvatarID'];
            }
         }
      }

      $sender->SetData('_current_stockavatar_id', $user_stockavatar_id);

      // Render
      $stock_avatar_payload = $this->getStockAvatarPayload();
      $sender->SetData('_stock_avatar_payload', $stock_avatar_payload);
      $sender->Title(T('Choose Avatar'));
      $sender->_SetBreadcrumbs(T('Choose Avatar'), UserUrl($sender->User, '', 'picture'));

      //$this->AddSideMenu('', $sender);
      $sender->Render('picture', '', 'plugins/avatarstock');
   }



   public function AddSideMenu($CurrentUrl = '', $sender) {
/*
      $session = Gdn::Session();

      if (!$session->User)
         return;

      $c = new Gdn_Controller();

      // Make sure to add the "Edit Profile" buttons.
      $c->AddModule('ProfileOptionsModule');

      // Show edit menu if in edit mode
      // Show profile pic & filter menu otherwise
      $SideMenu = new SideMenuModule($c);
      $sender->EventArguments['SideMenu'] = &$SideMenu; // Doing this out here for backwards compatibility.
      if ($c->EditMode) {
         $c->AddModule('UserBoxModule');
         $c->BuildEditMenu($SideMenu, $CurrentUrl);
         $c->FireEvent('AfterAddSideMenu');
         $c->AddModule($SideMenu, 'Panel');
      } else {
         // Make sure the userphoto module gets added to the page
         $c->AddModule('UserPhotoModule');

         // And add the filter menu module
         $c->FireEvent('AfterAddSideMenu');
         $c->AddModule('ProfileFilterModule');
      }
 *
 */
   }

}
