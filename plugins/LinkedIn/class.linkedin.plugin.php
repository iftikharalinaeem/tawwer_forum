<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['LinkedIn'] = array(
   'Name' => 'Linked In Social Connect',
   'Description' => "Allow users to sign in via linked in.",
   'Version' => '1.1',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com',
   'MobileFriendly' => TRUE,
   'SettingsUrl' => '/social/linkedin',
   'SocialConnect' => TRUE
);

class LinkedInPlugin extends Gdn_Plugin {
   const ProviderKey = 'LinkedIn';
   
   /// Methods ///
   
   protected $_AccessToken = NULL;
   
   public function AccessToken($Value = NULL) {
      if (!$this->IsConfigured()) 
         return FALSE;
      
      if ($Value !== NULL)
         $this->_AccessToken = $Value;
      elseif ($this->_AccessToken === NULL) {
         if (Gdn::Session()->IsValid())
            $this->_AccessToken = GetValueR(self::ProviderKey.'.AccessToken', Gdn::Session()->User->Attributes);
         else
            $this->_AccessToken = FALSE;
      }
      
      return $this->_AccessToken;
   }
   
   public function API($Path, $Post = FALSE) {
      // Build the url.
      $Url = 'https://api.linkedin.com/v1/'.ltrim($Path, '/');
      
      $AccessToken = $this->AccessToken();
      if (!$AccessToken)
         throw new Gdn_UserException("You don't have a valid LinkedIn connection.");
      
      if (strpos($Url, '?') === false)
         $Url .= '?';
      else
         $Url .= '&';
      $Url .= 'oauth2_access_token='.urlencode($AccessToken);
      $Url .= '&format=json';

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_URL, $Url);

