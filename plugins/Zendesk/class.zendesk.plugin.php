<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['Zendesk'] = array(
   'Name' => 'Zendesk',
   'Description' => "!!!!!!Users may designate a discussion as a Support Issue and the message will be submitted to Zendesk. Reply will be added to thread",
   'Version' => '0.0.4',
   'RequiredApplications' => array('Vanilla' => '2.1.18'),
   'SettingsUrl' => '/dashboard/plugin/Zendesk',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'MobileFriendly' => TRUE,
   'Author' => 'John Ashton',
   'AuthorEmail' => 'johnashton@vanillaforums.com',
   'AuthorUrl' => 'http://www.github.com/John0x00'
);

/**
 * Class ZendeskPlugin
 *
 */
class ZendeskPlugin extends Gdn_Plugin {


   //methods

   /** @var \Zendesk Zendesk */
   protected $Zendesk;

   public function __construct() {
      parent::__construct();

      $this->Zendesk = new Zendesk(
         new ZendeskCurlRequest(),
         C('Plugins.Zendesk.ApiUrl'),
         C('Plugins.Zendesk.User'),
         C('Plugins.Zendesk.ApiKey')
      );


   }

   /**
    * @param DiscussionController $Sender
    * @param $Arguments
    */
   public function DiscussionController_AfterDiscussionBody_Handler($Sender, $Arguments) {

      if (!C('Plugins.Zendesk.Enabled')) {
         return;
      }

      // Signed in users only. No guest reporting!
      if (!Gdn::Session()->UserID) {
         return;
      }

      if (!Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
         return;
      }

      $Sender->AddCssFile('Zendesk.css', 'plugins/Zendesk');

//      $TicketID = $Arguments['Discussion']->ZendeskTicketID;
//      if ($Arguments['Discussion']->ZendeskTicketID != NULL) {
//         $Ticket = $this->Zendesk->GetTicket($TicketID);
//         $Sender->SetData('Ticket', $Ticket);
//         echo $Sender->FetchView('ticket-badge', '', 'plugins/Zendesk');
//      }


   }

   /**
    * Creates the Virtual Zendesk Controller and adds Link to SideMenu in the dashboard
    *
    * @param Controller $Sender
    */
   public function PluginController_Zendesk_Create($Sender) {

      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Zendesk');
      $Sender->AddSideMenu('plugin/Zendesk');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }

