<?php if (!defined('APPLICATION')) exit();

/**
 * Simple Vanilla API 
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
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'SettingsUrl' => '/settings/api'
);

class SimpleAPIPlugin extends Gdn_Plugin {
   
   /// Methods ///
   
   public static function TranslatePost(&$Post, $ThrowError = TRUE) {
      $Errors = array();
      
      foreach ($Post as $Key => $Value) {
         if (StringEndsWith($Key, 'Category')) {
            // Translate a category column.
            $Px = StringEndsWith($Key, 'Category', TRUE, TRUE);
            $Column = $Px.'CategoryID';
            if (isset($Post[$Column]))
               continue;
            
            $Category = CategoryModel::Categories($Value);
            if (!$Category) {
               $Errors[] = self::NotFoundString('Category', $Value);
            } else {
               $Post[$Column] = (string)$Category['CategoryID'];
            }
         }
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
   
   protected static function NotFoundString($Code, $Item) {
      return sprintf(T('%1$s "%2$s" not found.'), T($Code), $Item);
   }
   
   public function Setup() {
      $this->Structure();
   }
   
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
   
   /// Event Handlers ///
   
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
      
      $MatchedAPI = preg_match('`^api/(v[\d\.]+)/(.+)`i', $IncomingRequest, $URI);
      
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
         $ApiMapper = new ApiMapper();
         
         // Lookup the mapped replacement for this request
         $MappedURI = $ApiMapper->Map($APIRequest);
         if (!$MappedURI) throw new Exception('Unable to map request');
         
         // Apply the mapped replacement
         Gdn::Request()->WithURI($MappedURI);
         
      } catch (Exception $Ex) {
         
         Gdn::Request()->WithURI($APIRequest);
         
      }
   }
   
   /**
    * Adds "Media" menu option to the Forum menu on the dashboard.
    * 
    * @param Gdn_Controller $Sender 
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Site Settings', T('API'), 'settings/api', 'Garden.Settings.Manage');
   }
   
   /**
    * 
    * 
    * @param Gdn_Dispatcher $Sender 
    */
   public function Gdn_Dispatcher_BeforeControllerMethod_Handler($Sender, $Args) {
      $Controller = $Args['Controller'];
      
      // This can be an API request if we are only requesting data and the correct access_token is given.
      if ($Controller->DeliveryType() == DELIVERY_TYPE_DATA) {
         $OnlyHttps = C('Plugins.SimpleAPI.OnlyHttps');
         if ($OnlyHttps && strcasecmp(Gdn::Request()->Scheme(), 'https') != 0) {
            throw new Exception(T('You must access the API through https.'), 401);
         }
         
         $AccessToken = GetValue('access_token', $_GET, NULL);
         
         if ($AccessToken !== NULL) {
            if ($AccessToken == C('Plugins.SimpleAPI.AccessToken')) {
               Gdn::Session()->Start(Gdn::UserModel()->GetSystemUserID(), FALSE, FALSE);
            } else {
               throw new Exception(T('Invald Access Token'), 401);
            }
         }
         
         if (strcasecmp(GetValue('contenttype', $_GET, ''), 'json') == 0 || strpos($_SERVER['CONTENT_TYPE'], 'json') !== FALSE) {
            $Post = file_get_contents('php://input');
            
            if ($Post)
               $Post = json_decode($Post, TRUE);
            else
               $Post = array();
         } else {
            $Post = Gdn::Request()->Post();         
         }
         
         self::TranslatePost($Post);
         
         Gdn::Request()->SetRequestArguments(Gdn_Request::INPUT_POST, $Post);
         $_POST = $Post;
         
//         decho($_POST, '$_POST');
//         decho(Gdn::Request()->Post(), '$_POST');
//         die();
      }
   }
   
   /**
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
}