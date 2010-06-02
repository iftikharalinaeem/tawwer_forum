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
require_once(implode(DS, array(PATH_LIBRARY,'vendors','OAuth','OAuth.php')));

class Gdn_HandshakeAuthenticator extends Gdn_Authenticator {

   protected $_CookieName = NULL;
   protected $_OAuthServer = NULL;
   
   public function __construct() {
      
      // This authenticator gets its data directly from the request object, always
      $this->_DataSourceType = Gdn_Authenticator::DATA_REQUEST;
      
      // Define the fields we will be grabbing from the request
      $this->HookDataField('UserEmail', 'email');
      $this->HookDataField('UserName', 'name');
      $this->HookDataField('UserID', 'uid');
      
      $this->HookDataField('ConsumerKey', 'oauth_consumer_key');
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
      $this->_CookieName = Gdn::Config('Garden.Authenticators.handshake.CookieName');
      
      // Initialize built-in authenticator functionality
      parent::__construct();
   }
   
   public function Authenticate() {
      
      if ($this->CurrentStep() != Gdn_Authenticator::MODE_VALIDATE) return Gdn_Authenticator::AUTH_INSUFFICIENT;
      
      $UserEmail = $this->GetValue('UserEmail');
      $UserName = $this->GetValue('UserName');
      $UserID = $this->GetValue('UserID');
      
      $ConsumerKey = $this->GetValue('ConsumerKey');
      $Nonce = $this->GetValue('Nonce');
      $Signature = $this->GetValue('Signature');
      $SignatureMethod = $this->GetValue('SignatureMethod');
      $Timestamp = $this->GetValue('Timestamp');
      $Version = $this->GetValue('Version');
      
      // First check if we already have a token for this userkey
      $SQL = Gdn::Database()->SQL();
      $HaveToken = $SQL->Select('ua.UserID, ua.ForeignUserKey, uat.*')
         ->From('UserAuthentication ua')
         ->Join('UserAuthenticationToken uat', 'ua.ForeignUserKey = uat.ForeignUserKey', 'left')
         ->Where('ua.ForeignUserKey', $UserEmail)
         ->Where('ua.ProviderKey', $ConsumerKey)
         ->BeginWhereGroup()
            ->Where('DATE_ADD(uat.Timestamp, INTERVAL uat.Lifetime SECOND) >=', 'NOW()')
            ->OrWHere('uat.Lifetime', 0)
         ->EndWhereGroup()
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
      
      if ($HaveToken) {
         
         $TokenKey = $HaveToken['Token'];

      } else {

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
            $Token = $this->_OAuthServer->fetch_request_token($OAuthRequest);
         } catch (Exception $e) {
            return Gdn_Authenticator::AUTH_DENIED;
         }
         
         $TokenKey = $Token->key;

      }

      $CookiePayload = array(
         'token'        => $TokenKey,
         'consumer_key' => $ConsumerKey,
         'name'         => $UserName,
         'uid'          => $UserID
      );
      $SerializedCookiePayload = Gdn_Format::Serialize($CookiePayload);
      
      $this->AssociateRemoteKey($ConsumerKey, $UserEmail, $TokenKey);

      // Set authorized cookie on target
      $this->_Remember($TokenKey, $SerializedCookiePayload);
      
