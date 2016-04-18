<?php if(!defined('APPLICATION')) die();

$PluginInfo['bulkusersimporter'] = array(
   'Name' => 'Bulk User Import',
   'Description' => 'Bulk user import with standardized CSV files. Send invites or directly insert new members.',
   'Version' => '1.2.3',
   'Author' => 'Dane MacMillan',
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
 * - Allow downloading the full error dump from table.
 *   - Instead of reporting line numbers, simply provide a dump of errors
 *     in a CSV file that can be be easily amended and uploaded.
 * - Consider multiple users operating the bulk importer. Do not just truncate
 *   the BulkUsersImporter table on every new upload.
 * - Create secondary table that will keep track of import sessions, which
 *   will be checked before uploading and truncating the main table, with
 *   option to resume from last aborted import.
 */

class BulkUsersImporterPlugin extends Gdn_Plugin {

   private $database_prefix;
   private $table_name = 'BulkUsersImporter';
   private $plugin_title = 'Bulk User Import';

   // Will contain whitelist of allowed roles.
   private $allowed_roles = array();

   /*
    * Grab maximum of 1000 rows, or timeout after 10 seconds--whichever
    * happens first. Setting threads means that the processing will be
    * additionally split up into parallel threads each working on their
    * own chunk of rows.
    * Note: read comment in construct about threads and the limit.
    *
    * @var int $limit
    */
   public $limit = 2000;

   /**
    * How long a job should run. Setting higher can result in server timeout.
    *
    * @var int $timeout
    */
   public $timeout = 10;

   /**
    * Note: more does not always mean faster.
    *
    * @var int $threads
    */
   public $threads = 5;

   /**
    * Number of times to retry a job before it's finally cancelled. This is
    * used if the server returns something other than a 200 response.
    *
    * @var int $retries
    */
   public $retries = 10;

   /**
    * How long to wait before the thread is retried.
    */
   public $retries_timeout_seconds = 10;

   /**
    * Username min and max lengths.
    *
    * @var array $username_limits
    */
   public $username_limits = array(
       'min' => 3,
       'max' => 40
   );

   public function __construct() {
      $this->database_prefix = Gdn::Database()->DatabasePrefix;

      // Adjust limit for threads. From what I've seen, having threads does not
      // really speed up the processing, aside from providing more real-time
      // progress updates. The processing power of the machine still determines
      // how many rows actually get processed, so if 1 thread can process
      // 1000 rows, 5 running in parallel will roughly process about 200 rows.
      // each. This is why the limit is being divided: most of it is never even
      // touched in the loop, so pull fewer rows into memory, which does has
      // performance benefits.
      // Adjust the above limit and threads variables accordingly.
      $this->limit = ceil($this->limit / $this->threads);

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
         ->Column('ThreadID', 'tinyint(1)', true, 'index')
         ->Column('Email', 'varchar(200)', true)
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
      $sender->AddCssFile('bulkusersimporter.css', 'plugins/bulkusersimporter');
      $sender->AddJsFile('bulkusersimporter.js', 'plugins/bulkusersimporter');

      // Render components pertinent to all views.
      $sender->SetData('Title', T($this->plugin_title));
      $sender->AddSideMenu();

      // Render specific component views.
      switch (true) {
         case in_array('upload', $args):
            // Handle upload and quickly parse file into DB.
            $results = $this->handleUploadInsert($sender);
            $sender->SetData('results', $results);
            $sender->SetData('_available_invites', $this->calculateAvailableInvites());
            $sender->AddDefinition('threads', $this->threads);
            $sender->AddDefinition('retries', $this->retries);
            $sender->AddDefinition('retries_timeout_seconds', $this->retries_timeout_seconds);
            $sender->Render('upload', '', 'plugins/bulkusersimporter');
            break;

         case in_array('process', $args):
            // Process inserted CSV data and create and email new users.
            $thread_id = Gdn::Request()->Post('thread_id', '');
            $this->processUploadedData($sender, $thread_id);
            $sender->Render('process', '', 'plugins/bulkusersimporter');
            break;

         default:
            $sender->AddDefinition('maxfilesizebytes', Gdn_Upload::UnformatFileSize(C('Garden.Upload.MaxFileSize')));
            $sender->SetData('allowed_roles', $this->allowed_roles);
            $sender->SetData('username_limits', $this->username_limits);
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
      $menu->AddLink('Import', T($this->plugin_title), '/settings/bulkusersimporter', 'Garden.Settings.Manage');
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

         // We need to allow local infile so we'll create a new db connection.
         $db_config = C('Database');
         $db_config['ConnectionOptions'][PDO::MYSQL_ATTR_LOCAL_INFILE] = TRUE;
         $db = new Gdn_Database($db_config);

         $pdo = $db->Connection();

         // Truncate table before using it.
         // TODO: In future, delete rows based on
         // current user performing import, as there could be multiple users
         // importing data simultaneously, which will cause the latest user
         // import to whipe out the previous one that is still active.
         $db->Query("TRUNCATE TABLE $db_table;");

         foreach ($files as $file) {
            // Get escaped EOL sequence to break on. No need to preg_quote, or
            // quote string with pdo, as already quoted.
            $line_termination = $this->getEOLFormat($file);

            // Quote strings for SQL
            $file_quoted = $pdo->quote($file);

            // Determine if file has standard or non-standard line endings.
            //
            // Added ThreadID with random number assigned between 1 and
            // n threads, to prevent race conditions, whereby multiple threads
            // will frequently select dupe rows that have not finished
            // processing.
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
                  SET
                     ThreadID = FLOOR(RAND() * ($this->threads - 1 + 1)) + 1,
                     Completed = 0
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
               $results['fail'][$filename] = 'Improperly formatted CSV file, or the server has not been configured for LOCAL INFILE use';
            }

            unlink($file);
         }
      }

      return $results;
   }

   public function calculateAvailableInvites() {
      $available_invites = 0;
      $user_id = Gdn::Session()->UserID;

      if ($user_id) {
         $user_model = Gdn::UserModel();
         $available_invites = $user_model->GetInvitationCount($user_id);
      }

      return $available_invites;
   }

   /**
    * Loop through the bulk_users_import table and create the members and send
    * out emails.
    *
    * @param SettingsController $sender
    */
   public function processUploadedData($sender, $thread_id = '') {
      $sender->SetData('status', 'Incomplete');

      // Use these to debug handling of different server responses.
      //http_response_code(500);
      //exit;

      // Collect error messages, concatenate them at end.
      $error_messages = array();

      // Get POST 'debug' value, because if it's
      // in debug, it will not send emails. Called it 'debug' in case in future
      // I want to expand on the functionality of debug mode. The actual
      // label for the checkbox states, "Do not send an email to successfully
      // imported users." It's either 1 or 0.
      $debug_mode = $sender->Request->Post('debug');
      $debug_mode = (isset($debug_mode))
         ? (int) $debug_mode
         : 0;

      // Determine if user will be sent an invitation, or inserted directly.
      // If no value provided, default is to send an invite.
      // Values: invite (default), insert
      $userin_modes = array('invite', 'insert', 'update');
      $userin_mode = $sender->Request->Post('userin');
      $userin_mode = (isset($userin_mode) && in_array($userin_mode, $userin_modes))
         ? $userin_mode
         : 'invite';

      // Get expiration of invite, if set.
      $invitation_expiration = 0; // No expiry
      $invite_expires = $sender->Request->Post('expires');
      if (isset($invite_expires)
      && strlen(trim($invite_expires)) > 0) {
         if (($expires_timestamp = strtotime($invite_expires)) !== false) {
            $invitation_expiration = $expires_timestamp;
         } else {
            // Value was provided, but it was not parseable by strtotime.
            $sender->SetJson('bad_expires', 1);
            $error_messages[] = Gdn_Format::PlainText('Expiry date of "'. $invite_expires . '" is invalid. Import will not begin.');
            $sender->SetJson('bulk_error_dump', json_encode($error_messages));
            return false;
         }
      }

      $bulk_user_importer_model = new Gdn_Model($this->table_name);
      $imported_users = $bulk_user_importer_model->GetWhere(array('ThreadID' => $thread_id, 'Completed' => 0), '', 'asc', $this->limit)->ResultArray();

      // Immediately block off the selection

      // Decide what model to use based on $userin_mode
      // Options are either invite or insert
      $user_model = Gdn::UserModel();
      $invitation_model = new InvitationModel();

      // Grab default roles just in case a blank provided. Not providing any
      // roles is not the same as providing an invalid role.
      $default_role_ids = C('Garden.Registration.DefaultRoles');

      // For byte-size processing
      $start_time = time();
      $processed = 0;

      // Begin processing each record.
      foreach($imported_users as $user) {

         $processed++;
         $send_email = false; // Default
         $user_id = false; // Default
         $invite_success = false; // For invitation model
         $user['Username'] = trim($user['Username']);
         $user['Email'] = trim($user['Email']);

         // Make sure these are reset on every iteration.
         $status = array();
         $role_ids = array();
         $banned = 0;

         // Grab first import id in job.
         if (!isset($first_import_id)) {
            $first_import_id = $user['ImportID'];
         }

         // Zenimax mentioned that usernames will contain all
         // variety of characters, so be very loose with the validation.
         //$regex_length = C("Garden.User.ValidationLength","{3,20}");
         $regex_length = '{'. $this->username_limits['min'] . ',' . $this->username_limits['max'] . '}';
         $regex_username = "/^(.)$regex_length$/";

         // Originally had ValidateUsername, but often the regex it was
         // validating with was too strict.
         // C("Garden.User.ValidationRegex","\d\w_");
         if (!isset($user['Username'])
         || $user['Username'] == '' // trimming down space names
         || !preg_match($regex_username, $user['Username'])) {
            $error_messages[$processed]['username'] = 'Invalid username: "' . $user['Username'] . '".';

            // Display more accurate error message for the length of usernames.
            $username_length = strlen($user['Username']);
            if ($username_length < $this->username_limits['min']) {
               $error_messages[$processed]['username'] .= ' Username is too short (min '. $this->username_limits['min'] . ' characters).';
            }

            if ($username_length > $this->username_limits['max']) {
               $error_messages[$processed]['username'] .= ' Username is too long (max '. $this->username_limits['max'] . ' characters).';
            }
         }

         if (!ValidateEmail($user['Email'])) {
            $error_messages[$processed]['email'] = 'Invalid email: "'. $user['Email'] .'". ';
         }

         // Get role ids based off of their name, and check if any invalid
         // roles were passed.
         $status = $this->roleNamesToInts($user['Status']);
         if (count($status['role_ids']) && !count($status['invalid_roles'])) {
            $role_ids = $status['role_ids'];
            $banned = $status['banned'];

            // For 'invite' userin_mode, a status of banned means the user does
            // not get invited. I'm not really sure why they would mark someone
            // as banned, but it's there. If they are banned, generate an error.
            if ($userin_mode == 'invite' && $banned == 1) {
               $error_messages[$processed]['role'] = 'User marked as banned. Invite will not be sent.';
            }
         } else {
            // No roles or invalid roles provided.

            $show_role_error = true;
            $status_list = 'no role provided';
            if (count($status['invalid_roles'])) {
               $status_list = implode(', ', $status['invalid_roles']);
            } else {
               $status['invalid_roles'] = array();
               $show_role_error = false;
            }

            // If invalid roles provided alongside valid roles, clear the
            // valid roles just to be safe.
            if (!empty($status['role_ids']) && count($status['role_ids'])) {
               $role_ids = $status['role_ids'] = array();
            }

            // If userin_mode is update--roles are required, so do not suppress
            // the error and assign a default--list the error.
            if ($userin_mode == 'update') {
               $show_role_error = true;
            }

            // If there are no invalid roles, that means no roles were provided
            // at all. Assign the defaults.
            if (!$show_role_error
            && is_array($default_role_ids)
            && count($default_role_ids)) {
               $role_ids = $status['role_ids'] = $default_role_ids;
            } else {
               $plural_status = PluralTranslate(count($status['invalid_roles']), 'role', 'roles');
               $error_messages[$processed]['role'] = "Invalid $plural_status: " . $status_list . '.';
            }
         }

         // Depending on value of $userin_mode, either insert the user directly
         // in the database, or send them an invite.
         switch ($userin_mode) {
            case 'insert':
               // TODO: look into these inconsistent error messages. They do not
               // always appear.
               //
               // [Line 62387] - The email you entered is in use by another member. (cheri.white@bethsoftasia.com,cheri.whitenub19_ESO2,Sanguine's Tester)
               // ^ This is fine, but it triggers some kind of cascade effect where
               // the next several rows are suddenly invalidated. THIS is what needs
               // to be resolved. I believe the comment below explains the solution.
               // [Line 62388] - Password is required. DateInserted is required.
               // ...
               // [Line 62709] - Password is required. DateInserted is required.
               // for the next n rows, directly after the email use error.
               // This happened on a Save. Not sure why those values would be
               // required on a Save. That information is already in the row.
               if (!isset($error_messages[$processed]['username'])
               && !isset($error_messages[$processed]['role'])) {
                  // Check if username in use.
                  $check_name = $user_model->GetWhere(array(
                      'Name' => $user['Username']
                  ))->FirstRow('array');

                  // Username controls, so if it exists, update the info, including
                  // email, otherwise create new user.
                  if (count($check_name['Name'])) {

                     // It's in this scope that the above errors happen, though
                     // inconsistently. If there happened to be a duplicate username
                     // attempting to get saved, $user_id would be false, because
                     // of UserModel:1584 $this->Validate($FormPostValues, $Insert)
                     // check, which returns false. By makign the username column
                     // unique in the table, this will prevent that from happening.
                     // It will also clean up a lot of redundancies. Still not sure
                     // why the error seems to cascade down and affect users for
                     // several iterations afterwards, then clears up. When that
                     // happens, the errors reported together are the Password
                     // and DateInserted rules. I've unapplied them for now, just
                     // to handle those exceptions. Though I'm certain they won't
                     // come up again after modifying the table column. Rows are
                     // still inserted correctly, just without the check.
                     $user_model->Validation->UnapplyRule('Password', 'Required');
                     $user_model->Validation->UnapplyRule('DateInserted', 'Required');

                     $send_email = false;

                     // Update the user.
                     $user_id = $user_model->Save(
                        array(
                         'UserID' => $check_name['UserID'],
                         'Name' => $check_name['Name'],
                         'Email' => $user['Email'],
                         'RoleID' => $role_ids,
                         'Banned' => $banned
                     ), array(
                         'SaveRoles' => true,
                         'FixUnique' => false // No, do not create a new user.
                     ));
                  } else {
                     $send_email = true;
                     // Create new user. The method seems to rely on Captcha keys, so
                     // this may error out due to none being passed to it.
                     $temp_password = sha1($user['Email'] . time());

                     $form_post_values = array(
                        'Name' => $user['Username'],
                        'Password' => $temp_password, // This will get reset
                        'Email' => $user['Email'],
                        'RoleID' => $role_ids,
                        'Banned' => $banned,
                        'DateInserted' => Gdn_Format::ToDateTime()
                     );

                     $form_post_options = array(
                        'SaveRoles' => true,
                        'CheckCaptcha' => false,
                        'ValidateSpam' => false
                     );

                     // This additional check is here to check if forum is in
                     // a registration mode that would require additional information
                     // not currently included in the import data, so for those
                     // instances just treat them as InsertForBasic, instead of
                     // calling Register, which will then use another method.
                     // Keep the logic here for now, even though InsertForBasic
                     // will be only method called. It was causing problems with
                     // spam filters when calling Register.
                     switch (strtolower(C('Garden.Registration.Method'))) {
                        case 'approval':
                        case 'invitation':
                        default:
                           $user_id = $user_model->InsertForBasic($form_post_values, GetValue('CheckCaptcha', $form_post_options, FALSE), $form_post_options);
                           //$user_id = $user_model->Register($form_post_values, $form_post_options);
                           break;
                     }
                  }
               }
               break;

            case 'update':

               $update_sucess = 0;

               // Email controls in the update, so invalidate a missing
               // username error, if exists.
               if (isset($error_messages[$processed]['username'])) {
                  unset($error_messages[$processed]['username']);
                  array_filter($error_messages[$processed]);
               }

               $user_row = array();
               // Check if email exists in user table.
               if (!isset($error_messages[$processed]['email'])) {
                  $user_row = (array) $user_model->GetByEmail($user['Email']);

                  if (!count(array_filter($user_row))) {
                     $error_messages[$processed]['email'] = 'Email does not exist: "'. $user['Email'] .'".';
                  }
               }

               // If they want to update the username, go ahead. If it's left
               // blank then just use the username already in the table.
               $username = $user['Username'];
               if (trim($username) == ''
               || !preg_match($regex_username, $username)) {
                  $username = $user_row['Name'];
               }

               // If there is a valid email and role(s), continue processing.
               if (!isset($error_messages[$processed]['email'])
               && !isset($error_messages[$processed]['role'])) {
                  $form_post_values = array(
                     'UserID' => $user_row['UserID'],
                     'Name' => $username,
                     'RoleID' => $role_ids,
                     'Banned' => $banned
                  );

                  $form_post_options = array(
                     'SaveRoles' => true,
                     'CheckCaptcha' => false,
                     'ValidateSpam' => false
                  );

                  $user_model->Validation->UnapplyRule('Email', 'Required');
                  $user_model->Validation->UnapplyRule('Password', 'Required');
                  $update_sucess = $user_model->Save($form_post_values, $form_post_options);
               }

               break;

            case 'invite':
            default:

               // Email controls for invites
               //
               // Invites do not require a valid username, so if there was an
               // error reported, no need to log it, so clear it.
               // For now usernames provided do not matter. They might in the
               // future, which is why I want to keep the username logic
               // above intact and simply clear the error here.
               $username = $user['Username'];
               if (isset($error_messages[$processed]['username'])) {
                  $username = '';
                  unset($error_messages[$processed]['username']);
               }

               // If there is a valid email, continue processing.
               if (!isset($error_messages[$processed]['email'])
               && !isset($error_messages[$processed]['role'])) {

                  // Determine if in can send email.
                  $send_invite_email = ($debug_mode == 0)
                     ? true
                     : false;

                  $form_post_values = array(
                     'Name' => $username,
                     'Email' => $user['Email'],
                     'RoleIDs' => dbencode($role_ids),
                     // For some reason this is only way for null to be set.
                     // If trying to assign variable to null, it ends up with
                     // first unix datetime possible (1970).
                     'DateExpires' => ($invitation_expiration === 0)
                        ? null
                        : Gdn_Format::ToDateTime($invitation_expiration)
                  );

                  // No point saving banned users to invitation table.
                  if (!$banned) {
                     $invite_success = $invitation_model->Save($form_post_values, $user_model, array(
                         'SendEmail' => $send_invite_email,
                         'Resend' => true
                     ));
                  }
               }

               break;
         }

         // Handle both insert and invite $userin_mode
         // This is so error handling is the same.
         $userin_model = (in_array($userin_mode, array('insert', 'update')))
            ? $user_model
            : $invitation_model;

         $complete_code = 0; // Error code
         if (($userin_mode == 'insert' && $user_id)
         || ($userin_mode == 'update' && $update_sucess)
         || ($userin_mode == 'invite' && $invite_success)) {
            $complete_code = 1;
            if (isset($error_messages[$processed]['role'])) {
               $complete_code = 2;
            }
         } else {
            $complete_code = 2;

            $db_error_string = $userin_model->Validation->ResultsText();
            if (strlen($db_error_string) > 2) {
               $error_messages[$processed]['db'] = $userin_model->Validation->ResultsText();
            }
         }
         $userin_model->Validation->Results(true);

         // Error message to get logged in DB, if any.
         $error_string = '';
         if (isset($error_messages[$processed])
         && is_array($error_messages[$processed])
         && count($error_messages[$processed])) {
            $error_string = '[Line ' . $user['ImportID'] . '] - ';
            foreach ($error_messages[$processed] as $error_message) {
               // Some of the error messages from ResultsText contain short
               // strings of gibberish, mainly " ." when there are no errors,
               // so strip those out.
               if (strlen($error_message) > 2) {
                  $error_string .= $error_message . ' ';
               }
            }
            $error_string = trim($error_string);
         }

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

         // Email successfully added users, but not users who already had an
         // account (according to username) with forum.
         // This is only for 'insert' $userin_mode
         if ($userin_mode == 'invite'
         && $send_email
         && $complete_code == 1) {
            if ($debug_mode == 0) {
               $this->PasswordRequest($user['Email']); // Reset current temp password
            }
         }

         // If timeout reached, end current operation. It will be called
         // again immediately to continue processing.
         if (time() - $start_time >= $this->timeout) {
            break;
         }
      }

      // Build content to send back to client. Determine success, etc.
      //
      // If multiple threads are in operation, but only a few rows are left,
      // the loop above will be skipped entirely, so handle situation.

      // If $processed is 0 by this point, it means there were no more rows
      // found for the given ThreadID, so make sure no requests for that
      // ThreadID are sent.

      $error_messages = array_filter($error_messages);
      $total_fail = count($error_messages);
      $total_success = $processed - $total_fail;

      if ($total_success < 0) {
         $total_success = 0;
      }

      $sender->SetJson('threadid-success-fail', $thread_id .'-'. $total_success .'-'. $total_fail);

      // Optionally just query the table for the number of processed rows,
      // but this should be just as accurate. JS will keep track of the rows.
      $sender->SetJson('job_rows_processed', $processed);

      // Send total rows processed so far.
      $total_rows_completed = $bulk_user_importer_model->GetCount(array(
          'Completed >' => 0
      ));
      $sender->SetJson('total_rows_completed', $total_rows_completed);

      // Send error dumps.
      if ($total_fail) {
         // Get dumps from DB relevant to this job.
         $bulk_error_dump = $bulk_user_importer_model->GetWhere(
            array(
             'ThreadID' => $thread_id,
             'ImportID >=' => $first_import_id,
             'Completed' => 2
            ),
            '',
            'asc',
            $this->limit
         )->ResultArray();

         $errors = array_column($bulk_error_dump, 'Error');

         // Just to be extra sure, make sure anything being output is clean.
         // I do let them know invalid emails, usernames, and roles, so they
         // could insert some nonsense strings, but even so, it would
         // only affect themselves.
         $errors = array_map(array('Gdn_Format', 'PlainText'), $errors);

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

      $user_model = Gdn::UserModel();

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

         // If roles have spaces in them, they can have quotation marks
         // around them, so strip those, if any.
         $role_names = array_map(function($role){
            return trim(trim($role, "'"), '"');
         }, $role_names);

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
            $role_ids = array_column($role_ids, 'RoleID');
         }
      }

      $status['role_ids'] = $role_ids;

      // Check if user is banned
      if (in_array('banned', $role_names)) {
         $status['banned'] = 1;
      }

      return $status;
   }
}
