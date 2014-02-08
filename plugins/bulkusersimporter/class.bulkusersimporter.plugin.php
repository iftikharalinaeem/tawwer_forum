<?php if(!defined('APPLICATION')) die();

$PluginInfo['bulkusersimporter'] = array(
   'Name' => 'Bulk Users Importer',
   'Description' => 'Bulk users import with standardized CSV files.',
   'Version' => '1.0.0',
   'Author' => "Dane MacMillan",
   'AuthorEmail' => 'dane@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/dane',
   'RequiredApplications' => array('Vanilla' => '>=2.2'),
   'RequiredTheme' => false,
   'RequiredPlugins' => false,
   'HasLocale' => false,
   'RegisterPermissions' => false,
   'SettingsUrl' => '/settings/bulkusersimporter',
   'SettingsPermission' => 'Garden.Setttings.Manage'
);

class BulkUsersImporterPlugin extends Gdn_Plugin {

   private $database_prefix;
   private $table_name = 'bulk_users_importer';

   public function __construct() {
      $this->database_prefix = Gdn::Database()->DatabasePrefix;
   }

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      Gdn::Structure()
         ->Table($this->table_name)
         ->PrimaryKey('import_id')
         ->Column('email', 'varchar(200)', true, 'index')
         ->Column('username', 'varchar(50)', true)
         ->Column('status', 'varchar(50)', true)
         ->Column('completed', 'tinyint(1)', 0, 'index')
         ->Set();
   }

   /**
    *
    * @param SettingsController $sender
    * @param array $args
    */
   public function SettingsController_BulkUsersImporter_Create($sender, $args) {
      $sender->Permission('Garden.Settings.Manage');

      // Load some assets
      $c = Gdn::Controller();
      //$c->AddDefinition('readlessMaxHeight', $this->max_height);
      $sender->AddCssFile('bulkusersimporter.css', 'plugins/bulkusersimporter');
      $c->AddJsFile('bulkusersimporter.js', 'plugins/bulkusersimporter');

      // Render components pertinent to all views.
      $sender->SetData('Title', T('Bulk Users Importer'));
      $sender->AddSideMenu();

      // Render specific component views.
      switch (true) {
         case in_array('upload', $args):
            // Handle upload and quickly parse file into DB.
            $results = $this->handleUploadInsert($sender);
            $sender->SetData('results', $results);
            $sender->Render('upload', '', 'plugins/bulkusersimporter');
            break;

         case in_array('process', $args):
            // Process inserted CSV data and create and email new users.
            $this->processUploadedData($sender);
            $sender->Render('process', '', 'plugins/bulkusersimporter');
            break;

         default:
            $sender->Render('settings', '', 'plugins/bulkusersimporter');
            break;
      }
   }

   /**
    * Adds menu option to the left in dashboard.
    */
   public function Base_GetAppSettingsMenuItems_Handler(&$sender) {
      $menu = $sender->EventArguments['SideMenu'];
      $menu->AddItem('Import', T('Import'));
      $menu->AddLink('Import', T('Bulk Users Importer'), '/settings/bulkusersimporter', 'Garden.Settings.Manage');
   }

   /**
    * Controller to handle upload and bulk import files and options.
    *
    * @param SettingsController $sender
    */
   public function handleUploadInsert($sender) {
      $sender->Permission('Garden.Settings.Manage');

      if (!$sender->Request->IsAuthenticatedPostBack()) {
         throw ForbiddenException('GET');
      }

      $upload = new Gdn_Upload();
      if (!$upload->CanUpload()) {
         return;
      }

      // Returned by function
      $results = array(
         'success' => array(),
         'fail' => array()
      );

      // Get request data
      $import_files = Gdn::Request()->GetValueFrom(Gdn_Request::INPUT_FILES, 'import_files', FALSE);
      $post = Gdn::Request()->Post();

      // Determine if CSV has headers by getting state of checkbox
      $ignore_line = (in_array('has_headers', $post['Checkboxes']) && isset($post['has_headers']))
         ? 1
         : 0;

      $maxfilesize_bytes = Gdn_Upload::UnformatFileSize(C('Garden.Upload.MaxFileSize'));

      // Validate files
      $allowed_files = array('csv');
      $files = array();
      // Clean up file array, because PHP sends multiples files in a strange
      // array, also, filter out bad files.
      for ($i = 0, $l = count($import_files['error']); $i < $l; $i++) {
         if (!$import_files['error'][$i]
         && $import_files['size'][$i] > 0
         && $import_files['size'][$i] <= $maxfilesize_bytes
         && in_array(pathinfo($import_files['name'][$i], PATHINFO_EXTENSION), $allowed_files)) {

            // Create destination path
            $destination_dir = PATH_UPLOADS . '/csv';
            TouchFolder($destination_dir);
            $path = $destination_dir . '/' . $import_files['name'][$i];
            if (move_uploaded_file($import_files['tmp_name'][$i], $path)) {
               // All good, so add to definitive file list.
               $files[$i] = $path;
            }
         } else {
            $results['fail'][$import_files['name'][$i]] = "Invalid file (too large or not a CSV)";
         }
      }

      $db_table = $this->database_prefix . $this->table_name;
      $pdo = Gdn::Database()->Connection();

      // Truncate table before using it.
      Gdn::Database()->Query("TRUNCATE TABLE $db_table;");

      foreach ($files as $file) {
         // Get escaped EOL sequence to break on. No need to preg_quote, or
         // quote string with pdo, as already quoted.
         $line_termination = $this->getEOLFormat($file);

         // Quote strings for SQL
         $file_quoted = $pdo->quote($file);

         // Determine if file has standard or non-standard line endings
         $sql = "
            LOAD DATA INFILE $file_quoted
               INTO TABLE
                  $db_table
               FIELDS TERMINATED BY ','
               ENCLOSED BY '\"'
               ESCAPED BY '\\\\'
               LINES TERMINATED BY '$line_termination'
               IGNORE $ignore_line LINES
                  (email, username, status)
               SET completed = 0
         ";

         // Use filename for results key
         $filename = pathinfo($file, PATHINFO_BASENAME);

         // Result will contain number of affected rows.
         $rows_affected = $pdo->exec($sql);
         if ($rows_affected > 0) {
            $results['success'][$filename] = $rows_affected;
         } else {
            $results['fail'][$filename] = 'Improperly formatted CSV file';
         }
      }

      return $results;
   }


   /**
    * Provide function with a file pointer or file path, determine whether
    * it has nix, mac, or win line endings, and then return the line
    * ending type. Note that it returns a non-expandable (single-quotes)
    * version of the EOL character sequence.
    *
    * @param mixed $file_path Either a file pointer or path
    * @return non-expandable character sequence
    */
   public function getEOLFormat($file_path = '') {

      $fp = '';
      // If file pointer passed, use it, otherwise get it.
      if (is_resource($file_path)) {
         $fp = $file_path;
      } elseif (is_readable($file_path)) {
         $fp = fopen($file_path, 'r');
      }

      $line = '';
      if (is_resource($fp)) {
         $line = fgets($fp);
         fclose($fp);
         unset($fp);
      }

      // Non-expandable EOL sequences.
      $eol_win = '\r\n';
      $eol_mac = '\r'; // Lagacy Mac, probably won't be used much.
      $eol_nix = $eol = '\n'; // And set default

      // Would be nice if could dynamically expand single-quoted strings, so
      // the EOL sequences don't have to be repeated.
      if (strpos($line, "\r\n") !== false) {
         $eol = $eol_win;
      } elseif (strpos($line, "\r") !== false) {
         $eol = $eol_mac;
      } elseif (strpos($line, "\n") !== false) {
         $eol = $eol_nix;
      }

      return $eol;
   }

   /**
    * Loop through the bulk_users_import table and create the members and send
    * out emails.
    *
    * @param SettingsController $sender
    */
   public function processUploadedData($sender) {
      $sender->SetData('status', 'Incomplete');

      $bulk_user_importer_model = new Gdn_Model($this->table_name);
      $imported_users = $bulk_user_importer_model->GetWhere(array('completed' => 0))->ResultArray();

      // Will contain array of added user ids.
      $success = array();
      $fail = array();
      $user_model = new UserModel();

      foreach($imported_users as $user) {

         // Check if email in use.
         $check_email = $user_model->GetWhere(array(
             'Email' => $user['email']
         ))->FirstRow('array');

         $unique_email = (count($check_email['Email']))
            ? false
            : true;

         // Check if username in use.
         $check_name = $user_model->GetWhere(array(
             'Name' => $user['username']
         ))->FirstRow('array');

         $unique_name = (count($check_name['Name']))
            ? false
            : true;

         // Add random string to end of name, if not unique. User can log in
         // and change it afterwards.
         if (!$unique_name) {
            $user['username'] .= '_' . mt_rand(1,500);
         }

         // If email is already in DB, get the UserID
         if (!$unique_email) {
            $user_id = $check_email['UserID'];
         }

         $temp_password = sha1($user['email'] . time());

         // If email is unique, definitely add them, otherwise they just get
         // the welcome email and password reset email.
         if ($unique_email) {
            // Create new user.
            $user_id = $user_model->Save(array(
                'Name' => $user['username'],
                'Password' => $temp_password, // This will get reset
                'Email' => $user['email']
            ));

            if ($user_id) {
               $success[$user_id] = $user['email'];
            } else {
               $fail[] = $user['email'];
            }
         } else {
            // email already exists
            $success[$user_id] = $user['email'];
         }

         // Email successfully added users, and users who were in the CSV file
         // but already had an account (according to email) with forum.
         $user_model->SendWelcomeEmail($user_id, ''); // Don't send temp password
         $user_model->PasswordRequest($user['email']); // Reset current temp password
      }

      $total_success = count($success);
      $total_fail = count($fail);

      if ($total_success || $total_fail) {
         $feedback = 'Processing complete: ' . $total_success . ' users added, ' . $total_fail . ' users skipped (may already exist, or data was corrupt).';
         $sender->SetData('status', 'Complete');
         $sender->JsonTarget('#process-csvs', $feedback, 'ReplaceWith');
      } else {
         $sender->InformMessage('There was a problem processing the data.');
      }
   }
}