   /**
    * Dashboard Settings
    * Default method of virtual Zendesk controller.
    * @param Gdn_Controller $Sender
    */
   public function Controller_Index($Sender) {

      $Sender->AddCssFile('admin.css');

      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Plugin.Zendesk.ApiKey',
         'Plugin.Zendesk.User',
         'Plugin.Zendesk.Url',
         'Plugin.Zendesk.ApiUrl',
      ));

      // Set the model on the form.
      $Sender->Form->SetModel($ConfigurationModel);


      // If seeing the form for the first time...
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $Sender->Form->SetData($ConfigurationModel->Data);
      } else {
         $FormValues = $Sender->Form->FormValues();
         if ($Sender->Form->IsPostBack()) {
            $Sender->Form->ValidateRule('Plugin.Zendesk.ApiKey', 'function:ValidateRequired', 'API Key is required');
            $Sender->Form->ValidateRule('Plugin.Zendesk.User', 'function:ValidateRequired', 'User is required');
            $Sender->Form->ValidateRule('Plugin.Zendesk.Url', 'function:ValidateRequired', 'Url is required');
            $Sender->Form->ValidateRule('Plugin.Zendesk.ApiUrl', 'function:ValidateRequired', 'API Url is required');

            if ($Sender->Form->ErrorCount() == 0) {
               SaveToConfig('Plugins.Zendesk.ApiKey', trim($FormValues['Plugin.Zendesk.ApiKey']));
               SaveToConfig('Plugins.Zendesk.User', trim($FormValues['Plugin.Zendesk.User']));
               SaveToConfig('Plugins.Zendesk.Url', trim($FormValues['Plugin.Zendesk.Url']));
               SaveToConfig('Plugins.Zendesk.ApiUrl', trim($FormValues['Plugin.Zendesk.ApiUrl']));
               $Sender->InformMessage(T("Your changes have been saved."));
            } else {
               $Sender->InformMessage(T("Error saving settings to config."));
            }
         }


      }


      $Sender->Form->SetValue('Plugin.Zendesk.Url', C('Plugins.Zendesk.Url'));
      $Sender->Form->SetValue('Plugin.Zendesk.ApiKey', C('Plugins.Zendesk.ApiKey'));
      $Sender->Form->SetValue('Plugin.Zendesk.User', C('Plugins.Zendesk.User'));
      $Sender->Form->SetValue('Plugin.Zendesk.ApiUrl', C('Plugins.Zendesk.ApiUrl'));

      $Sender->Render($this->GetView('dashboard.php'));
   }

   public function DiscussionController_DiscussionOptions_Handler($Sender, $Args) {

      if (!C('Plugins.Zendesk.Enabled')) {
         return;
      }

      // Signed in users only. No guest reporting!
      if (!Gdn::Session()->UserID) {
         return;
      }

      $DiscussionID = $Args['Discussion']->DiscussionID;
      $ElementAuthorID = $Args['Discussion']->InsertUserID;

      if ($ElementAuthorID == Gdn::Session()->UserID) {
         //no need to create support tickets for your self
         return;
      }

      $LinkText = 'Create Zendesk Ticket';
      $Sender->AddCssFile('Zendesk.css', 'plugins/Zendesk');
      if (isset($Args['DiscussionOptions'])) {
         $Args['DiscussionOptions']['Zendesk'] = array(
            'Label' => T($LinkText),
            'Url' => "/discussion/Zendesk/$DiscussionID",
            'Class' => 'Popup'
         );
      }

      //@todo check attachments - remove option if zendesk-issue already created.
   }


   /**
    * Handle Zendesk popup in discussions
    * @throws Exception
    * @param DiscussionController $Sender
    */
   public function DiscussionController_Zendesk_Create($Sender) {

      // Signed in users only.
      if (!($UserID = Gdn::Session()->UserID)) return;
      $UserName = Gdn::Session()->User->Name;

      $Arguments = $Sender->RequestArgs;
      if (sizeof($Arguments) != 1) {
         throw new Exception('Invalid Request Url');
      }
      $DiscussionID = $Arguments[0];

      $Sender->Form = new Gdn_Form();

      if ($Sender->Form->IsPostBack() && $Sender->Form->AuthenticatedPostBack() === TRUE) {
         $Sender->Form->ValidateRule('Plugin.Zendesk.Title', 'function:ValidateRequired', 'Title is required');
         $Sender->Form->ValidateRule('Plugin.Zendesk.Body', 'function:ValidateRequired', 'Body is required');

         if ($Sender->Form->ErrorCount() == 0) {
            $FormValues = $Sender->Form->FormValues();

            $TicketID = $this->Zendesk->CreateTicket(
               $FormValues['Plugin.Zendesk.Title'],
               $FormValues['Plugin.Zendesk.Body'],
               $this->Zendesk->CreateRequester(
                  $FormValues['Plugin.Zendesk.InsertName'],
                  $FormValues['Plugin.Zendesk.InsertEmail']),
               array('custom_fields' => array('DiscussionID' => $DiscussionID))
            );

            if ($TicketID > 0) {
               //@todo save attachment
               $Sender->InformMessage(T("Message Sent to Zendesk."));

            } else {
               $Sender->InformMessage(T("Error creating ticket with Zendesk"));
            }


         }

      }

      $Content = $Sender->DiscussionModel->GetId($DiscussionID);
 

      $Data = array(
         'DiscussionID' => $DiscussionID,
         'UserID' => $UserID,
         'UserName' => $UserName,
         'Body' => $Content->Body,
         'InsertName' => $Content->InsertName,
         'InsertEmail' => $Content->InsertEmail,
         'Title' => $Content->Name,
      );

      $Sender->Form->SetValue('Plugin.Zendesk.UserId', $UserID);
      $Sender->Form->SetValue('Plugin.Zendesk.UserName', $UserName);
      $Sender->Form->SetValue('Plugin.Zendesk.Body', $Content->Body);
      $Sender->Form->SetValue('Plugin.Zendesk.InsertName', $Content->InsertName);
      $Sender->Form->SetValue('Plugin.Zendesk.InsertEmail', $Content->InsertEmail);
      $Sender->Form->SetValue('Plugin.Zendesk.Title', $Content->Name);

      $Sender->SetData('Plugin.Zendesk.Data', $Data);

      $Sender->Render('createticket', '', 'plugins/Zendesk');

//      $Sender->Render($this->GetView('createticket.php'));


   }


   /**
    * Enable/Disable .
    * @param Controller $Sender
    */
   public function Controller_Toggle($Sender) {

      // Enable/Disable
      if (Gdn::Session()->ValidateTransientKey(GetValue(1, $Sender->RequestArgs))) {
         if (C('Plugins.Zendesk.Enabled')) {
            $this->_Disable();
         } else {
            $this->_Enable();
         }
         Redirect('plugin/Zendesk');
      }
   }


   /**
    * Add Zendesk to Dashboard menu.
    * @param Controller $Sender
    * @param array $Arguments
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender, $Arguments) {
      $LinkText = T('Zendesk');
      $Menu = $Arguments['SideMenu'];
      $Menu->AddItem('Forum', T('Forum'));
      $Menu->AddLink('Forum', $LinkText, 'plugin/Zendesk', 'Garden.Settings.Manage');
   }


   protected function _Enable() {
      SaveToConfig('Plugins.Zendesk.Enabled', TRUE);
   }

   protected function _Disable() {
      RemoveFromConfig('Plugins.Zendesk.Enabled');
   }

   public function Setup() {

      $this->_SetupConfig();
      $this->Structure();

   }

   public function Structure() {


   }

   private function _SetupConfig() {
      SaveToConfig('Plugins.Zendesk.Enabled', FALSE);
      $ConfigSettings = array(
         'ApiKey',
         'User',
         'Url',
         'ApiUrl'
      );
      //prevents resetting any previous values
      foreach ($ConfigSettings as $ConfigSetting) {
         if (C('Plugins.Zendesk.' . $ConfigSetting)) {
            SaveToConfig('Plugins.Zendesk.' . $ConfigSetting, '');
         }
      }
   }



   //OAUTH - NOT WORKING

   const ProviderKey = 'Zendesk';
   const BaseUrl = 'https://amazinghourse.zendesk.com';
   const AuthorizeUrl = 'https://amazinghourse.zendesk.com/oauth/authorizations/new';
   const TokenUrl = 'https://amazinghourse.zendesk.com/oauth/tokens';
   const RedirectUrl = 'https://amazinghourse.zendesk.com/profile/connections';

   const Secret = 'd3591711ad3dcc2dd3d12303fcbd75df9255744a546862cdc0f4e5cb7cdd52fa';
   const ApplicationID = 'vanilla';

   /**
    * Used in the Oauth Process
    *
    * @param bool|string $RedirectUri
    * @param bool|string $State
    * @return string Authorize URL
    */
   public static function AuthorizeUri($RedirectUri = FALSE, $State = FALSE) {
      $AppID = self::ApplicationID;
      if (!$RedirectUri) {
         $RedirectUri = self::RedirectUri();
      }
      $Query = array(
         'redirect_uri' => $RedirectUri,
         'client_id' => $AppID,
         'response_type' => 'code',
         'scope' => 'read'
      );
      if ($State) {
         $Query['state'] = $State;
      }
      $Return = self::AuthorizeUrl . "?"
         . http_build_query($Query);
      return $Return;
   }

   /**
    * Used in the OAuth Process
    * @param null $NewValue a different redirect url
    * @return null|string
    */
   public static function RedirectUri($NewValue = NULL) {
      if ($NewValue !== NULL) {
         $RedirectUri = $NewValue;
      } else {
         $RedirectUri = Url('/profile/zendesk', TRUE, TRUE, TRUE);
         if (strpos($RedirectUri, '=') !== FALSE) {
            $p = strrchr($RedirectUri, '=');
            $Uri = substr($RedirectUri, 0, -strlen($p));
            $p = urlencode(ltrim($p, '='));
            $RedirectUri = $Uri . '=' . $p;
         }
      }
      return $RedirectUri;
   }

   /**
    * Used in the Oath Process
    *
    * @param $Code - OAuth Code
    * @param $RedirectUri - Redirect Uri
    * @return string Response
    * @throws Gdn_UserException
    */
   public static function GetTokens($Code, $RedirectUri) {
      $Post = array(
         'grant_type' => 'authorization_code',
         'client_id' => self::ApplicationID,
         'client_secret' => self::Secret,
         'code' => $Code,
         'redirect_uri' => $RedirectUri,
      );
      $Proxy = new ProxyRequest();
      $Response = $Proxy->Request(
         array(
            'URL' => self::TokenUrl,
            'Method' => 'POST',
         ),
         $Post
      );

      if (strpos($Proxy->ContentType, 'application/json') !== FALSE) {
         $Response = json_decode($Response);
      }
      if (isset($Response->error)) {
         throw new Gdn_UserException('Error Communicating with Zendesk API: ' . $Response->error_description);
      }

      return $Response;

   }

   /**
    * Used in the Oauth Process
    *
    * @return string $Url
    */
   public static function ProfileConnecUrl() {
      return Gdn::Request()->Url('/profile/zendeskconnect', TRUE, TRUE, TRUE);
   }

   /**
    * Used in the Oauth Process
    *
    * @return bool
    */
   public static function IsConfigured() {
      $AppID = self::ApplicationID;
      $Secret = self::Secret;
      if (!$AppID || !$Secret) {
         return FALSE;
      }
      return TRUE;
   }

   public function SetAccessToken($AccessToken) {
      $this->AccessToken = $AccessToken;
   }

   public function SetInstanceUrl($InstanceUrl) {
      $this->InstanceUrl = $InstanceUrl;
   }

   /**
    * @param Controller $Sender
    * @param array $Args
    */
   public function Base_GetConnections_Handler($Sender, $Args) {
      if (!$this->IsConfigured()) {
         return;
      }
      $Sf = GetValueR('User.Attributes.' . self::ProviderKey, $Args);
      Trace($Sf);
      $Profile = GetValueR('User.Attributes.' . self::ProviderKey . '.Profile', $Args);
      $Sender->Data["Connections"][self::ProviderKey] = array(
         'Icon' => $this->GetWebResource('icon.png', '/'),
         'Name' => self::ProviderKey,
         'ProviderKey' => self::ProviderKey,
         'ConnectUrl' => self::AuthorizeUri(self::ProfileConnecUrl()),
         'Profile' => array(
            'Name' => GetValue('fullname', $Profile),
            'Photo' => GetValue('photo', $Profile)
         )
      );
   }

   /**
    * @param ProfileController $Sender
    * @param string $UserReference
    * @param string $Username
    * @param bool $Code
    *
    */
   public function ProfileController_ZendeskConnect_Create($Sender, $UserReference = '', $Username = '', $Code = FALSE) {
      $Sender->Permission('Garden.SignIn.Allow');
      $Sender->GetUserInfo($UserReference, $Username, '', TRUE);
      $Sender->_SetBreadcrumbs(T('Connections'), UserUrl($Sender->User, '', 'connections'));
      //check $GET state // if DashboardConnection // then do global connection.
//      $State = GetValue('state', $_GET, FALSE);
//      if ($State == 'DashboardConnection') {
//         try {
//            $Tokens = self::GetTokens($Code, self::ProfileConnecUrl());
//         } catch (Gdn_UserException $e) {
//            $Message = $e->getMessage();
//            Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($Message))
//               ->Dispatch('home/error');
//            return;
//         }
//         Redirect('/plugin/Zendesk/?DashboardConnection=1&' . http_build_query($Tokens));
//      }
      try {
         $Tokens = $this->GetTokens($Code, self::ProfileConnecUrl());
      } catch (Gdn_UserException $e) {
         $Attributes = array(
            'RefreshToken' => NULL,
            'AccessToken' => NULL,
            'InstanceUrl' => NULL,
            'Profile' => NULL,
         );
         Gdn::UserModel()->SaveAttribute($Sender->User->UserID, self::ProviderKey, $Attributes);
         $Message = $e->getMessage();
         Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($Message))
            ->Dispatch('home/error');
         return;
      }
      $AccessToken = GetValue('access_token', $Tokens);
      $InstanceUrl = GetValue('instance_url', $Tokens);
      $RefreshToken = GetValue('refresh_token', $Tokens);

      //@todo profile
      $Profile = array();
      Gdn::UserModel()->SaveAuthentication(array(
         'UserID' => $Sender->User->UserID,
         'Provider' => self::ProviderKey,
         'UniqueID' => $Profile['id']
      ));
      $Attributes = array(
         'RefreshToken' => $RefreshToken,
         'AccessToken' => $AccessToken,
         'InstanceUrl' => $InstanceUrl,
         'Profile' => $Profile,
      );
      Gdn::UserModel()->SaveAttribute($Sender->User->UserID, self::ProviderKey, $Attributes);
      $this->EventArguments['Provider'] = self::ProviderKey;
      $this->EventArguments['User'] = $Sender->User;
      $this->FireEvent('AfterConnection');

      $RedirectUrl = UserUrl($Sender->User, '', 'connections');

      Redirect($RedirectUrl);
   }

   //end of OAUTH


}