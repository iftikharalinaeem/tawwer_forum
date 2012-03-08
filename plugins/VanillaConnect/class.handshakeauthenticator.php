<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Validates sessions by handshaking with another site by means of OAuth
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package Garden
 */
require_once(implode(DS, array(PATH_LIBRARY,'vendors','oauth','OAuth.php')));

class Gdn_HandshakeAuthenticator extends Gdn_Authenticator implements Gdn_IHandshake {

   protected $_CookieName = NULL;
   protected $_OAuthServer = NULL;
   
   protected $Provider = NULL;
   protected $Token = NULL;
   protected $Nonce = NULL;
   
   public function __construct() {
      
      // This authenticator gets its data directly from the request object, always
      $this->_DataSourceType = Gdn_Authenticator::DATA_REQUEST;
      
      // Define the fields we will be grabbing from the request
      $this->HookDataField('UserEmail', 'email');
      $this->HookDataField('UserName', 'name');
      $this->HookDataField('UserID', 'uid');
      $this->HookDataField('Transient', 'transient', FALSE);      // transient key, if needed/provided
      
      $this->HookDataField('ConsumerKey', 'oauth_consumer_key');
      $this->HookDataField('Token', 'oauth_token', FALSE);        // Might be null if doing background SSO
      $this->HookDataField('Nonce', 'oauth_nonce');
      $this->HookDataField('Signature', 'oauth_signature');
      $this->HookDataField('SignatureMethod', 'oauth_signature_method');
      $this->HookDataField('Timestamp', 'oauth_timestamp');
      $this->HookDataField('Version', 'oauth_version');
      
      // Create the instance of Server
      $this->_OAuthServer = new OAuthServer($this);

      // Configure the server instance with the supported signature methods
      $this->_OAuthServer->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
      $this->_OAuthServer->add_signature_method(new OAuthSignatureMethod_PLAINTEXT());
      
      // Which cookie signals the presence of an authentication package?
      $this->_CookieName = Gdn::Config('Garden.Authenticators.handshake.CookieName', 'VanillaHandshake');
      
      // Initialize built-in authenticator functionality
      parent::__construct();
   }
   
   public function Authenticate() {
      if ($this->CurrentStep() != Gdn_Authenticator::MODE_VALIDATE) return Gdn_Authenticator::AUTH_INSUFFICIENT;
      
      $UserEmail = $this->GetValue('UserEmail');
      $UserName = $this->GetValue('UserName');
      $UserID = $this->GetValue('UserID');
      $TransientKey = $this->GetValue('Transient');
      
      $ConsumerKey = $this->GetValue('ConsumerKey');
      $RToken = $this->GetValue('Token');
      $Nonce = $this->GetValue('Nonce');
      $Signature = $this->GetValue('Signature');
      $SignatureMethod = $this->GetValue('SignatureMethod');
      $Timestamp = $this->GetValue('Timestamp');
      $Version = $this->GetValue('Version');
      
      // When the request is coming from a script (reverse request order), fake the token... lol >,>
      if ($this->GetHandshakeMode() == 'javascript' && empty($RToken)) {
         $Token = $this->CreateToken('request', $ConsumerKey);
         $RToken = GetValue('Token', $Token);
      }
      
      $RequestArguments = array(
         'oauth_consumer_key'       => $ConsumerKey,
         'oauth_token'              => $RToken,
         'oauth_version'            => $Version,
         'oauth_timestamp'          => $Timestamp,
         'oauth_nonce'              => $Nonce,
         'oauth_signature_method'   => $SignatureMethod,
         'oauth_signature'          => $Signature,
         'email'                    => $UserEmail,
         'name'                     => $UserName,
         'uid'                      => $UserID,
         'transient'                => $TransientKey
      );
      
      $RequestURL = Url('/entry/auth/handshake',TRUE);
      
      try {
         $OAuthRequest = OAuthRequest::from_request('GET', $RequestURL, $RequestArguments);
         $UserAssociation = Gdn::Authenticator()->GetAssociation($UserEmail, $ConsumerKey, $KeyType = Gdn_Authenticator::KEY_TYPE_PROVIDER);
         
         if ($UserAssociation !== FALSE && $UserAssociation['Token']) {
            $TokenKey = $UserAssociation['Token'];
         } else {
            $Token = $this->lookup_token($ConsumerKey, 'request', $OAuthRequest->get_parameter('oauth_token'));
            $TokenKey = $Token->key;
         }
         
      } catch (Exception $e) {
         die('auth denied - '.$e->getMessage());
         return Gdn_Authenticator::AUTH_DENIED;
      }
      
      $CookiePayload = array(
         'token'        => $TokenKey,
         'consumer_key' => $ConsumerKey,
         'name'         => $UserName,
         'uid'          => $UserID,
         'transient'    => $TransientKey
      );
      
      /*
       * If this foreign key is already fully associated with a local user account, don't bother to set a request cookie.
       * Just go directly to creating an access token and authenticating it with a normal vanilla identity cookie.
       */
      if (GetValue('UserID', $UserAssociation, FALSE)) {
         $this->ProcessAuthorizedRequest($CookiePayload);
         return Gdn_Authenticator::AUTH_SUCCESS;
      } else {
         
         $SerializedCookiePayload = Gdn_Format::Serialize($CookiePayload);
         
         $this->AuthorizeToken($TokenKey);
         $this->AssociateRemoteKey($ConsumerKey, $UserEmail, $TokenKey);
   
         // Set authorized cookie on target
         $this->Remember($TokenKey, $SerializedCookiePayload);
      
         if ($this->GethandshakeMode() != Gdn_Authenticator::HANDSHAKE_DIRECT)
            return Gdn_Authenticator::AUTH_PARTIAL;
         else
            return Gdn_Authenticator::AUTH_SUCCESS;
      }
   }
   
