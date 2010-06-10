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
 * Validates sessions by handshaking with another site by means of direct socket connection
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package Garden
 */
require_once(implode(DS, array(PATH_LIBRARY,'vendors','oauth','OAuth.php')));

class Gdn_ProxyAuthenticator extends Gdn_Authenticator {

   protected $_CookieName = NULL;
   
   public function __construct() {
   
      // This authenticator gets its data directly from the request object, always
      $this->_DataSourceType = Gdn_Authenticator::DATA_NONE;
      
      // Which cookie signals the presence of an authentication package?
      $this->_CookieName = Gdn::Config('Garden.Authenticators.handshake.CookieName');
      
      // Initialize built-in authenticator functionality
      parent::__construct();
   }
   
   public function Authenticate() {
      //return false;
      $ForeignIdentityUrl = C('Garden.Authenticator.AuthenticateURL');
      if (!$ForeignIdentityUrl) return FALSE;
      
      try {
         $Response = $this->_GetForeignCredentials($ForeignIdentityUrl);
         if (!$Response) throw new Exception();
         
         $SQL = Gdn::Database()->SQL();
         $Provider = $SQL->Select('uap.AuthenticationKey, uap.AssociationSecret')
            ->From('UserAuthenticationProvider uap')
            ->Get()
            ->FirstRow(DATASET_TYPE_ARRAY);
         if (!$Provider) throw new Exception();
         
         // Got a response from the remote identity provider
         $ConsumerNotifyUrl = '/entry/auth/handshake/handshake.js'; $SignedConsumerNotifyUrl = Url($ConsumerNotifyUrl, TRUE);
         $UserEmail = ArrayValue('Email', $Response);
         $UserName = ArrayValue('Name', $Response);
         $UserID = ArrayValue('UniqueID', $Response);
         
         $UserName = trim(preg_replace('/[^a-z0-9-]+/i','',$UserName));
         $UserID = 1;
         
         // Build an OAuth request from the provided information
         $OAuthConsumer = new OAuthConsumer($Provider['AuthenticationKey'], $Provider['AssociationSecret']);
         $ConsumerParams = array("email" => $UserEmail, "name" => $UserName, "uid" => $UserID);
         $OAuthRequest = OAuthRequest::from_consumer_and_token($OAuthConsumer, null, "GET", $SignedConsumerNotifyUrl, $ConsumerParams);
         $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
         $OAuthRequest->sign_request($SignatureMethod, $OAuthConsumer, null);
         
         // Strip out the information from the resulting OAuth URL
         $Handshake = $OAuthRequest->to_url();
         $Params = explode('?', $Handshake);
         array_shift($Params);
         parse_str(implode('?', $Params), $RequestParams);
         
         // Fake the OAuth arguments in the [GET] array, since OAuth library snoopes the actual headers like a tool
         $_GET = array_merge($_GET,$RequestParams);
         
         // Forward Garden to the handshake.js internally so that Authenticate() feels happy
         Gdn::Request()->WithURI($ConsumerNotifyUrl)->WithArgs(Gdn_Request::INPUT_GET)->WithCustomArgs($RequestParams);
         $HandshakeAuthenticator = Gdn::Authenticator()->AuthenticateWith('handshake');
         $HandshakeAuthenticator->FetchData(Gdn::Request());
         
         // Piggyback on top of HandshakeAuthenticator's Authentication
         $AuthResponse = $HandshakeAuthenticator->Authenticate();
         
         if ($AuthResponse == Gdn_Authenticator::AUTH_PARTIAL) {
            Redirect(Url('/entry/handshake',TRUE),302);
         } else {
            Gdn::Request()->WithRoute('DefaultController');
            throw new Exception('authentication failed');
         }
         
      }
      catch (Exception $e) {
         // Fallback to defer checking until the next session
         $this->SetIdentity(-1, FALSE);
      }
   }
   
   public function DeAuthenticate() {
      
   }
   
   // What to do if entry/auth/* is called while the user is logged out. Should normally be REACT_RENDER
   public function LoginResponse() {
      return Gdn_Authenticator::REACT_RENDER;
   }
   
   // What to do after part 1 of a 2 part authentication process. This is used in conjunction with OAauth/OpenID type authentication schemes
   public function PartialResponse() {
      return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   // What to do after authentication has succeeded. 
   public function SuccessResponse() {
      return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   // What to do if the entry/auth/* page is triggered for a user that is already logged in
   public function RepeatResponse() {
      return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   public function GetURL($URLType) {
      // We arent overriding anything
      return FALSE;
   }
   
   protected function _GetForeignCredentials($ForeignIdentityUrl) {
      $Response = ProxyRequest($ForeignIdentityUrl,5);
      if ($Response) {
         $Result = @parse_ini_string($Response);
         if ($Result) {
            $ReturnArray = array(
               'Email'     => ArrayValue('Email', $Result),
               'Name'      => ArrayValue('Name', $Result),
               'UniqueID'  => ArrayValue('UniqueID', $Result)
            );
            return $ReturnArray;
         }
      }
      return FALSE;
   }
   
   public function CurrentStep() {
      $Id = Gdn::Authenticator()->GetRealIdentity();
      
      if (!$Id) return Gdn_Authenticator::MODE_GATHER;
      if ($Id > 0) return Gdn_Authenticator::MODE_REPEAT;
      if ($Id < 0) return Gdn_Authenticator::MODE_NOAUTH;
   }
   
   public function WakeUp() {
      $ForeignIdentityUrl = C('Garden.Authenticator.AuthenticateURL');
      if (!$ForeignIdentityUrl) return FALSE;
      
      $HaveHandshake = Gdn_CookieIdentity::CheckCookie($this->_CookieName);
      if ($HaveHandshake) return;
      
      $CurrentStep = $this->CurrentStep();
      
      if (substr(Gdn::Request()->Path(),0,6) != 'entry/') {
      
         // Shortcircuit to prevent pointless work when the access token has already been handled and we already have a session 
         if ($CurrentStep == Gdn_Authenticator::MODE_REPEAT)
            return;
            
         // Don't try to wakeup when we've already tried once this session
         if ($CurrentStep == Gdn_Authenticator::MODE_NOAUTH)
            return;
            
      }
            
      $this->Authenticate();
   }
   
}