<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class Oauth2Controller extends Gdn_Controller {
   protected function _CheckRequiredKeys($Keys, $Array = NULL) {
      if ($Array == NULL)
         $Array = $this->Request->Get();

      $Missing = array();

      foreach ($Keys as $Key) {
         if (!array_key_exists($Key, $Array))
            $Missing[] = $Key;
      }

      if (count($Missing) > 0) {
         // The array is missing some keys, return the exception.
         $Message = sprintf(T('The request is missing the following keys: %s.'), implode(', ', $Missing));
         return $Message;
      }
      return TRUE;
   }

   public function Authorize($Method) {
      // Make sure there is a redirect URI.
      if (!$this->Request->Get('redirect_uri')) {
         exit(sprintf(T('ValidateRequired'), 'redirect_uri'));
      }

      // Make sure the client ID checks out.
      $ClientID = $this->Request->GetValueFrom(Gdn_Request::INPUT_GET, 'client_id');
      $ApplicationModel = new ApplicationModel();
      $Application = $ApplicationModel->GetID($ClientID, DATASET_TYPE_ARRAY);
      if (!$Application) {
         Redirect($this->Request->Get('redirect_uri').'&error=invalid_client');
      }

      if ($this->Request->GetValue('display'))
         $Query = 'display='.urlencode($this->Request->GetValue('display'));

      $Plugin = $this->_GetPlugin($Method);
      $Plugin->Authorize($Query);
   }

   /**
    * Connects a user to the system regardless of name clashes.
    */
   public function Connect($Method) {
      $Form = new Gdn_Form();
      $this->Form = $Form;

      // Here are the initial data array values that can be set by a plugin.
      $Data = array('Provider' => '', 'ProviderName' => '', 'UniqueID' => '', 'FullName' => '', 'Name' => '', 'Email' => '', 'Photo' => '', 'Target' => GetIncomingValue('Target', '/'));
      $this->Form->FormValues($Data);

      $Target = $this->Request->GetValue('Target');

      // The different providers can check to see if they are being used and modify the data array accordingly.
      $Plugin = $this->_GetPlugin($Method);
      $this->EventArguments = array($Method);

      try {
         $this->FireEvent('ConnectData');
      } catch (Exception $Ex) {
         // There was an error with the connection. Redirect back to the destination with that information.
         $Url = $this->Request->Get('redirect_uri').'&error=invalid_request&error_description='.urlencode($Ex->getMessage());
         Redirect($Url);
      }

      // Make sure the data has been verified.
      if (!$this->Data('Verified')) {
      }

      // Check to see of there is an existing user with the connection data.
      $UID = $Form->GetFormValue('UniqueID');
      $Provider = $Form->GetFormValue('Provider');

      $UserModel = new UserModel();
      $Auth = $UserModel->GetAuthentication($UID, $Provider);
      if (!$Auth) {
         // Add the user.
         $User = $this->Form->FormValues();
         if (!GetValue('Name', $User)) {
            if ($GetValue('FullName', $User))
               $User['Name'] = $User['FullName'];
            else
               $User['Name'] = 'Anonymous';
         }
         if (!GetValue('Email', $User)) {
            $User['Email'] = '***';
         }
         $User['Password'] = RandomString(50);
         $User['HashMethod'] = 'Random';
         $UserID = $UserModel->InsertForBasic($User);

         if ($UserID) {
            $Auth = array('UniqueID' => $UID, 'Provider' => $Provider, 'UserID' => $UserID);
            $UserModel->SaveAuthentication($Auth);
         } else {
            $this->Form->SetValidationResults($UserModel->ValidationResults());
            $Error = array(
                'error' => 'access_denied',
                'error_description' => Gdn_Format::Text($this->Form->Errors(), FALSE)
            );
            $this->_RedirectError($Error);
         }
         Gdn::Session()->Start($UserID, FALSE);
      } else {
         // Synchronize the user's information.
         $User = $this->Form->FormValues();
         $User['UserID'] = $Auth['UserID'];

         $UserModel->Save($User);
         Gdn::Session()->Start($User['UserID'], FALSE);
      }

      // Issue an authorization code for the user.
      $TokenModel = new OAuth2TokenModel();
      $ExpiresIn = 60 * 5;
      $Token = $TokenModel->Issue('authorization_code', $ExpiresIn);

      // Redirect the code back to the originating page.
      $Url = $this->Request->Get('redirect_uri');
      $Args = array(
          'code' => $Token,
          'expires_in' => $ExpiresIn);
      $Url .= (strpos($Url, '?') === FALSE ? '?' : '&').  http_build_query($Args);
      Redirect($Url);

      $FormData = $this->Form->FormValues(); // debug
   }

   protected function _GetPlugin($Method) {
      switch (strtolower($Method)) {
         case 'facebook':
            $Result = Gdn::PluginManager()->GetPluginInstance('FacebookPlugin');
            break;
         case 'twitter':
            $Result = Gdn::PluginManager()->GetPluginInstance('Twitter2Plugin');
            break;
         default:
            throw new Exception(sprintf(T('Unrecognized authentication method: %s.'), $Type), 400);
            break;
      }
      $Result->RedirectUri(Url('/oauth2/connect/'.urlencode($Method), TRUE).'?redirect_uri='.urlencode($this->Request->GetValue('redirect_uri')));
      return $Result;
   }

   public function Token() {
      $this->DeliveryMethod(DELIVERY_METHOD_JSON);
      $this->DeliveryType(DELIVERY_TYPE_DATA);
      $this->SetHeader('Cache-Control', 'no-store');
      
      $Get = $this->Request->Get();

      // Make sure the required fields have been supplied.
      $CheckRequired = $this->_CheckRequiredKeys(array('client_id', 'client_secret', 'code', 'redirect_uri'));
      if ($CheckRequired !== TRUE) {
         $this->_RenderError(array('error' => 'invalid_request', 'error_description' => $CheckRequired));
      }

      // Confirm the client and secret.
      $ClientID = $this->Request->Get('client_id');
      $Secret = $this->Request->Get('client_secret');
      $Code = $this->Request->Get('code');
      $RedirectUri = $this->Request->Get('redirect_uri');

      // Make sure the client ID and secret matches.
      $ApplicationModel = new ApplicationModel();
      $Application = $ApplicationModel->GetID($ClientID, DATASET_TYPE_ARRAY);
      if (!$Application)
         $this->_RenderError(array('error' => 'invalid_client'));
      elseif ($Application['Secret'] != $Secret)
         $this->_RenderError(array('error' => 'unauthorized_client'));

      // Get the access code.
      $TokenModel = new OAuth2TokenModel();
      $AuthorizationCode = $TokenModel->GetID($Code);
      if ($AuthorizationCode === NULL) {
         $this->_RenderError(array('error' => 'invalid_grant', 'error_description' => 'The authorization code is invalid, expired, or revoked.'));
      } else {
         // Issue the access token.
         $ExpiresIn = 60 * 60 * 3;
         Gdn::Session()->Start($AuthorizationCode['UserID'], FALSE);
         $AccessToken = $TokenModel->Issue('access_token', $ExpiresIn);

         // Remove the authorization code.
         //$TokenModel->Delete($AuthorizationCode);

         // Build the response.
         $this->Data = array(
             'access_token' => $AccessToken,
             'expires_in' => $ExpiresIn);
         $this->Render();
      }
   }

   protected function _RenderError($Error) {
      header('HTTP/1.0 400 (Bad Request)', TRUE, 400);
      exit (json_encode($Error));
   }

   protected function _RedirectError($Error) {
      $Url = $this->Request->Get('redirect_uri');
      $Url .= (strpos($Url, '?') !== FALSE ? '&' : '?').http_build_query($Error);
      Redirect($Url);
   }
}