<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class LinkedInPlugin extends Gdn_Plugin {
   const ProviderKey = 'LinkedIn';

   /// Methods ///

   protected $_AccessToken = NULL;

   public function AccessToken($value = NULL) {
      if (!$this->IsConfigured())
         return FALSE;

      if ($value !== NULL)
         $this->_AccessToken = $value;
      elseif ($this->_AccessToken === NULL) {
         if (Gdn::Session()->IsValid())
            $this->_AccessToken = GetValueR(self::ProviderKey.'.AccessToken', Gdn::Session()->User->Attributes);
         else
            $this->_AccessToken = FALSE;
      }

      return $this->_AccessToken;
   }

   public function API($path, $post = FALSE) {
      // Build the url.
      $url = 'https://api.linkedin.com/v1/'.ltrim($path, '/');

      $accessToken = $this->AccessToken();
      if (!$accessToken)
         throw new Gdn_UserException("You don't have a valid LinkedIn connection.");

      if (strpos($url, '?') === false)
         $url .= '?';
      else
         $url .= '&';
      $url .= 'oauth2_access_token='.urlencode($accessToken);
      $url .= '&format=json';

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_URL, $url);

      if ($post !== false) {
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
         Trace("  POST $url");
      } else {
         Trace("  GET  $url");
      }

      $response = curl_exec($ch);

      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
      curl_close($ch);

      Gdn::Controller()->SetJson('Type', $contentType);

      if (strpos($contentType, 'application/json') !== FALSE) {
         $result = json_decode($response, TRUE);

         if (isset($result['error'])) {
            Gdn::Dispatcher()->PassData('LinkedInResponse', $result);
            throw new Gdn_UserException($result['error']['message']);
         }
      } else
         $result = $response;

      return $result;
   }

   public function AuthorizeUri($redirectUri = FALSE) {
      $appID = C('Plugins.LinkedIn.ApplicationID');
      $scope = C('Plugins.LinkedIn.Scope', 'r_basicprofile r_emailaddress');

      if (!$redirectUri)
         $redirectUri = $this->RedirectUri();

      $query = [
         'client_id' => $appID,
         'response_type' => 'code',
         'scope' => $scope,
         'state' => substr(sha1(mt_rand()), 0, 8),
         'redirect_uri' => $redirectUri];

      $signinHref = "https://www.linkedin.com/uas/oauth2/authorization?".http_build_query($query);
      return $signinHref;
   }

   protected function GetAccessToken($code, $redirectUri, $throwError = TRUE) {
      $get = [
          'grant_type' => 'authorization_code',
          'client_id' => C('Plugins.LinkedIn.ApplicationID'),
          'client_secret' => C('Plugins.LinkedIn.Secret'),
          'code' => $code,
          'redirect_uri' => $redirectUri];

      $url = 'https://www.linkedin.com/uas/oauth2/accessToken?'.http_build_query($get);

      // Get the redirect URI.
      $c = curl_init();
      curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($c, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($c, CURLOPT_URL, $url);
      $contents = curl_exec($c);

      $info = curl_getinfo($c);
      if (strpos(GetValue('content_type', $info, ''), 'application/json') !== FALSE) {
         $tokens = json_decode($contents, TRUE);
      } else {
         parse_str($contents, $tokens);
      }

      if (GetValue('error', $tokens)) {
         throw new Gdn_UserException('LinkedIn returned the following error: '.GetValue('error_description', $tokens, 'Unknown error.'), 400);
      }

      $accessToken = GetValue('access_token', $tokens);
//      $Expires = GetValue('expires', $Tokens, NULL);

      return $accessToken;
   }

   public function GetProfile() {
      $profile = $this->API('/people/~:(id,formatted-name,picture-url,email-address)');
      $profile = ArrayTranslate(array_change_key_case($profile), ['id', 'emailaddress' => 'email', 'formattedname' => 'fullname', 'pictureurl' => 'photo']);
      return $profile;
   }

   public function SignInButton($type = 'button') {
      $url = $this->AuthorizeUri();

      $result = SocialSignInButton('LinkedIn', $url, $type);
      return $result;
   }

   public function IsConfigured() {
      $appID = C('Plugins.LinkedIn.ApplicationID');
      $secret = C('Plugins.LinkedIn.Secret');
      if (!$appID || !$secret)
         return FALSE;
      return TRUE;
   }

   public static function ProfileConnectUrl() {
      return url('profile/linkedinconnect', true).'?userID='.Gdn::Session()->UserID;
   }

   protected $_RedirectUri = NULL;
   public function RedirectUri($newValue = NULL) {
      if ($newValue !== NULL)
         $this->_RedirectUri = $newValue;
      elseif ($this->_RedirectUri === NULL) {
         $redirectUri = Url('/entry/connect/linkedin', TRUE);
         if (strpos($redirectUri, '=') !== FALSE) {
            $p = strrchr($redirectUri, '=');
            $uri = substr($redirectUri, 0, -strlen($p));
            $p = urlencode(ltrim($p, '='));
            $redirectUri = $uri.'='.$p;
         }

         $path = Gdn::Request()->Path();

         $target = GetValue('Target', $_GET, $path ? $path : '/');
         if (ltrim($target, '/') == 'entry/signin' || empty($target))
            $target = '/';
         $args = ['Target' => $target];


         $redirectUri .= strpos($redirectUri, '?') === FALSE ? '?' : '&';
         $redirectUri .= http_build_query($args);
         $this->_RedirectUri = $redirectUri;
      }

      return $this->_RedirectUri;
   }

   public function Setup() {
      $error = '';
      if (!function_exists('curl_init'))
         $error = ConcatSep("\n", $error, 'This plugin requires curl.');
      if ($error)
         throw new Gdn_UserException($error, 400);

      $this->Structure();
   }

   public function Structure() {
      // Save the facebook provider type.
      Gdn::SQL()->Replace('UserAuthenticationProvider',
         ['AuthenticationSchemeAlias' => 'linkedin', 'URL' => '...', 'AssociationSecret' => '...', 'AssociationHashMethod' => '...'],
         ['AuthenticationKey' => self::ProviderKey], TRUE);
   }

   /// Event Handlers ///

   public function Base_SignInIcons_Handler($sender, $args) {
		echo ' '.$this->SignInButton('icon').' ';
   }

   public function Base_BeforeSignInButton_Handler($sender, $args) {
      echo ' '.$this->SignInButton('icon').' ';
	}

   /**
    *
    * @param Gdn_Controller $sender
    * @param array $args
    */
   public function Base_ConnectData_Handler($sender, $args) {
      if (GetValue(0, $args) != 'linkedin')
         return;

      if (isset($_GET['error'])) {
         throw new Gdn_UserException(GetValue('error_description', $_GET, T('There was an error connecting to LinkedIn')));
      }

//      $AppID = C('Plugins.LinkedIn.ApplicationID');
//      $Secret = C('Plugins.LinkedIn.Secret');
      $code = $sender->Request->Get('code');
      $accessToken = $sender->Form->GetFormValue('AccessToken');

      // Get the access token.
      if (!$accessToken && $code) {
         // Exchange the token for an access token.
         $code = urlencode($code);
         $accessToken = $this->GetAccessToken($code, $this->RedirectUri());
         $this->AccessToken($accessToken);
         $newToken = TRUE;
      } elseif ($accessToken) {
         $this->AccessToken($accessToken);
      }

      $profile = $this->GetProfile();

      $form = $sender->Form; //new Gdn_Form();
      $iD = GetValue('id', $profile);
      $form->SetFormValue('UniqueID', $iD);
      $form->SetFormValue('Provider', self::ProviderKey);
      $form->SetFormValue('ProviderName', 'LinkedIn');
      $form->SetFormValue('FullName', GetValue('fullname', $profile));
      $form->SetFormValue('Email', GetValue('email', $profile));
      $form->SetFormValue('Photo', GetValue('photo', $profile));
      $form->AddHidden('AccessToken', $accessToken);

      // Save some original data in the attributes of the connection for later API calls.
      $attributes = [];
      $attributes[self::ProviderKey] = [
          'AccessToken' => $accessToken,
          'Profile' => $profile
      ];
      $form->SetFormValue('Attributes', $attributes);

      $sender->SetData('Verified', TRUE);
   }

   public function Base_GetConnections_Handler($sender, $args) {
      if (!$this->IsConfigured())
         return;

      $profile = GetValueR('User.Attributes.'.self::ProviderKey.'.Profile', $args);

      $sender->Data["Connections"][self::ProviderKey] = [
         'Icon' => $this->GetWebResource('icon.png', '/'),
         'Name' => self::ProviderKey,
         'ProviderKey' => self::ProviderKey,
         'ConnectUrl' => $this->AuthorizeUri(self::ProfileConnectUrl()),
         'Profile' => [
            'Name' => GetValue('fullname', $profile),
            'Photo' => GetValue('photo', $profile)
            ]
      ];
   }

      /**
    *
    * @param Gdn_Controller $sender
    */
   public function EntryController_SignIn_Handler($sender, $args) {
      if (isset($sender->Data['Methods'])) {
            // Add the facebook method to the controller.
            $method = [
               'Name' => self::ProviderKey,
               'SignInHtml' => $this->SignInButton('button')];
//         }

         $sender->Data['Methods'][] = $method;
      }
   }

   /**
    *
    *
    * @param ProfileController $sender
    * @param type $UserReference
    * @param type $Username
    * @param type $code
    */
   public function ProfileController_LinkedInConnect_Create($sender, $code=false) {
      $sender->Permission('Garden.SignIn.Allow');

      $userID = $sender->Request->get('userID');

      $sender->GetUserInfo('', '', $userID, TRUE);
      $sender->_SetBreadcrumbs(T('Connections'), UserUrl($sender->User, '', 'connections'));

      // Get the access token.
      $accessToken = $this->GetAccessToken($code, self::ProfileConnectUrl());
      Trace($accessToken, 'AccessToken');
      $this->AccessToken($accessToken);

      // Get the profile.
      $profile = $this->GetProfile();

      // Save the authentication.
      Gdn::UserModel()->SaveAuthentication([
         'UserID' => $sender->User->UserID,
         'Provider' => self::ProviderKey,
         'UniqueID' => $profile['id']]);

      // Save the information as attributes.
      $attributes = [
          'AccessToken' => $accessToken,
          'Profile' => $profile
      ];
      Gdn::UserModel()->SaveAttribute($sender->User->UserID, self::ProviderKey, $attributes);

      $this->EventArguments['Provider'] = self::ProviderKey;
      $this->EventArguments['User'] = $sender->User;
      $this->FireEvent('AfterConnection');

      redirectTo(UserUrl($sender->User, '', 'connections'));
   }

   public function SocialController_LinkedIn_Create($sender, $args) {
      $sender->Permission('Garden.Settings.Manage');
      $sender->SetData('Title', T('Linked In Settings'));

      $cf = new ConfigurationModule($sender);
      $cf->Initialize([
          'Plugins.LinkedIn.ApplicationID' => ['LabelCode' => 'API Key'],
          'Plugins.LinkedIn.Secret' => ['LabelCode' => 'Secret Key']
          ]);

      $sender->AddSideMenu('social');
      $sender->SetData('Title', sprintf(T('%s Settings'), 'LinkedIn'));
      $sender->ConfigurationModule = $cf;
      $sender->Render('Settings', '', 'plugins/LinkedIn');
   }
}