   public function Remember($TokenKey, $SerializedCookiePayload) {
      Gdn_CookieIdentity::SetCookie($this->_CookieName, $TokenKey, array(1, 0, $SerializedCookiePayload), 0);
   }
   
   protected function _AssociateOAuthToken($TokenKey, $UserKey) {
      Gdn::Database()->SQL()->Reset();
      Gdn::Database()->SQL()->Update('UserAuthenticationToken')
         ->Set('ForeignUserKey', $UserKey)
         ->Where('Token', $TokenKey)
         ->Put();
   }
   
   public function CurrentStep() {
      $Id = Gdn::Authenticator()->GetIdentity();
      if ($Id > 0) return Gdn_Authenticator::MODE_REPEAT;
      
      $HookedFieldStatus = $this->_CheckHookedFields();
      if ($HookedFieldStatus) {
         if (is_null($this->_DataHooks['Token']['value']) && $this->GetHandshakeMode() != 'javascript')
            return FALSE;
      }
      return $HookedFieldStatus;
   }

   public function DeAuthenticate() {
   
      $ConsumerKey = $this->GetValue('ConsumerKey');
      $Nonce = $this->GetValue('Nonce');
      $Signature = $this->GetValue('Signature');
      $SignatureMethod = $this->GetValue('SignatureMethod');
      $Timestamp = $this->GetValue('Timestamp');
      $Version = $this->GetValue('Version');
      
      $RequestArguments = array(
         'oauth_consumer_key'      => $ConsumerKey,
         'oauth_version'           => $Version,
         'oauth_timestamp'         => $Timestamp,
         'oauth_nonce'             => $Nonce,
         'oauth_signature_method'  => $SignatureMethod,
         'oauth_signature'         => $Signature,
      );

      try {
         $OAuthRequest = OAuthRequest::from_request(NULL, NULL, $RequestArguments);
      } catch (Exception $e) {
         return Gdn_Authenticator::AUTH_DENIED;
      }
      
      $this->DeleteCookie();
      Gdn::Authenticator()->SetIdentity(NULL);
      return Gdn_Authenticator::AUTH_SUCCESS;
   }
   
