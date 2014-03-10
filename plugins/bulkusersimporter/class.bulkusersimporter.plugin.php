<?php if(!defined('APPLICATION')) die();

$PluginInfo['bulkusersimporter'] = array(
   'Name' => 'Bulk Users Importer',
   'Description' => 'Bulk users import with standardized CSV files.',
   'Version' => '1.0.4',
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

/**
 * TODO:
 * - approximate number of jobs necessary to complete an import.
 * - estimate time per job to process.
 */
class BulkUsersImporterPlugin extends Gdn_Plugin {

   private $database_prefix;
   private $table_name = 'BulkUsersImporter';

   // Will contain whitelist of allowed roles.
   private $allowed_roles = array();

   // Grab maximum of 1000 rows, or timeout after 20 seconds--whichever
   // happens first.
   // It will be asynchronously iterated over until there are no more records.
   // Modify these values to change how many requests will be sent to server
   // until job is complete.
   public $limit = 1000;
   public $timeout = 20;

   public function __construct() {
      $this->database_prefix = Gdn::Database()->DatabasePrefix;

      // Create whitelist of allowed roles.
      // Will be 'Guest', 'Unconfirmed', 'Applicant', 'Member', 'Administrator',
      // 'Moderator', and any custom roles.
      $role_model = new Gdn_Model('Role');
      $allowed_roles = $role_model->Get('Name')->ResultArray();
      if ($allowed_roles) {
         $this->allowed_roles = array_column($allowed_roles, 'Name', 'RoleID');
      }

      // Add Banned to list (in future add Verified and Confirmed).
      array_push($this->allowed_roles, 'Banned');
   }

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      Gdn::Structure()
         ->Table($this->table_name)
         ->PrimaryKey('ImportID')
         ->Column('Email', 'varchar(200)', true, 'index')
         ->Column('Username', 'varchar(50)', true)
         ->Column('Status', 'varchar(50)', true)
         ->Column('Completed', 'tinyint(1)', 0, 'index')
         ->Column('Error', 'text', true)
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
            $sender->AddDefinition('maxfilesizebytes', Gdn_Upload::UnformatFileSize(C('Garden.Upload.MaxFileSize')));
            $sender->SetData('allowed_roles', $this->allowed_roles);
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

      // Create destination path
      $destination_dir = PATH_UPLOADS . '/csv';
      TouchFolder($destination_dir);

      // Get request data
      $import_files = Gdn::Request()->GetValueFrom(Gdn_Request::INPUT_FILES, 'import_files', FALSE);
      $post = Gdn::Request()->Post();

      // Validate files
      $allowed_files = array('csv');
      $files = array();

      // File that is included by URL, so download.
      if (!count(array_filter($import_files['size']))) {
         $url = $post['import_url'];
         if (in_array(pathinfo($url, PATHINFO_EXTENSION), $allowed_files)) {
            $path = $destination_dir . '/bulkimport' . time() . '.csv';
            file_put_contents($path, fopen($url, 'r'));
            $files[0] = $path;
         }
      }

      // Determine if CSV has headers by getting state of checkbox
      $ignore_line = (in_array('has_headers', $post['Checkboxes']) && isset($post['has_headers']))
         ? 1
         : 0;

      $maxfilesize_bytes = Gdn_Upload::UnformatFileSize(C('Garden.Upload.MaxFileSize'));

      // Clean up file array, because PHP sends multiples files in a strange
      // array, also, filter out bad files.
      for ($i = 0, $l = count(array_filter($import_files['size'])); $i < $l; $i++) {
         if (!$import_files['error'][$i]
         && $import_files['size'][$i] > 0
         && $import_files['size'][$i] <= $maxfilesize_bytes
         && in_array(pathinfo($import_files['name'][$i], PATHINFO_EXTENSION), $allowed_files)) {
            $path = $destination_dir . '/' . $import_files['name'][$i];
            if (move_uploaded_file($import_files['tmp_name'][$i], $path)) {
               // All good, so add to definitive file list.
               $files[$i] = $path;
            }
         } else {
            $results['fail'][$import_files['name'][$i]] = "Invalid file (too large or not a CSV)";
         }
      }

      if (count($files)) {

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
               LOAD DATA LOCAL INFILE $file_quoted
                  INTO TABLE
                     $db_table
                  FIELDS TERMINATED BY ','
                  ENCLOSED BY '\"'
                  ESCAPED BY '\\\\'
                  LINES TERMINATED BY '$line_termination'
                  IGNORE $ignore_line LINES
                     (Email, Username, Status)
                  SET completed = 0
            ";

            // Use filename for results key
            $filename = pathinfo($file, PATHINFO_BASENAME);

            // There would only be one, so loop is inconsequential.
            if (isset($url) && $url != '') {
               $filename = $url;
            }

            // Result will contain number of affected rows.
            $rows_affected = $pdo->exec($sql);
            if ($rows_affected > 0) {
               $results['success'][$filename] = $rows_affected;
            } else {
               $results['fail'][$filename] = 'Improperly formatted CSV file';
            }

            // Delete the file
            unlink($file);
         }
      }

      return $results;
   }

   /**
    * Loop through the bulk_users_import table and create the members and send
    * out emails.
    *
    * @param SettingsController $sender
    */
   public function processUploadedData($sender) {
      $sender->SetData('status', 'Incomplete');

      // Get POST 'debug' value, because if it's
      // in debug, it will not send emails. Called it 'debug' in case in future
      // I want to expand on the functionality of debug mode. The actual
      // label for the checkbox states, "Do not send an email to successfully
      // imported users." It's either 1 or 0.
      $debug_mode = $sender->Request->Post('debug');
      $debug_mode = (isset($debug_mode))
         ? (int) $sender->Request->Post('debug')
         : 0;

      $bulk_user_importer_model = new Gdn_Model($this->table_name);
      $imported_users = $bulk_user_importer_model->GetWhere(array('completed' => 0), '', 'asc', $this->limit)->ResultArray();
      $user_model = new UserModel();

      // Collect error messages, concatenate them at end.
      $error_messages = array();

      // For byte-size processing
      $start_time = time();
      $processed = 0;

      foreach($imported_users as $user) {
         $processed++;
         $send_email = false; // Default
         $user_id = false; // Default
         $user['Username'] = trim($user['Username']);
         $user['Email'] = trim($user['Email']);

         // Grab first import id in job.
         if (!isset($first_import_id)) {
            $first_import_id = $user['ImportID'];
         }

         // Zenimax mentioned that usernames will contain all
         // variety of characters, so be very loose with the validation.
         $regex_length = C("Garden.User.ValidationLength","{3,20}");
         $regex_username = "/^(.)$regex_length$/";

         // Originally had ValidateUsername, but often the regex it was
         // validating with was too strict.
         // C("Garden.User.ValidationRegex","\d\w_");
         if (!isset($user['Username']) && !preg_match($regex_username, $user['Username'])) {
            $error_messages[$processed]['username'] = 'Invalid username on line ' . $user['ImportID'] . ': ' . $user['Username'] . '.';
            //$sender->SetJson('import_id', 0);
            //$sender->SetJson('error_message', $error_messages[$processed]['username']);
            //break;
         }

         if (!ValidateEmail($user['Email'])) {
            $error_messages[$processed]['email'] = 'Invalid email on line '. $user['ImportID'] .': '. $user['Email'] .'.';
            //$sender->SetJson('import_id', 0);
            //$sender->SetJson('error_message', $error_messages[$processed]['email']);
            //break;
         }

         // Get role ids based off of their name, and check if any invalid
         // roles were passed.
         $status = $this->roleNamesToInts($user['Status']);
         if (!count($status['invalid_roles'])) {
            $role_ids = $status['role_ids'];
            $banned = $status['banned'];
         } else {
            // No roles
            $plural_status = PluralTranslate(count($status['invalid_roles']), 'status', 'statuses');
            $error_messages[$processed]['role'] = "Invalid $plural_status on line " . $user['ImportID'] . ': ' . implode(', ', $status['invalid_roles']) . '.';
            //$sender->SetJson('import_id', 0);
            //$sender->SetJson('error_message', $error_messages[$processed]['role']);
            //break;
         }

         if (!isset($error_messages[$processed]['username'])) {
            // Check if username in use.
            $check_name = $user_model->GetWhere(array(
                'Name' => $user['Username']
            ))->FirstRow('array');

            // Username controls, so if it exists, update the info, including
            // email, otherwise create new user.
            if (count($check_name['Name'])) {
               $send_email = false;
               // Update the user.
               $user_id = $user_model->Save(
                  array(
                   'UserID' => $check_name['UserID'],
                   'Email' => $user['Email'],
                   'RoleID' => $role_ids,
                   'Banned' => $banned
               ), array(
                   'SaveRoles' => true
               ));
            } else {
               $send_email = true;
               // Create new user. The method seems to rely on Captcha keys, so
               // this may error out due to none being passed to it.
               $temp_password = sha1($user['Email'] . time());

               // Create new user
               $user_id = $user_model->Register(
                  array(
                   'Name' => $user['Username'],
                   'Password' => $temp_password, // This will get reset
                   'Email' => $user['Email'],
                   'RoleID' => $role_ids,
                   'Banned' => $banned
               ), array(
                   'SaveRoles' => true,
                   'CheckCaptcha' => false
               ));
            }
         }

         $complete_code = 0; // Error code
         if ($user_id) {
            $complete_code = 1;
            if (isset($error_messages[$processed]['role'])) {
               $complete_code = 2;
            }
         } else {
            $complete_code = 2;
            $error_messages[$processed]['db'] = $user_model->Validation->ResultsText();
         }

         // Error message to get logged in DB, if any.
         $error_string = (isset($error_messages[$processed]))
            ? implode(' ', $error_messages[$processed])
            : '';

         // Log errors in DB--will be cleared on next import.
         $bulk_user_importer_model->Update(
            array(
               'Completed' => $complete_code,
               'Error' => $error_string
            ),
            array(
               'ImportID' => $user['ImportID']
            )
         );
         $user_model->Validation->Results(true);

         // Email successfully added users, but not users who already had an
         // account (according to username) with forum.
         if ($send_email && $complete_code == 1) {
            if ($debug_mode == 0) {
               $this->PasswordRequest($user['Email']); // Reset current temp password
            }
         }

         $sender->SetJson('import_id', $user['ImportID']);
         $sender->SetJson('first_import_id', $first_import_id);
         // If timeout reached, end current operation. It will be called
         // again immediately to continue processing.
         if (time() - $start_time >= $this->timeout) {
            break;
         }
      }

      $total_fail = count($error_messages);
      $total_success = $processed - $total_fail;

      if ($total_success < 0) {
         $total_success = 0;
      }

      if ($total_success || $total_fail) {
         $sender->SetJson('feedback', 'Latest job processed up to row ' . $user['ImportID']);
      } else {
         $sender->InformMessage('There was a problem processing the data.');
      }

      // Send error dumps.
      if ($total_fail) {
         // Get dumps from DB relevant to this job.
         $bulk_error_dump = $bulk_user_importer_model->GetWhere(
            array(
             'ImportID >=' => $first_import_id,
             'Completed' => 2
            ),
            '',
            'asc',
            $this->limit
         )->ResultArray();
         $errors = array_column($bulk_error_dump, 'Error');
         $sender->SetJson('bulk_error_dump', json_encode($errors));
      }

      $sender->Render('Blank', 'Utility', 'Dashboard');
   }

   // Send custom email reset, copied from method in usermodel, with slight
   // mod--changing the email message.
   public function PasswordRequest($Email) {
      if (!$Email) {
         return FALSE;
      }

      $user_model = new UserModel();

      $Users = $user_model->GetWhere(array('Email' => $Email))->ResultObject();
      if (count($Users) == 0) {
         // Check for the username.
         $Users = $user_model->GetWhere(array('Name' => $Email))->ResultObject();
      }

      if (count($Users) == 0) {
         $user_model->Validation->AddValidationResult('Name', "Couldn't find an account associated with that email/username.");
         return FALSE;
      }

      $NoEmail = TRUE;

      foreach ($Users as $User) {
         if (!$User->Email) {
            continue;
         }
         $Email = new Gdn_Email(); // Instantiate in loop to clear previous settings
         $PasswordResetKey = BetterRandomString(20, 'Aa0');
         $PasswordResetExpires = strtotime('+1 hour');
         $user_model->SaveAttribute($User->UserID, 'PasswordResetKey', $PasswordResetKey);
         $user_model->SaveAttribute($User->UserID, 'PasswordResetExpires', $PasswordResetExpires);
         $AppTitle = C('Garden.Title');
         $Email->Subject(sprintf(T('[%s] Forum Account Creation'), $AppTitle));
         $Email->To($User->Email);

         // Custom mesage for bulk importer.
         $message = '';
         $message .= "Hello,\n\n";
         $message .= "An account has been created for you at the $AppTitle forum.\n\n";
         $message .= "To activate your account, please follow this link:\n";
         $message .= ExternalUrl('/entry/passwordreset/'.$User->UserID.'/'.$PasswordResetKey) . "\n\n";
         $message .= "Please contact us if you have questions regarding this email.\n\n";
         $message .= "Sincerely,\n";
         $message .= $AppTitle;

         $Email->Message($message);
         $Email->Send();
         $NoEmail = FALSE;
      }

      if ($NoEmail) {
         $this->Validation->AddValidationResult('Name', 'There is no email address associated with that account.');
         return FALSE;
      }
      return TRUE;
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
    * Converts role names to their corresponding integers.
    *
    * Valid roles: Guest, Unconfirmed, Applicant, Member, Administrator,
    * Moderator
    *
    * Other valid: Banned
    *
    * @param string $role_names
    * @return mixed Will return array of valid role IDs or false if none.
    */
   public function roleNamesToInts($role_names) {
      if (!$role_names) {
         return false;
      }

      // Whitelist from DB.
      $allowed_roles = array_map('strtolower', $this->allowed_roles);

      $status = array(
          'role_ids' => array(),
          'invalid_roles' => array(),
          'banned' => 0,
          // These are not coded yet.
          'confirmed' => 1,
          'verified' => 1
      );

      $role_ids = array();

      if (is_string($role_names) && !is_numeric($role_names)) {
         // The $role_names are a colon-delimited list of role names.
         $role_names = array_map('trim', explode(':', $role_names));

         // Normalize the roles given, so lowercase all, and make sure the
         // DB query does the same.
         $role_names = array_map('strtolower', $role_names);

         foreach($role_names as $i => $role_name) {
            if (!in_array($role_name, $allowed_roles)) {
               $status['invalid_roles'][] = $role_names[$i];
               unset($role_names[$i]);
            }
         }

         if (count($role_names)) {
            $role_model = new Gdn_Model('Role');
            $role_ids = $role_model->SQL
               ->Select('r.RoleID')
               ->From('Role r')
               ->WhereIn('LOWER(r.Name)', $role_names)
               ->Get()->ResultArray();
            $role_ids = ConsolidateArrayValuesByKey($role_ids, 'RoleID');
         }
      }

      $status['role_ids'] = $role_ids;

      // Check if user is banned
      if (in_array('Banned', $role_names)) {
         $status['banned'] = 1;
      }

      return $status;
   }
}
