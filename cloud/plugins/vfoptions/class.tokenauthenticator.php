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
 * Authentication Module: Local User/Password auth tokens.
 * 
 * @author Mark O'Sullivan
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

/**
 * Validating, Setting, and Retrieving session data in cookies. The HMAC
 * Hashing method used here was inspired by Wordpress 2.5 and this document in
 * particular: http://www.cse.msu.edu/~alexliu/publications/Cookie/cookie.pdf
 *
 * @package Garden
 */
class Gdn_TokenAuthenticator extends Gdn_Authenticator {
   
   public function __construct() {
   
      if (!C('Garden.Authenticators.token.Name',FALSE))
         SaveToConfig('Garden.Authenticators.token.Name','VFOptions Token');
   
      $this->_DataSourceType = Gdn_Authenticator::DATA_REQUEST;
      $this->HookDataField('Token', 'token');
      
      // Initialize built-in authenticator functionality
      parent::__construct();
   }

   /**
    * Returns the unique id assigned to the user in the database, 0 if the
    * username/password combination weren't found, or -1 if the user does not
    * have permission to sign in.
    */
   public function Authenticate($Token = NULL) {
      if (is_null($Token)) {
         return 0;
      }
      
      if (C('Garden.Authenticators.token.Token', NULL) != $Token) {
         return 0;
      }
         
      $Expiry = C('Garden.Authenticators.token.Expiry', 0);
      if ($Expiry != 0 && strtotime($Expiry) < time()) {
         RemoveFromConfig('Garden.Authenticators.token.Token');
         RemoveFromConfig('Garden.Authenticators.token.Expiry');
         return 0;
      }
      
      $UserID = Gdn::UserModel()->GetSystemUserID();
      
      // One use token, gets removed immediately
      RemoveFromConfig('Garden.Authenticators.token.Token');
      RemoveFromConfig('Garden.Authenticators.token.Expiry');
      
      return $UserID;
   }
   
   /**
    * Destroys the user's session cookie - essentially de-authenticating them.
    */
   public function DeAuthenticate() {
      $this->SetIdentity(NULL);

      return Gdn_Authenticator::AUTH_SUCCESS;
   }

   public function LoginResponse() {
      return Gdn_Authenticator::REACT_RENDER;
   }

   public function PartialResponse() {
      return Gdn_Authenticator::REACT_REDIRECT;
   }

   public function SuccessResponse() {
      return Gdn_Authenticator::REACT_REDIRECT;
   }

   public function LogoutResponse() {
      return Gdn_Authenticator::REACT_REDIRECT;
   }

   public function RepeatResponse() {
      return Gdn_Authenticator::REACT_RENDER;
   }

   // What to do if the entry/auth/* page is triggered but login is denied or fails
   public function FailedResponse() {
      return Gdn_Authenticator::REACT_RENDER;
   }
   
   public function CurrentStep() {
      $Id = Gdn::Authenticator()->GetRealIdentity();
      
      // Check token and token expiry
      if (C('Garden.Authenticators.token.Token', FALSE) === FALSE) {
         return Gdn_Authenticator::MODE_NOAUTH;
      }
      $Expiry = C('Garden.Authenticators.token.Expiry', 0);
      if ($Expiry != 0 && strtotime($Expiry) < time()) {
         return Gdn_Authenticator::MODE_NOAUTH;
      }
      
      if ($Id == 0 || $Id == -1) {
         $this->_CheckHookedFields();
         return Gdn_Authenticator::MODE_GATHER;
      }
      if ($Id) return Gdn_Authenticator::MODE_REPEAT;
      return Gdn_Authenticator::MODE_NOAUTH;
   }
   
   public function WakeUp() {
      
      $CurrentStep = $this->CurrentStep();
      
      // Shortcircuit to prevent pointless work when the access token has already been handled and we already have a session 
      if ($CurrentStep == Gdn_Authenticator::MODE_REPEAT)
         return;
         
      // Don't try to wakeup when we're throttled, or when there is no available token anyway.
      if ($CurrentStep == Gdn_Authenticator::MODE_NOAUTH)
         return;
         
      $this->FetchData(Gdn::Request());
      
      $Token = $this->GetValue('Token', NULL);
      $UserID = $this->Authenticate($Token);
      if ($UserID > 0) {
         // The user authenticated. We want to set the session, not the identity.
         Gdn::Session()->Start($UserID, FALSE);
         Gdn::Session()->User->Token = TRUE;
      }
   }
   
   public function GetURL($URLType) {
      // We arent overriding anything
      return FALSE;
   }

}