      if ($Post !== false) {
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $Post); 
         Trace("  POST $Url");
      } else {
         Trace("  GET  $Url");
      }

      $Response = curl_exec($ch);

      $HttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $ContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
      curl_close($ch);
      
      Gdn::Controller()->SetJson('Type', $ContentType);

      if (strpos($ContentType, 'application/json') !== FALSE) {
         $Result = json_decode($Response, TRUE);
         
         if (isset($Result['error'])) {
            Gdn::Dispatcher()->PassData('LinkedInResponse', $Result);
            throw new Gdn_UserException($Result['error']['message']);
         }
      } else
         $Result = $Response;

      return $Result;
   }
   
   public function AuthorizeUri($RedirectUri = FALSE) {
      $AppID = C('Plugins.LinkedIn.ApplicationID');
      $Scope = C('Plugins.LinkedIn.Scope', 'r_basicprofile r_emailaddress');

      if (!$RedirectUri)
         $RedirectUri = $this->RedirectUri();
      
      $Query = array(
         'client_id' => $AppID,
         'response_type' => 'code',
         'scope' => $Scope,
         'state' => substr(sha1(mt_rand()), 0, 8),
         'redirect_uri' => $RedirectUri);

      $SigninHref = "https://www.linkedin.com/uas/oauth2/authorization?".http_build_query($Query);
      return $SigninHref;
   }
   
   protected function GetAccessToken($Code, $RedirectUri, $ThrowError = TRUE) {
      $Get = array(
          'grant_type' => 'authorization_code',
          'client_id' => C('Plugins.LinkedIn.ApplicationID'),
          'client_secret' => C('Plugins.LinkedIn.Secret'),
          'code' => $Code,
          'redirect_uri' => $RedirectUri);
      
      $Url = 'https://www.linkedin.com/uas/oauth2/accessToken?'.http_build_query($Get);
      
      // Get the redirect URI.
      $C = curl_init();
      curl_setopt($C, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($C, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($C, CURLOPT_URL, $Url);
      $Contents = curl_exec($C);

      $Info = curl_getinfo($C);
      if (strpos(GetValue('content_type', $Info, ''), 'application/json') !== FALSE) {
         $Tokens = json_decode($Contents, TRUE);
      } else {
         parse_str($Contents, $Tokens);
      }

      if (GetValue('error', $Tokens)) {
         throw new Gdn_UserException('LinkedIn returned the following error: '.GetValue('error_description', $Tokens, 'Unknown error.'), 400);
      }

      $AccessToken = GetValue('access_token', $Tokens);
//      $Expires = GetValue('expires', $Tokens, NULL);
      
      return $AccessToken;
   }
   
   public function GetProfile() {
      $Profile = $this->API('/people/~:(id,formatted-name,picture-url,email-address)');
      $Profile = ArrayTranslate(array_change_key_case($Profile), array('id', 'emailaddress' => 'email', 'formattedname' => 'fullname', 'pictureurl' => 'photo'));
      return $Profile;
   }
   
   public function SignInButton($type = 'button') {
      $Url = $this->AuthorizeUri();
      
      $Result = SocialSignInButton('LinkedIn', $Url, $type);
      return $Result;
   }
   
   public function IsConfigured() {
      $AppID = C('Plugins.LinkedIn.ApplicationID');
      $Secret = C('Plugins.LinkedIn.Secret');
      if (!$AppID || !$Secret)
         return FALSE;
      return TRUE;
   }
   
   public static function ProfileConnecUrl() {
      return Url(UserUrl(Gdn::Session()->User, FALSE, 'linkedinconnect'), TRUE);
   }
   
   protected $_RedirectUri = NULL;
   public function RedirectUri($NewValue = NULL) {
      if ($NewValue !== NULL)
         $this->_RedirectUri = $NewValue;
      elseif ($this->_RedirectUri === NULL) {
         $RedirectUri = Url('/entry/connect/linkedin', TRUE);
         if (strpos($RedirectUri, '=') !== FALSE) {
            $p = strrchr($RedirectUri, '=');
            $Uri = substr($RedirectUri, 0, -strlen($p));
            $p = urlencode(ltrim($p, '='));
            $RedirectUri = $Uri.'='.$p;
         }

         $Path = Gdn::Request()->Path();

         $Target = GetValue('Target', $_GET, $Path ? $Path : '/');
         if (ltrim($Target, '/') == 'entry/signin' || empty($Target))
            $Target = '/';
         $Args = array('Target' => $Target);


         $RedirectUri .= strpos($RedirectUri, '?') === FALSE ? '?' : '&';
         $RedirectUri .= http_build_query($Args);
         $this->_RedirectUri = $RedirectUri;
      }
      
      return $this->_RedirectUri;
   }
   
   public function Setup() {
      $Error = '';
      if (!function_exists('curl_init'))
         $Error = ConcatSep("\n", $Error, 'This plugin requires curl.');
      if ($Error)
         throw new Gdn_UserException($Error, 400);

      $this->Structure();
   }

   public function Structure() {
      // Save the facebook provider type.
      Gdn::SQL()->Replace('UserAuthenticationProvider',
         array('AuthenticationSchemeAlias' => 'linkedin', 'URL' => '...', 'AssociationSecret' => '...', 'AssociationHashMethod' => '...'),
         array('AuthenticationKey' => self::ProviderKey), TRUE);
   }
   
   /// Event Handlers ///
   
   /**
    * @param AssetModel $Sender
    */
   public function AssetModel_StyleCss_Handler($Sender, $Args) {
      $Sender->AddCssFile('linkedin.css', 'plugins/LinkedIn');
   }
   
   public function Base_SignInIcons_Handler($Sender, $Args) {
		echo ' '.$this->SignInButton('icon').' ';
   }

   public function Base_BeforeSignInButton_Handler($Sender, $Args) {
      echo ' '.$this->SignInButton('icon').' ';
	}
   
   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function Base_ConnectData_Handler($Sender, $Args) {
      if (GetValue(0, $Args) != 'linkedin')
         return;

      if (isset($_GET['error'])) {
         throw new Gdn_UserException(GetValue('error_description', $_GET, T('There was an error connecting to LinkedIn')));
      }

//      $AppID = C('Plugins.LinkedIn.ApplicationID');
//      $Secret = C('Plugins.LinkedIn.Secret');
      $Code = $Sender->Request->Get('code');
      $AccessToken = $Sender->Form->GetFormValue('AccessToken');
      
      // Get the access token.
      if (!$AccessToken && $Code) {
         // Exchange the token for an access token.
         $Code = urlencode($Code);
         $AccessToken = $this->GetAccessToken($Code, $this->RedirectUri());
         $this->AccessToken($AccessToken);
         $NewToken = TRUE;
      } elseif ($AccessToken) {
         $this->AccessToken($AccessToken);
      }
      
      $Profile = $this->GetProfile();

      $Form = $Sender->Form; //new Gdn_Form();
      $ID = GetValue('id', $Profile);
      $Form->SetFormValue('UniqueID', $ID);
      $Form->SetFormValue('Provider', self::ProviderKey);
      $Form->SetFormValue('ProviderName', 'LinkedIn');
      $Form->SetFormValue('FullName', GetValue('fullname', $Profile));
      $Form->SetFormValue('Email', GetValue('email', $Profile));
      $Form->SetFormValue('Photo', GetValue('photo', $Profile));
      $Form->AddHidden('AccessToken', $AccessToken);
      
      // Save some original data in the attributes of the connection for later API calls.
      $Attributes = array();
      $Attributes[self::ProviderKey] = array(
          'AccessToken' => $AccessToken,
          'Profile' => $Profile
      );
      $Form->SetFormValue('Attributes', $Attributes);
      
      $Sender->SetData('Verified', TRUE);
   }
   
   public function Base_GetConnections_Handler($Sender, $Args) {
      if (!$this->IsConfigured())
         return;
      
      $Profile = GetValueR('User.Attributes.'.self::ProviderKey.'.Profile', $Args);
      
      $Sender->Data["Connections"][self::ProviderKey] = array(
         'Icon' => $this->GetWebResource('icon.png', '/'),
         'Name' => self::ProviderKey,
         'ProviderKey' => self::ProviderKey,
         'ConnectUrl' => $this->AuthorizeUri(self::ProfileConnecUrl()),
         'Profile' => array(
            'Name' => GetValue('fullname', $Profile),
            'Photo' => GetValue('photo', $Profile)
            )
      );
   }
   
      /**
    *
    * @param Gdn_Controller $Sender
    */
   public function EntryController_SignIn_Handler($Sender, $Args) {
      if (isset($Sender->Data['Methods'])) {
            // Add the facebook method to the controller.
            $Method = array(
               'Name' => self::ProviderKey,
               'SignInHtml' => $this->SignInButton('button'));
//         }

         $Sender->Data['Methods'][] = $Method;
      }
   }
   
   /**
    * 
    * 
    * @param ProfileController $Sender
    * @param type $UserReference
    * @param type $Username
    * @param type $Code
    */
   public function ProfileController_LinkedInConnect_Create($Sender, $UserReference, $Username, $Code = FALSE) {
      $Sender->Permission('Garden.SignIn.Allow');
      
      $Sender->GetUserInfo($UserReference, $Username, '', TRUE);
      $Sender->_SetBreadcrumbs(T('Connections'), UserUrl($Sender->User, '', 'connections'));
      
      // Get the access token.
      $AccessToken = $this->GetAccessToken($Code, self::ProfileConnecUrl());
      Trace($AccessToken, 'AccessToken');
      $this->AccessToken($AccessToken);
      
      // Get the profile.
      $Profile = $this->GetProfile();
      
      // Save the authentication.
      Gdn::UserModel()->SaveAuthentication(array(
         'UserID' => $Sender->User->UserID,
         'Provider' => self::ProviderKey,
         'UniqueID' => $Profile['id']));
      
      // Save the information as attributes.
      $Attributes = array(
          'AccessToken' => $AccessToken,
          'Profile' => $Profile
      );
      Gdn::UserModel()->SaveAttribute($Sender->User->UserID, self::ProviderKey, $Attributes);
      
      $this->EventArguments['Provider'] = self::ProviderKey;
      $this->EventArguments['User'] = $Sender->User;
      $this->FireEvent('AfterConnection');
      
      Redirect(UserUrl($Sender->User, '', 'connections'));
   }
   
   public function SocialController_LinkedIn_Create($Sender, $Args) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->SetData('Title', T('Linked In Settings'));

      $Cf = new ConfigurationModule($Sender);
      $Cf->Initialize(array(
          'Plugins.LinkedIn.ApplicationID' => array('LabelCode' => 'API Key'),
          'Plugins.LinkedIn.Secret' => array('LabelCode' => 'Secret Key')
          ));

      $Sender->AddSideMenu('dashboard/social');
      $Sender->SetData('Title', sprintf(T('%s Settings'), 'LinkedIn'));
      $Sender->ConfigurationModule = $Cf;
      $Sender->Render('Settings', '', 'plugins/LinkedIn');
   }
}
