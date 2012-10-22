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
 *
 * @package Garden
 */
class Gdn_Oauth2Authenticator extends Gdn_Authenticator {
   public function __construct() {
      // Initialize built-in authenticator functionality
      parent::__construct();
   }

   /**
    * Returns the unique id assigned to the user in the database, 0 if the
    * username/password combination weren't found, or -1 if the user does not
    * have permission to sign in.
    *
    * @param mixed $AccessToken The access token to check.
    */
   public function Authenticate($AccessToken = NULL) {
      if ($AccessToken === NULL) {
         $AccessToken = Gdn::Request()->GetValue('access_token', NULL);
      }

      $UserID = 0;

      // Check to see of the access token authenticates.
      if ($AccessToken) {
         $TokenModel = new OAuth2TokenModel();
         $Token = $TokenModel->GetID($AccessToken);
         if ($Token) {
            $UserID = GetValue('UserID', $Token, 0);
            $TokenModel->Touch($Token);
            Gdn::Authenticator()->Trigger(Gdn_Authenticator::AUTH_SUCCESS);
         } else {
            Gdn::Authenticator()->Trigger(Gdn_Authenticator::AUTH_DENIED);
         }
      } else {
         Gdn::Authenticator()->Trigger(Gdn_Authenticator::AUTH_DENIED);
      }

      return $UserID;
      
   }

   public function CurrentStep() {
      return Gdn_Authenticator::MODE_GATHER;
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

   public function WakeUp() {
      // Is there an access token?
      if (Gdn::Request()->GetValue('access_token', FALSE) !== FALSE) {
         $UserID = $this->Authenticate();
         if ($UserID > 0) {
            // The user authenticated. We want to set the session, not the identity.
            Gdn::Session()->Start($UserID, FALSE);
         }
      }
   }

   public function GetURL($URLType) {
      // We arent overriding anything
      return FALSE;
   }
}