   public function LoginResponse() {
      if ($this->GethandshakeMode() != Gdn_Authenticator::HANDSHAKE_DIRECT)
         return Gdn_Authenticator::REACT_REMOTE;
      else
         return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   public function PartialResponse() {
      if ($this->GethandshakeMode() != Gdn_Authenticator::HANDSHAKE_DIRECT)
         return Gdn_Authenticator::REACT_REMOTE;
      else
         return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   public function RepeatResponse() {
      if ($this->GethandshakeMode() != Gdn_Authenticator::HANDSHAKE_DIRECT)
         return Gdn_Authenticator::REACT_REMOTE;
      else
         return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   public function SuccessResponse() {
      if ($this->GethandshakeMode() != Gdn_Authenticator::HANDSHAKE_DIRECT)
         return Gdn_Authenticator::REACT_REMOTE;
      else
         return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   public function LogoutResponse() {
      if ($this->GethandshakeMode() != Gdn_Authenticator::HANDSHAKE_DIRECT)
         return Gdn_Authenticator::REACT_REMOTE;
      else
         return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   public function FailedResponse() {
      if ($this->GethandshakeMode() != Gdn_Authenticator::HANDSHAKE_DIRECT)
         return Gdn_Authenticator::REACT_REMOTE;
      else
         return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   public function GetHandshakeMode() {
      $ModeStr = Gdn::Request()->GetValue('mode', Gdn_Authenticator::HANDSHAKE_DIRECT);
      return $ModeStr;
   }
   
   public function RequireLogoutTransientKey() {
      return FALSE;
   }
   
   public function AssociateRemoteKey($ConsumerKey, $UserKey, $TokenKey, $UserID = 0) {
      Gdn::Authenticator()->AssociateUser($ConsumerKey, $UserKey, $UserID);      
      $this->_AssociateOAuthToken($TokenKey, $UserKey);
   }
   
   public function lookup_consumer($consumer_key) {
      try {
         $this->GetProvider($consumer_key, TRUE);
      } catch (Exception $e) {
         return FALSE;
      }
      return new Gdn_OAuthConsumer(
         $this->GetProviderKey(), 
         $this->GetProviderSecret(), 
         $this->GetProviderUrl(),
         $this->GetProviderValue('RegisterUrl'),
         $this->GetProviderValue('SignInUrl'),
         $this->GetProviderValue('SignOutUrl')
      );
   }
   
   public function lookup_token($Consumer, $TokenType, $Token) {
      $TokenKey = is_object($Token) ? $Token->key : $Token;
      $ConsumerKey = is_object($Consumer) ? $Consumer->key : $Consumer;
   
      // Delete old tokens
      Gdn::Database()->SQL()
         ->From('UserAuthenticationToken uat')
         ->Where('uat.Timestamp + uat.Lifetime <','NOW()', FALSE, FALSE)
         ->Where('uat.Lifetime >',0)
         ->Delete();
   
      $TokenData = Gdn::Database()->SQL()
         ->Select('uat.*')
         ->From('UserAuthenticationToken uat')
         ->Where('uat.Token', $TokenKey)
         ->Where('uat.TokenType', $TokenType)
         ->Where('uat.ProviderKey', $ConsumerKey)
         ->BeginWhereGroup()
            ->Where('(uat.Timestamp + uat.Lifetime) >=', 'NOW()')
            ->OrWHere('uat.Lifetime', 0)
         ->EndWhereGroup()
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
      
      if ($TokenData) {
         $OToken = new Gdn_OAuthToken(
            $TokenData['Token'], 
            $TokenData['TokenSecret'], 
            $TokenType,
            $TokenData['ForeignUserKey'], 
            $TokenData['Authorized'], 
            strtotime($TokenData['Timestamp']), 
            $TokenData['Lifetime']
         );
         return $OToken;
      }
      
      return FALSE;
   }
   
   public function lookup_nonce($Consumer, $Token, $Nonce, $Timestamp) {
      $TokenKey = is_object($Token) ? $Token->key : $Token;
      $ConsumerKey = is_object($Consumer) ? $Consumer->key : $Consumer;
      
      $StoredNonce = implode('.', array('nonce', $ConsumerKey, $Nonce));
      
      $SQL = Gdn::Database()->SQL();
      
      $SQL->Select('uan.*')
         ->From('UserAuthenticationNonce uan');
         
      if ($TokenKey)
         $SQL->Where('uan.Token', $TokenKey);
         
      $NonceData = $SQL->Where('uan.Nonce', $StoredNonce)
         ->Where('uan.Timestamp <=', $Timestamp)
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
         
      if (!$NonceData) {
         if ($TokenKey) {
            $SQL->Reset();
            
            $InsertArray = array(
               'Token'     => $TokenKey,
               'Nonce'     => $StoredNonce,
               'Timestamp' => date('Y-m-d H:i:s',$Timestamp)
            );
            
            try {
               $SQL->Insert('UserAuthenticationNonce', $InsertArray);
            } catch (Exception $e) {
               return FALSE;
            }
         }
         return FALSE;
      } else {
         // TODO: Delete the nonce
      }
      
      return TRUE;
   }
   
   public function new_request_token($Consumer) {
      $TokenArray = $this->CreateToken('request', $Consumer->key, NULL, FALSE);
      return new Gdn_OAuthToken($TokenArray['Token'], $TokenArray['TokenSecret'], $TokenArray['TokenType'], $TokenArray['ForeignUserKey'], $TokenArray['Authorized'], $TokenArray['Timestamp'], $TokenArray['Lifetime']);
   }
   
   public function new_access_token($Token, $Consumer, $Verifier) {
      if ($Token->Authorized) {
         $TokenArray = $this->CreateToken('access', $Consumer->key, $Token->UserKey, $Token->Authorized);
         $AccessToken = new Gdn_OAuthToken($TokenArray['Token'], $TokenArray['TokenSecret'], $TokenArray['TokenType'], $TokenArray['ForeignUserKey'], $TokenArray['Authorized'], $TokenArray['Timestamp'], $TokenArray['Lifetime']);
         
         $SQL = Gdn::Database()->SQL();
         $SQL
            ->From('UserAuthenticationToken')
            ->Where('Token', $Token->key)
            ->Where('ProviderKey', $Consumer->key)
            ->Delete();
            
         return $AccessToken;
      }
      
      return FALSE;
   }
   
   protected function _ConvertRequestToken($RequestToken, $OAuthConsumer) {
      
      if ($RequestToken && $RequestToken->UserKey) {
         $OAuthRequest = OAuthRequest::from_consumer_and_token($OAuthConsumer, $RequestToken, "GET", $OAuthConsumer->callback_url, array());
         
         $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
         $OAuthRequest->sign_request($SignatureMethod, $OAuthConsumer, $RequestToken);
         $AccessToken = $this->_OAuthServer->fetch_access_token($OAuthRequest);
         
         if ($AccessToken) {
            if (GetValue('UserID',$RequestToken,FALSE))
               $AccessToken->UserID = GetValue('UserID',$RequestToken);
            
            $this->AssociateRemoteKey($OAuthConsumer->key, $RequestToken->UserKey, $AccessToken->key, $AccessToken->UserID);
            return $AccessToken;
         }
      }
      
      return FALSE;
   }
   
   protected function _Synchronize($RequestToken, $OAuthConsumer, $CookiePayload) {
      $UserEmail = $RequestToken->UserKey;
      
      // Check if the user has already been associated, otherwise redirect to the HANDSHAKER OF DOOM!!!
      if (($UserAssociation = $RequestToken->GetAssociation()) !== false ) {
         $AccessToken = $this->_ConvertRequestToken($RequestToken, $OAuthConsumer);
         return $AccessToken;
      } else {
         Gdn::Request()->WithURI('entry/handshake/handshake');
      }
      
      return FALSE;
   }
   
   public function Finalize($UserKey, $UserID, $ConsumerKey, $TokenKey, $CookiePayload) {

      // Associate the request token with this user ID
      $this->AssociateRemoteKey($ConsumerKey, $UserKey, $TokenKey,  $UserID);
            
      // Process the request token and create an access token
      $this->ProcessAuthorizedRequest($CookiePayload);
   }
   
   public function ProcessAuthorizedRequest($CookiePayload) {
      
      $TokenKey = $CookiePayload['token'];
      $ConsumerKey = $CookiePayload['consumer_key'];
      $OAuthConsumer = $this->lookup_consumer($ConsumerKey);
      
      /* 
       * First see if this is a request token. If so, perform synchronization on it.
       *
       * The user will first be shown a UI element that will allow them to associate an existing account
       * or create a new one. The UserAuthentication row will then be associated with the resulting UserID.
       *
       * Upon redirection, the user will still have a request token and will land up here again. _Synchronize 
       * will detect their association and convert their request token into an access token and return that
       * new token.
       */
      $RequestToken = $this->lookup_token($ConsumerKey, 'request', $TokenKey);
      if ($RequestToken && $RequestToken->UserKey) {
      
         $AccessToken = $this->_Synchronize($RequestToken, $OAuthConsumer, $CookiePayload);
      } else {
      
         // The cookie token key did not correspond to a request.
         // Check for an access token incase this is a returning SSO user that is already fully associated
         $AccessToken = $this->lookup_token($ConsumerKey, 'access', $TokenKey);
      }
      
      if (!$AccessToken && !$RequestToken) {
         // Delete the cookie, it didnt match an access OR request token. Probably witchcraft at work.
         $this->DeleteCookie();
      }
      
      // If we have an access token, it means that we can probably autologin the user!
      if ($AccessToken && $AccessToken->UserKey) {
      
         // Actually, just delete the cookie now. We're tracked by vanilla cookies at this point. :D
         $this->DeleteCookie();
         
         $UserAssociation = $AccessToken->GetAssociation();
         if ($UserAssociation != FALSE) {
            $this->SetIdentity($UserAssociation['UserID'], FALSE);
            if ($CookiePayload['transient'])
               Gdn::Session()->TransientKey($CookiePayload['transient']);
         }   
      }
   }
   
   public function GetHandshake() {
      $HaveHandshake = Gdn_CookieIdentity::CheckCookie($this->_CookieName);
      
      if ($HaveHandshake) {
         // Found a handshake cookie, sweet. Get the payload.
         $Payload = Gdn_CookieIdentity::GetCookiePayload($this->_CookieName);
         
         // Shift the 'userid' and 'expiration' off the front. These were made-up anyway :D
         array_shift($Payload);
         array_shift($Payload);
         
         // Rebuild the real payload
         $ReconstitutedCookiePayload = Gdn_Format::Unserialize(TrueStripSlashes(array_shift($Payload)));
         
         return $ReconstitutedCookiePayload;
      }
      
      return FALSE;
   }
   
   public function DeleteCookie() {
      Gdn_Cookieidentity::DeleteCookie($this->_CookieName);
   }
   
   public function GetUserKeyFromHandshake($Handshake) {
      $TokenKey = ArrayValue('token', $Handshake, FALSE);
      $ConsumerKey = ArrayValue('consumer_key', $Handshake, FALSE);
      
      $Token = FALSE;
      foreach (array('request','access') as $TokenType) {
         $Token = $this->lookup_token($ConsumerKey, $TokenType, $TokenKey);
         if ($Token) break;
      }
      return $Token ? $Token->UserKey : $Token;
   }
   
   public function GetProviderKeyFromHandshake($Handshake) {
      return ArrayValue('consumer_key', $Handshake, FALSE);
   }
   
   public function GetTokenKeyFromHandshake($Handshake) {
      return ArrayValue('token', $Handshake, '');
   }
   
   public function GetUserNameFromHandshake($Handshake) {
      return ArrayValue('name', $Handshake, '');
   }
   
   public function GetUserEmailFromHandshake($Handshake) {
      return $this->GetUserKeyFromHandshake($Handshake);
   }
   
   public function GetURL($URLType) {
      $Provider = $this->GetProvider();
      $Nonce = $this->GetNonce();
      
      // Dirty hack to allow handling Remote* url requests and delegate basic requests to the config
      if (strlen($URLType) == strlen(str_replace('Remote','',$URLType))) return FALSE;
      
      $URLType = str_replace('Remote','',$URLType);
      // If we get here, we're handling a RemoteURL question
      if ($Provider && GetValue($URLType, $Provider, FALSE)) {
         $ResponseArray = array(
            'URL'          => $Provider[$URLType],
            'Parameters'   => array(
               'Nonce'  => $Nonce['Nonce']
            )
         );
         
         if ($URLType = Gdn_Authenticator::URL_SIGNIN) {
            $ResponseArray['URL'] .= '&oauth_token={oauth_token}&oauth_token_secret={oauth_token_secret}';
            $RequestToken = $this->new_request_token($this->lookup_consumer($Provider['AuthenticationKey']));
            $ResponseArray['Parameters']['oauth_token'] = $RequestToken->key;
            $ResponseArray['Parameters']['oauth_token_secret'] = $RequestToken->secret;
         }
         return $ResponseArray;
      }
      
      return FALSE;
   }
   
   public function AuthenticatorConfiguration(&$Sender) {
      // Let the plugin handle the config
      $Sender->AuthenticatorConfigure = NULL;
      $Sender->FireEvent('AuthenticatorConfigurationHandshake');
      return $Sender->AuthenticatorConfigure;
   }
   
   public function WakeUp() {
      // Allow the entry/handshake method to function
      Gdn::Authenticator()->AllowHandshake();
      
      $this->FetchData(Gdn::Request());
      $CurrentStep = $this->CurrentStep();
      
      // Shortcircuit to prevent pointless work when the access token has already been handled and we already have a session 
      if ($CurrentStep == Gdn_Authenticator::MODE_REPEAT)
         return;
      
      // Don't try to wakeup when the URL contains an OAuth request
      if ($CurrentStep == Gdn_Authenticator::MODE_VALIDATE)
         return;
      
      if ($this->GethandshakeMode() != Gdn_Authenticator::HANDSHAKE_DIRECT)
         return;
      
      // Look for handshake cookies
      $Payload = $this->GetHandshake();
      
      if ($Payload) {
         
         // Process the cookie auth
         $this->ProcessAuthorizedRequest($Payload);
      }
   }
   
}

class Gdn_OAuthToken extends OAuthToken {

   public $TokenType;
   public $UserKey;
   public $Authorized;
   public $Timestamp;
   public $Lifetime;
   public $UserID;
   
   public function __construct($TokenKey, $TokenSecret, $TokenType, $UserKey = NULL, $Authorized = FALSE, $Timestamp = NULL, $Lifetime = 60) {
      parent::__construct($TokenKey, $TokenSecret);
      $this->UserKey = $UserKey;
      $this->TokenType = $TokenType;
      $this->Authorized = $Authorized;
      
      $this->Timestamp = is_null($Timestamp) ? time() : $Timestamp;
      $this->Lifetime = $Lifetime;
      $this->UserID = 0;
   }
   
   public function GetAssociation() {
      return Gdn::Authenticator()->GetAssociation($this->UserKey, $this->key, Gdn_Authenticator::KEY_TYPE_TOKEN);
   }

}

class Gdn_OAuthConsumer extends OAuthConsumer {

   public $RegisterURL;
   public $SignInURL;
   public $SignOutURL;

   public function __construct($ConsumerKey, $ConsumerSecret, $ConsumerUrl, $RegisterUrl, $SignInUrl, $SignOutUrl) {
      parent::__construct($ConsumerKey, $ConsumerSecret, $ConsumerUrl);
      
      $this->RegisterUrl = $RegisterUrl;
      $this->SignInUrl = $SignInUrl;
      $this->SignOutUrl = $SignOutUrl;
   }

}
