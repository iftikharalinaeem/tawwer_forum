<?php if (!defined('APPLICATION')) exit();

/**
 * Simple Vanilla API 
 * 
 * Changes:
 *  1.0        Initial Release
 *  1.1        Versioning overhaul
 *  1.2        Authentication overhaul
 * 
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['SimpleAPI'] = array(
   'Name' => 'Simple API',
   'Description' => "Provides simple access_token API access to the forum.",
   'Version' => '1.2',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Tim Gunter',
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://about.me/timgunter',
   'SettingsUrl' => '/settings/api'
);

class SimpleAPIPlugin extends Gdn_Plugin {
   
   /**
    * Mapper tool
    * @var SimpleApiMapper
    */
   public $Mapper = NULL;
   
   /**
    * Intercept POST data
    * 
    * This method inspects and potentially modifies incoming POST data to 
    * facilitate simpler API development. 
    * 
    * For example, passing a KVP of:
    *    User.Email = tim@vanillaforums.com
    * would result in the corresponding UserID KVP being added to the POST data:
    *    UserID = 2387
    * 
    * @param array $Post
    * @param boolean $ThrowError
    * @return boolean
    * @throws Exception 
    */
   public static function TranslatePost(&$Post, $ThrowError = TRUE) {
      
      $Errors = array();
      $PostData = $Post;
      $Post = array();
      
      // Loop over every KVP in the POST data
      foreach ($PostData as $Key => $Value) {
         if ($Key == 'access_token') continue;
            
         // Unscrew PHP encoding of periods in POST data
         $Key = str_replace('_', '.', $Key);
         $Post[$Key] = $Value;
         
      }
      unset($PostData);
      
      // Loop over every KVP in the POST data
      foreach ($Post as $Key => $Value) {
         
         $TranslateErrors = self::TranslateField($Post, $Key, $Value);
         if (is_array($TranslateErrors))
            $Errors = array_merge($Errors, $TranslateErrors);
         
      }
      
      if (count($Errors) > 0) {
         if ($ThrowError) {
            throw new Exception(implode(' ', $Errors), 400);
         } else {
            return $Errors;
         }
      }
      
      return TRUE;
   }

   /**
    * Intercept GET data
    * 
    * This method inspects and potentially modifies incoming GET data to 
    * facilitate simpler API development. 
    * 
    * For example, passing a KVP of:
    *    User.Email = tim@vanillaforums.com
    * would result in the corresponding UserID KVP being added to the GET data:
    *    UserID = 2387
    * 
    * @param array $Get
    * @param boolean $ThrowError
    * @return boolean
    * @throws Exception 
    */
   public static function TranslateGet(&$Get, $ThrowError = TRUE) {
      
      $Errors = array();
      $GetData = $Get;
      $Get = array();
      
      // Loop over every KVP in the POST data
      foreach ($GetData as $Key => $Value) {
         if ($Key == 'access_token') continue;
            
         // Unscrew PHP encoding of periods in POST data
         $Key = str_replace('_', '.', $Key);
         $Get[$Key] = $Value;
         
      }
      unset($GetData);
      
      // Loop over every KVP in the GET data
      foreach ($Get as $Key => $Value) {
         
         $TranslateErrors = self::TranslateField($Get, $Key, $Value);
         if (is_array($TranslateErrors))
            $Errors = array_merge($Errors, $TranslateErrors);
         
      }
      
      if (count($Errors) > 0) {
         if ($ThrowError) {
            throw new Exception(implode(' ', $Errors), 400);
         } else {
            return $Errors;
         }
      }
      
      return TRUE;
   }
   
   /**
    * Translate a single field in an array
    * 
    * @param array $Data
    * @param string $Field
    * @param string $Value
    */
   protected static function TranslateField(&$Data, $Field, $Value) {
      $Errors = array();
      $SupportedTables = array('Badge', 'Category', 'Rank', 'Role', 'User');
      
      try {
         
         // If the Key is dot-delimited, inspect it for potential munging
         if (strpos($Field, '.') !== FALSE) {
            
            list($FieldPrefix, $ColumnLookup) = explode('.', $Field, 2);
            
            $TableName = $FieldPrefix;
            
            if (StringEndsWith($FieldPrefix, 'User'))
               $TableName = 'User';
            if (StringEndsWith($FieldPrefix, 'Users'))
               $TableName = 'Users';
            
            // Limit to supported tables
            $TableAllowed = TRUE;
            $Multi = FALSE;
            if (!in_array($TableName, $SupportedTables)) {
               $TableAllowed = FALSE;
               
               // First check if this is a Multi request
               if ($SingularTableName = StringEndsWith($TableName, 's', FALSE, TRUE)) {
                  if (in_array($SingularTableName, $SupportedTables)) {
                     $TableName = $SingularTableName;
                     $FieldPrefix = StringEndsWith($FieldPrefix, 's', FALSE, TRUE);
                     $TableAllowed = TRUE;
                     $Multi = TRUE;
                  }
               }
            }
            
            if (!$TableAllowed)
               return;
               //throw new Exception("Table {$TableName} is not supported by SmartID", 405);

            // We desire the 'ID' root field
            $LookupField = "{$FieldPrefix}ID";

            // Don't override an existing desired field
            if (isset($Data[$LookupField]) && !$Multi)
               return;

            $LookupFieldValue = NULL;
            $LookupKey = "{$TableName}.{$ColumnLookup}";
            $LookupMethod = 'simple';

            if ($ColumnLookup == 'ID')
               $LookupMethod = 'noop';

            if ($LookupKey == 'User.ForeignID')
               $LookupMethod = 'custom';
            
            if ($Multi)
               $Value = explode(',', $Value);
            $Value = (array)$Value;
            
            foreach ($Value as $MultiValue) {
               switch ($LookupMethod) {

                  // Noop lookup
                  case 'noop':
                     $LookupFieldValue = $MultiValue;
                     break;

                  // Simple table.field lookup types
                  case 'simple':
                     $MatchRecords = Gdn::SQL()->GetWhere($TableName, array(
                        $ColumnLookup => $MultiValue
                     ));
                     if (!$MatchRecords->NumRows())
                        throw new Exception(self::NotFoundString($FieldPrefix, $MultiValue), 404);

                     if ($MatchRecords->NumRows() > 1)
                        throw new Exception(sprintf('Multiple %ss found by %s for "%s".', T('User'), $ColumnLookup, $MultiValue), 409);

                     $Record = $MatchRecords->FirstRow(DATASET_TYPE_ARRAY);
                     $LookupFieldValue = GetValue($LookupField, $Record);
                     break;

                  // Custom lookup types
                  case 'custom':

                     // Special lookup for SSO users
                     if ($LookupKey == 'User.ForeignID') {
                        if (strpos($MultiValue, ':') === FALSE)
                           throw new Exception("Malformed ForeignID object '{$MultiValue}'. Should be '[provider key]:[foreign id]'.", 400);

                        $ProviderParts = explode(':', $MultiValue, 2);
                        $ProviderKey = $ProviderParts[0];
                        $ForeignID = $ProviderParts[1];

                        // Check if we have a provider by that key
                        $ProviderModel = new Gdn_AuthenticationProviderModel();
                        $Provider = $ProviderModel->GetProviderByKey($ProviderKey);
                        if (!$Provider)
                           throw new Exception(self::NotFoundString('Provider', $ProviderKey), 404);

                        // Check if we have an associated user for that ForeignID
                        $UserAssociation = Gdn::Authenticator()->GetAssociation($ForeignID, $ProviderKey, Gdn_Authenticator::KEY_TYPE_PROVIDER);
                        if (!$UserAssociation)
                           throw new Exception(self::NotFoundString('User', $MultiValue), 404);

                        $LookupFieldValue = GetValue($LookupField, $UserAssociation);
                     }

                     break;
               }

               if (!is_null($LookupFieldValue)) {
                  if ($Multi) {
                     if (!isset($Data[$LookupField])) $Data[$LookupField] = array();
                     if (!is_array($Data[$LookupField])) $Data[$LookupField] = array($Data[$LookupField]);
                     $Data[$LookupField][] = $LookupFieldValue;
                  } else {
                     $Data[$LookupField] = $LookupFieldValue;
                  }
               }
            }

         } elseif (StringEndsWith($Field, 'Category')) {
            // Translate a category column.
            $Px = StringEndsWith($Field, 'Category', TRUE, TRUE);
            $Column = $Px.'CategoryID';
            if (isset($Data[$Column]))
               return;

            $Category = CategoryModel::Categories($MultiValue);
            if (!$Category)
               throw new Exception(self::NotFoundString('Category', $MultiValue), 404);
            
            $Data[$Column] = (string)$Category['CategoryID'];
         }
         
      } catch (Exception $Ex) {
         $Errors[] = $Ex->getMessage();
      }
      
      return $Errors;
   }
   
   protected static function NotFoundString($Code, $Item) {
      return sprintf('%1$s "%2$s" not found.', T($Code), $Item);
   }
   
   /**
    * API Translation hook
    * 
    * This method fires before the dispatcher inspects the request. It allows us
    * to translate incoming API requests according their version specifier.
    * 
    * If no version is specified, or if the specified version cannot be loaded,
    * strip the version and directly pass the resulting URI without modification.
    * 
    * @param Gdn_Dispatcher $Sender 
    */
   public function Gdn_Dispatcher_AppStartup_Handler($Sender) {
      
      $IncomingRequest = Gdn::Request()->RequestURI();
      
      // Detect a versioned API call
      
      $MatchedAPI = preg_match('`^/?api/(v[\d\.]+)/(.+)`i', $IncomingRequest, $URI);
      
      if (!$MatchedAPI)
         return;
      
      $APIVersion = $URI[1];
      $APIRequest = $URI[2];
      
      // Check the version slug
      
      try {
         
         $ClassFile = "class.api.{$APIVersion}.php";
         $PluginInfo = Gdn::PluginManager()->GetPluginInfo('SimpleAPI');
         $PluginPath = $PluginInfo['PluginRoot'];
         $MapperFile = CombinePaths(array($PluginPath, 'library', $ClassFile));
         
         if (!file_exists($MapperFile)) throw new Exception('No such API Mapper');
         
         require_once($MapperFile);
         if (!class_exists('ApiMapper')) throw new Exception('API Mapper is not available after inclusion');
         
         $this->Mapper = new ApiMapper();
         
         $this->EventArguments['Mapper'] = &$this->Mapper;
         $this->FireEvent('Mapper');
         
         // Lookup the mapped replacement for this request
         $MappedURI = $this->Mapper->Map($APIRequest);
         if (!$MappedURI) throw new Exception('Unable to map request');
         
         // Apply the mapped replacement
         Gdn::Request()->WithURI($MappedURI);
         
         // Authenticate & prepare data
         $this->PrepareAPI($Sender);
         
      } catch (Exception $Ex) {
         
         Gdn::Request()->WithURI($APIRequest);
         
      }
      
   }
   
   /**
    * 
    * @param type $Sender
    * @throws Exception
    */
   protected function PrepareAPI($Sender) {
      $AccessToken = GetValue('access_token', $_GET, NULL);
         
      if ($AccessToken !== NULL) {
         if ($AccessToken === C('Plugins.SimpleAPI.AccessToken')) {
            // Check for only-https here because we don't want to check for https on json calls from javascript.
            $OnlyHttps = C('Plugins.SimpleAPI.OnlyHttps');
            if ($OnlyHttps && strcasecmp(Gdn::Request()->Scheme(), 'https') != 0) {
               throw new Exception(T('You must access the API through https.'), 401);
            }

            $UserID = C('Plugins.SimpleAPI.UserID');
            $User = FALSE;
            if ($UserID)
               $User = Gdn::UserModel()->GetID($UserID);
            if (!$User)
               $UserID = Gdn::UserModel()->GetSystemUserID();

            Gdn::Session()->Start($UserID, FALSE, FALSE);
         } else {
            if (!Gdn::Session()->IsValid())
               throw new Exception(T('Invald Access Token'), 401);
         }
      }

      if (strcasecmp(GetValue('contenttype', $_GET, ''), 'json') == 0 || strpos(GetValue('CONTENT_TYPE', $_SERVER, NULL), 'json') !== FALSE) {
         $Post = file_get_contents('php://input');

         if ($Post)
            $Post = json_decode($Post, TRUE);
         else
            $Post = array();
      } else {
         $Post = Gdn::Request()->Post();         
      }

      // Translate POST data
      self::TranslatePost($Post);
      Gdn::Request()->SetRequestArguments(Gdn_Request::INPUT_POST, $Post);
      $_POST = $Post;

      // Translate GET data
      self::TranslateGet($_GET);
      Gdn::Request()->SetRequestArguments(Gdn_Request::INPUT_GET, $_GET);
   }
   
   /**
    * Apply output filter
    * 
    * @param Gdn_Controller $Sender
    */
   public function Gdn_Controller_Finalize_Handler($Sender) {
      if ($this->Mapper instanceof SimpleApiMapper)
         $this->Mapper->Filter($Sender->EventArguments['Data']);
   }
   
   /**
    * API Settings
    * 
    * @param SettingsController $Sender
    * @param array $Args 
    */
   public function SettingsController_API_Create($Sender, $Args) {
      $Sender->Permission('Garden.Settings.Manage');
      
      if ($Sender->Form->IsPostBack()) {
         $Save = array(
            'Plugins.SimpleAPI.AccessToken' => $Sender->Form->GetFormValue('AccessToken'),
            'Plugins.SimpleAPI.UserID' => NULL,
            'Plugins.SimpleAPI.OnlyHttps' => (bool)$Sender->Form->GetFormValue('OnlyHttps')
         );
         
         
         // Validate the settings.
         if (!ValidateRequired($Sender->Form->GetFormValue('AccessToken'))) {
            $Sender->Form->AddError('ValidateRequired', 'Access Token');
         }
         
         // Make sure the user exists.
         $Username = $Sender->Form->GetFormValue('Username');
         if (!ValidateRequired($Username))
            $Sender->Form->AddError('ValidateRequired', 'User');
         else {
            $User = Gdn::UserModel()->GetByUsername($Username);
            if (!$User)
               $Sender->Form->AddError('@'.self::NotFoundString('User', htmlspecialchars($Username)));
            else
               $Save['Plugins.SimpleAPI.UserID'] = GetValue('UserID', $User);
         }
         
         if ($Sender->Form->ErrorCount() == 0) {
            // Save the data.
            SaveToConfig($Save);
            
            $Sender->InformMessage('Your changes have been saved.');
         }
      } else {
         // Get the data.
         $Data = array(
             'AccessToken' => C('Plugins.SimpleAPI.AccessToken'),
             'UserID' => C('Plugins.SimpleAPI.UserID', Gdn::UserModel()->GetSystemUserID()),
             'OnlyHttps' => C('Plugins.SimpleAPI.OnlyHttps'));
         
         $User = Gdn::UserModel()->GetID($Data['UserID'], DATASET_TYPE_ARRAY);
         if ($User) {
            $Data['Username'] = $User['Name'];
         } else {
            $User = Gdn::UserModel()->GetID(Gdn::UserModel()->GetSystemUserID(), DATASET_TYPE_ARRAY);
            $Data['Username'] = $User['Name'];
            $Data['UserID'] = $User['UserID'];
         }
         
         $Sender->Form->SetData($Data);
      }
      
      $Sender->SetData('Title', 'API Settings');
      $Sender->AddSideMenu();
      $Sender->Render('Settings', '', 'plugins/SimpleAPI');
   }
   
   /**
    * Adds "API" menu option to the Forum menu on the dashboard.
    * 
    * @param Gdn_Controller $Sender 
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Site Settings', T('API'), 'settings/api', 'Garden.Settings.Manage');
   }
   
   /**
    * Plugin setup
    */
   public function Setup() {
      $this->Structure();
   }
   
   /**
    * Database structure 
    */
   public function Structure() {
      // Make sure the API user is set.
      $UserID = C('Plugins.SimpleAPI.UserID');
      if (!$UserID)
         $UserID = Gdn::UserModel()->GetSystemUserID();
      $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
      if (!$User)
         $UserID = Gdn::UserModel()->GetSystemUserID();
      
      // Make sure the access token is set.
      $AccessToken = C('Plugins.SimpleAPI.AccessToken');
      if (!$AccessToken)
         $AccessToken = md5(microtime());
      
      SaveToConfig(array(
          'Plugins.SimpleAPI.UserID' => $UserID,
          'Plugins.SimpleAPI.AccessToken' => $AccessToken
      ));
   }
   
}