<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class OldoauthController extends VpiController {

   /** Bring a user to the authorization page.
    */
   public function Authorize($Type = '') {
      $Params = $_GET;

      // Cast the authorization type to one of the known types.
      if (!in_array($Type, array('facebook', 'twitter')))
         $Type = 'all';

      // Make sure the required parameters have been passed in.
      $Ex = $this->_CheckRequiredKeys(array('client_id', 'redirect_uri'), $Params);
      if (is_a($Ex, 'VPIException')) {
//         $Ex->SetHeader($this);
//         return $this->_RenderData($Ex->ToOAuth());
         $Ex->SetData($this);
         $this->Render();
      }
      
      // Make sure the application is registered.
      $Application = $this->ApplicationModel()->GetID(GetValue('client_id', $Params, 0), DATASET_TYPE_ARRAY);
      if (!is_array($Application)) {
         $Ex = new VPIException(sprintf(T('Invalid client_id: %s.'), $Params['client_id']), 'invalid_client');
         $Ex->SetHeader();
         return $this->_RenderData($Ex->ToOAuth());
      }

      // Check to see if the user is already signed in.
      if (Gdn::Session()->UserID > 0) {
         return $this->AuthorizeCode($Type);
      }

      // Redirect to the appropriate authorization page.
      $RedirectUri = urlencode("{$this->Url}/authorize2/$Type");
      $State = $Params['redirect_uri'];
      if (isset($Params['state'])) {
         $State .= '|'.$Params['state'];
      }
      $State = base64_encode($State);

      switch (strtolower($Type)) {
         case 'facebook':
            $FbClientID = C('Facebook.ApplicationID');
            $Url = "https://graph.facebook.com/oauth/authorize2?client_id=$FbClientID&redirect_uri={$RedirectUri}&state=$State";
            Redirect($Url);
            break;
         case 'twitter':
            
            break;
         default:
            // Set the state to the correct format because this version will not necessarily redirect.
            $_GET['state'] = $State;
            return $this->AuthorizeVanilla();
      }
      
   }

   public function Authorize2($Type) {
      $Params = $_GET;

      // Cast the authorization type to one of the known types.
      if (!in_array($Type, array('facebook', 'twitter')))
         $Type = 'all';

      // Make sure the required parameters are returned.
      $Ex = $this->_CheckRequiredKeys(array('code', 'state'));
      if (is_a($Ex, 'VPIException')) {
         $Ex->SetHeader();
         return $this->_RenderData($Ex->ToOAuth());
      }

      // Make sure the authorization code was returned.
      if (!isset($Params['code'])) {
         
      }

      $RedirectUri = "{$this->BaseUrl}/authorize2/$Type";

      // Get the access token from an external type.
      switch (strtolower($Type)) {
         case 'facebook':
            // Get the facebook_access_token.
            $FbApplicationID = 'Facebook.ApplicationID';
            $FbSecret = 'Facebook.Secret';
            $Url = "https://graph.facebook.com/oauth/access_token?client_id=$FbApplicationID&redirect_uri=$RedirectUri&client_secret=$FbSecret&code={$Params['code']}";
            $Contents = file_get_contents($Url);
            parse_str($Contents, $Tokens);
            $AccessToken = GetValue('access_token', $Tokens);
            $ExpiresIn = GetValue('expires', $Tokens);

            // Save the facebook token as a preference.
            Gdn::Session()->SetPreference('fb_access_token', $AccessToken, TRUE);
            break;
         case 'twitter':

            break;
      }

      // Issue the user a new authorization code.
      $TokenModel = new OAuth2TokenModel();
      $Code = $TokenModel->Issue('authorization_code');

      // Redirect to the apprioriate url with the given url.
      $StateParts = explode('|', base64_decode(GetValue('state', $Params)));
      $Url = "$State[0]?code=$Code";

      // Add the state if specified.
      if (count($StateParts) > 1) {
         $Url .= '&state='.urlencode($StateParts[1]);
      }
      Redirect($Url);
   }

   /**
    * Authorize with a vanilla username and password.
    */
   public function AuthorizeVanilla() {
      if (Gdn::Session()->User) {
         // The user is already signed in, issue them an authorization code.
         $_GET['code'] = '***';
         return $this->Authorize2('vanilla');
      } else {
         // Present the user with the signin page.
         Gdn::Session()->Start(1); // testing only.
         return $this->Authorize2('vanilla');
      }
   }

   /**
    * Issue an access token
    */
   public function Token() {
      $Params = $_POST;

      // Make sure the required parameters have been passed in.
      $CheckRequired = $this->_CheckRequiredKeys(array('client_id', 'client_secret', 'code'), $Params);
      if (is_a($CheckRequired, 'VPIException')) {
         $Ex->SetHeader();
         return $this->_RenderData($CheckRequired->ToOAuth());
      }

      // Make sure the application is registered.
      $Application = $this->ApplicationModel()->GetID(GetValue('client_id', $Params, 0), DATASET_TYPE_ARRAY);
      if (!is_array($Application)) {
         $Ex = new VPIException(sprintf(T('OAuth.invalid_client'), $Params['client_id']), 'invalid_client');
         $Ex->SetHeader();
         return $this->_RenderData($Ex->ToOAuth());
      }
      // Make sure the application secret matches.
      if ($Application['Secret'] != $Params['client_secret']) {
         $Ex = new VPIException(T('The client_secret was incorrect.'), 'unauthorized_client');
         $Ex->SetHeader();
         return $this->_RenderData($Ex->ToOAuth());
      }

      // TODO: Make sure the redirect_uri matches.

      // Get the authorization code.
      $TokenModel = new OAuth2TokenModel();
      $TokenRow = $TokenModel->GetID($Params['code']);
      if ($TokenRow == FALSE || $TokenModel::Expired($TokenRow)) {
         $Ex = new VIPException(T('Invalid authorization code.'), 'access_denied');
         return $this->_RenderData($Ex->ToOAuth());
      }

      // Issue the access_token.
      $ExpiresIn = C('VPI.OAuth.ExpiresIn', 0);
      $AccessToken = $TokenModel->Issue('access_token', $ExpiresIn);

      $ResultArray = array(
         'access_token' => $AccessToken,
         'expires_in' => $ExpiresIn);

      header('Cache-Control: no-store');
      return $this->_RenderData($ResultArray);
   }
}