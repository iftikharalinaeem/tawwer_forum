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

   public function __construct() {
      $this->database_prefix = Gdn::Database()->DatabasePrefix;
      $this->file_destination_dir = PATH_UPLOADS . '/avatarstock';
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
         ->Column('Path', 'varchar(255)', false)
         ->Column('StorageMethod', 'varchar(24)', true)
         ->Column('TimestampAdded', 'int(10)', true)
         ->Column('Deleted', 'tinyint(1)', 0)
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

         default:
            $sender->SetData('_file_input_name', $this->file_input_name);
            $stock_avatar_payload = $this->getStockAvatarPayload($sender);
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

   /**
    * Get payload of avatar stock photos for view.
    */
   public function getStockAvatarPayload($sender) {
      $stock_avatar_payload = array();
      $avatarstock_model = new Gdn_Model('AvatarStock');

      $payload = $avatarstock_model->GetWhere(array(
          'Deleted' => 0
      ))->ResultArray();


      $total_stock_images = count($payload);
      if ($total_stock_images) {
         $upload = new Gdn_Upload();
         for ($i = 0; $i < $total_stock_images; $i++) {
            // Make sure URL is valid.
            $payload[$i]['Path'] = $upload->Url($payload[$i]['Path']);
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
      $destination_dir = $this->file_destination_dir;
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

         // Create thumbnail
         $target_name_thumb = $upload_image->GenerateTargetName($destination_dir);
         $thumb_parsed = $upload_image->SaveImageAs(
            $tmp_file,
            $target_name_thumb,
            C('Garden.Profile.MaxHeight'),
            C('Garden.Profile.MaxWidth'),
            array(
               'SaveGif' => C('Garden.Thumbnail.SaveGif')
            )
         );

         // Insert into DB
         $avatarstock_model = new Gdn_Model('AvatarStock');
         $avatar_id = $avatarstock_model->Save(array(
            'OriginalFileName' => $original_filename,
            'Caption' => $avatar_caption,
            'Path' => $thumb_parsed['SaveName'],
            'TimestampAdded' => time(),
         ));

         if ($avatar_id) {
            $success = $upload_image->Url($thumb_parsed['SaveName']);
         }
      }

      return $success;
   }

}