      /*
       * If this foreign key is already fully associated with a local user account, don't bother to set a request cookie.
       * Just go directly to creating an access token and authenticating it with a normal vanilla identity cookie.
       */
      if ($HaveToken['UserID']) {
         
         $this->ProcessAuthorizedRequest($CookiePayload, TRUE);
         return Gdn_Authenticator::AUTH_SUCCESS;
      } else {
         
         if (Gdn::Request()->Filename() == 'handshake.js')
            return Gdn_Authenticator::AUTH_PARTIAL;
         else
            return Gdn_Authenticator::AUTH_SUCCESS;
      }
   }
   
   protected function _Remember($TokenKey, $SerializedCookiePayload) {
      Gdn_CookieIdentity::SetCookie($this->_CookieName, $TokenKey, array(1, 0, $SerializedCookiePayload), 0);
   }
   
   protected function _AssociateOAuthToken($TokenKey, $UserKey) {
      $SQL = Gdn::Database()->SQL();
      $SQL->Reset();
      $SQL->Update('UserAuthenticationToken')
         ->Set('ForeignUserKey', $UserKey)
         ->Where('Token', $TokenKey)
         ->Put();
   }
   
   public function CurrentStep() {
      $Id = Gdn::Authenticator()->GetIdentity();
      if ($Id > 0) return Gdn_Authenticator::MODE_REPEAT;
      
      return $this->_CheckHookedFields();
   }

   public function DeAuthenticate() {
      $SignOutURL = Gdn::Authenticator()->SignOutUrl();
      Gdn::Authenticator()->SetIdentity(NULL);
      
      // Redirect to the external signout url
      Redirect($SignOutURL);
   }
   
   public function LoginResponse() {
      if (Gdn::Request()->Filename() == 'handshake.js')
         return Gdn_Authenticator::REACT_REMOTE;
      else
         return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   public function PartialResponse() {
      if (Gdn::Request()->Filename() == 'handshake.js')
         return Gdn_Authenticator::REACT_REMOTE;
      else
         return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   public function RepeatResponse() {
      if (Gdn::Request()->Filename() == 'handshake.js')
         return Gdn_Authenticator::REACT_REMOTE;
      else
         return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   public function SuccessResponse() {
      if (Gdn::Request()->Filename() == 'handshake.js')
         return Gdn_Authenticator::REACT_REMOTE;
      else
         return Gdn_Authenticator::REACT_EXIT;
   }
   
   public function AssociateRemoteKey($ConsumerKey, $UserKey, $TokenKey, $UserID = 0) {
      $SQL = Gdn::Database()->SQL();
      
      if ($UserID == 0) {
         try {
            $SQL->Insert('UserAuthentication',array(
               'UserID'          => 0,
               'ForeignUserKey'  => $UserKey,
               'ProviderKey'     => $ConsumerKey
            ));
         } catch(Exception $e) {}
      } else {
         $SQL->Replace('UserAuthentication',array(
               'UserID'          => $UserID
            ), array(
               'ForeignUserKey'  => $UserKey,
               'ProviderKey'     => $ConsumerKey
            ));
      }
      
      $this->_AssociateOAuthToken($TokenKey, $UserKey);
   }
   
   public function lookup_consumer($consumer_key) {
      try {
         $this->LoadProvider($consumer_key);
      } catch (Exception $e) {
         return FALSE;
      }
      return new Gdn_OAuthConsumer(
         $this->GetProviderKey(), 
         $this->GetProviderSecret(), 
         $this->GetProviderUrl(),
         $this->GetProviderValue('RegistrationUrl'),
         $this->GetProviderValue('SignInUrl'),
         $this->GetProviderValue('SignOutUrl')
      );
   }
   
   public function lookup_token($Consumer, $TokenType, $Token) {
      $TokenKey = is_object($Token) ? $Token->key : $Token;
      $ConsumerKey = is_object($Consumer) ? $Consumer->key : $Consumer;
   
      $SQL = Gdn::Database()->SQL();
      $TokenData = $SQL
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
      return $this->CreateNewToken('request', $Consumer->key, NULL, TRUE);
   }
   
   public function new_access_token($Token, $Consumer, $Verifier) {
      if ($Token->Authorized) {
         $AccessToken = $this->CreateNewToken('access', $Consumer->key, $Token->UserKey, $Token->Authorized);
         
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
   
   protected function CreateNewToken($TokenType, $ConsumerKey, $UserKey = NULL, $Authorized = FALSE) {
      $TokenKey = implode('.', array('token',$ConsumerKey,time(),mt_rand(0,100000)));
      $TokenSecret = sha1(md5(implode('.',array($TokenKey,mt_rand(0,100000)))));
      $Timestamp = time();
      
      $SQL = Gdn::Database()->SQL();
      $InsertArray = array(
         'Token' => $TokenKey,
         'TokenSecret' => $TokenSecret,
         'TokenType' => $TokenType,
         'ProviderKey' => $ConsumerKey,
         'Lifetime' => Gdn::Config('Garden.Authenticators.handshake.TokenLifetime', 60),
         'Timestamp' => date('Y-m-d H:i:s',$Timestamp),
         'Authorized' => $Authorized
      );
      
      if ($UserKey !== NULL)
         $InsertArray['ForeignUserKey'] = $UserKey;
         
      $SQL->Insert('UserAuthenticationToken', $InsertArray);
         
      $OToken = new Gdn_OAuthToken($TokenKey, $TokenSecret, $TokenType, $UserKey, $Authorized, $Timestamp, $InsertArray['Lifetime']);
      return $OToken;
   }
   
   protected function _ConvertRequestToken($RequestToken, $OAuthConsumer) {
      
      if ($RequestToken && $RequestToken->UserKey) {
         $OAuthRequest = OAuthRequest::from_consumer_and_token($OAuthConsumer, $RequestToken, "GET", $OAuthConsumer->callback_url, array());
         
         $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
         $OAuthRequest->sign_request($SignatureMethod, $OAuthConsumer, $RequestToken);
         $AccessToken = $this->_OAuthServer->fetch_access_token($OAuthRequest);
         
         if ($AccessToken) {
            if ($RequestToken->UserID)
               $AccessToken->UserID = $RequestToken->UserID;
            
            $this->AssociateRemoteKey($OAuthConsumer->key, $RequestToken->UserKey, $AccessToken->key, $AccessToken->UserID);
            return $AccessToken;
         }
      }
      
      return FALSE;
   }
   
   protected function _Synchronize($RequestToken, $OAuthConsumer, $CookiePayload) {
      $UserEmail = $RequestToken->UserKey;
      $UserName = array_key_exists('name', $CookiePayload) ? $CookiePayload['name'] : NULL;
      
      $TokenHash = array_pop(explode('.',$RequestToken->key));
      $ConsumerKey = $OAuthConsumer->key;
      
      // Check if the user has already been associated, otherwise redirect to the HANDSHAKER OF DOOM!!!
      if (($UserAssociation = $RequestToken->GetAssociation()) !== false ) {
         $AccessToken = $this->_ConvertRequestToken($RequestToken, $OAuthConsumer);
         return $AccessToken;
      } else {
         Gdn::Request()->WithURI('entry/handshake');
      }
      
      return FALSE;
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
         }   
      }
   }
   
   public function GetHandshakeCookie() {
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
   
   public function GetTokenFromCookie($CookiePayload) {
      $TokenKey = $CookiePayload['token'];
      $ConsumerKey = $CookiePayload['consumer_key'];
      foreach (array('request','access') as $TokenType) {
         $Token = $this->lookup_token($ConsumerKey, $TokenType, $TokenKey);
         if ($Token) break;
      }
      return $Token;
   }
   
   public function GetURL($URLType) {
      $SQL = Gdn::Database()->SQL();
      if ($UserID = Gdn::Authenticator()->GetIdentity()) {
         $Provider = $SQL->Select('uap.*')
            ->From('UserAuthenticationProvider uap')
            ->Join('UserAuthentication ua', 'ua.ProviderKey = uap.AuthenticationKey', 'left')
            ->Where('ua.UserID', $UserID)
            ->Get()
            ->FirstRow(DATASET_TYPE_ARRAY);
      } else {
         $Provider = $SQL->Select('uap.*')
            ->From('UserAuthenticationProvider uap')
            ->Get()
            ->FirstRow(DATASET_TYPE_ARRAY);
      }
      
      if ($Provider && $Provider[$URLType])
         return $Provider[$URLType];
      
      return FALSE;
   }
   
   public function WakeUp() {
      $this->FetchData(Gdn::Request());
      $CurrentStep = $this->CurrentStep();
      
      // Shortcircuit to prevent pointless work when the access token has already been handled and we already have a session 
      if ($CurrentStep == Gdn_Authenticator::MODE_REPEAT)
         return;
      
      // Don't try to wakeup when the URL contains an OAuth request
      if ($CurrentStep == Gdn_Authenticator::MODE_VALIDATE)
         return;
      
      if (Gdn::Request()->Filename() == 'handshake.js')
         return;
      
      // Look for handshake cookies
      $Payload = $this->GetHandshakeCookie();
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
      if ($this->UserKey == NULL) 
         return FALSE;
         
      $SQL = Gdn::Database()->SQL();
      $UserAssociation = $SQL->Select('ua.UserID, ua.ForeignUserKey')
         ->From('UserAuthentication ua')
         ->Join('UserAuthenticationToken uat', 'ua.ForeignUserKey = uat.ForeignUserKey', 'left')
         ->Where('ua.ForeignUserKey', $this->UserKey)
         ->Where('uat.Token', $this->key)
         ->Where('UserID >', 0)
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
         
      if ($UserAssociation) {
         $this->UserID = $UserAssociation['UserID'];
         return $UserAssociation;
      }
         
      return FALSE;
   }

}

class Gdn_OAuthConsumer extends OAuthConsumer {

   public $RegistrationURL;
   public $SignInURL;
   public $SignOutURL;

   public function __construct($ConsumerKey, $ConsumerSecret, $ConsumerUrl, $RegistrationUrl, $SignInUrl, $SignOutUrl) {
      parent::__construct($ConsumerKey, $ConsumerSecret, $ConsumerUrl);
      
      $this->RegistrationUrl = $RegistrationUrl;
      $this->SignInUrl = $SignInUrl;
      $this->SignOutUrl = $SignOutUrl;
   }

}