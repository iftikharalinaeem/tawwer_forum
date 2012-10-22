<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['VPI'] = array(
   'Name' => 'VPI',
   'Description' => 'Connect to the Vanilla Programming Interface.',
   'Version' => '1.0a',
   'SettingsUrl' => '',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/todd'
);

class VPIPlugin extends Gdn_Plugin {
   public $ApiUrl = "http://vanilla.local";


   protected $_AccessToken = NULL;

   public function AccessToken($NewValue = NULL, $Expiry = NULL) {
      $CookieName = 'vpi_'.C('VPI.ApplicationID');

      if ($NewValue !== NULL) {
         if ($Expiry === NULL)
            $Expiry = 0;
         else
            $Expiry = time() + $Expiry;

         setcookie($CookieName, $NewValue, $Expiry, C('Garden.Cookie.Path', '/'), C('Garden.Cookie.Domain', ''));
         $_COOKIE[$CookieName] = $NewValue;
         $this->_AccessToken = $NewValue;
      } elseif ($NewValue === FALSE) {
         setcookie($CookieName, $NewValue, 0, C('Garden.Cookie.Path', '/'), C('Garden.Cookie.Domain', ''));
         unset($_COOKIE[$NewValue]);
         $this->_AccessToken = FALSE;
      } elseif ($this->_AccessToken === NULL) {
         $this->_AccessToken = GetValue($CookieName, $_COOKIE);
      }

      return $this->_AccessToken;
   }

   protected function _AuthorizeHref($Method, $Query = FALSE) {
      $Method = strtolower($Method);
      $ClientID = $this->ClientID();
      $RedirectUri = urlencode($this->_RedirectUri($Query));
      $Href = "{$this->ApiUrl}/oauth2/authorize/$Method?client_id=$ClientID&redirect_uri=$RedirectUri";
      if ($Query)
         $Href .= '&'.$Query;
      return $Href;
   }

   protected $_ClientID = NULL;

   public function ClientID() {
      if ($this->_ClientID === NULL)
         $this->_ClientID = C('VPI.ApplicationID');
      return $this->_ClientID;
   }

   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function EntryController_ConnectData_Handler($Sender, $Args) {
      if (GetValue(0, $Args) != 'vpi')
         return;

      $AppID = C('VPI.ApplicationID');
      $Secret = C('VPI.Secret');
      $Code = GetValue('code', $_GET);
      $Query = '';
      if ($Sender->Request->Get('display'))
         $Query = 'display='.urlencode($Sender->Request->Get('display'));

      $RedirectUri = urlencode(ConcatSep('&', $this->RedirectUri(), $Query));

      // Get the access token.
      if ($Code || !($AccessToken = $this->AccessToken())) {
         // Exchange the token for an access token.

         $Url = $this->ApiUrl."/oauth2/token?client_id=$AppID&client_secret=$Secret&code=$Code&redirect_uri=$RedirectUri";

         // Get the redirect URI.
         $Contents = ProxyRequest($Url);
         $Tokens = json_decode($Contents, TRUE);
         if (isset($Tokens['error'])) {
            $Message = $Tokens['error'];
            if (isset($Tokens['error_description']))
               $Message .= ': '.$Tokens['error_description'];
            throw new Exception($Message, 400);
         }

         $AccessToken = GetValue('access_token', $Tokens);
         $Expires = GetValue('expires', $Tokens, NULL);
         $this->AccessToken($AccessToken, $Expires);

         $NewToken = TRUE;
      }

      // Get the profile.
      try {
         $Profile = $this->API('/profile/get.json');
      } catch (Exception $Ex) {
         if (!isset($NewToken)) {
            // There was an error getting the profile, which probably means the saved access token is no longer valid. Try and reauthorize.
            if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
               Redirect($this->AuthorizeUri());
            } else {
               $Sender->SetHeader('Content-type', 'application/json');
               $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
               $Sender->RedirectUrl = $this->AuthorizeUri();
            }
         } else {
            $Sender->Form->AddError('There was an error with the Facebook connection.');
         }
      }

      $Form = $Sender->Form; //new Gdn_Form();
      $ID = GetValue('UserID', $Profile);
      $Form->SetFormValue('UniqueID', $ID);
      $Form->SetFormValue('Provider', 'vpi');
//      $Form->SetFormValue('ProviderName', 'VPI');
      $Form->SetFormValue('Email', GetValue('Email', $Profile));
      $Form->SetFormValue('Photo', GetValue('Photo', $Profile));
      $Sender->SetData('Verified', TRUE);
   }

   public function API($Url) {
      if (strpos($Url, '//') === FALSE) {
         $Url = trim($this->ApiUrl, '/').'/'.trim($Url, '/');
      }
      $AccessToken = $this->AccessToken();
      $Url .= (strpos($Url, '?') === FALSE ? '?' : '&').'access_token='.urlencode($AccessToken);

      // Make a curl request to the API.
      $Response = ProxyRequest($Url);
      $Result = json_decode($Response, TRUE);
      if (isset($Result['Exception'])) {
         throw new Exception(GetValue('Exception', $Result), GetValue('Code', $Result));
      }
      return $Result;
   }

   protected function _RedirectUri($Query = FALSE) {
      $RedirectUri = Url('/entry/connect/vpi', TRUE);

      $Target = GetValue('Target', $_GET, Url(''));
      $RedirectUri .= '?'.http_build_query(array('Target' => $Target));
      if ($Query != FALSE)
         $RedirectUri .= '&'.$Query;

      return $RedirectUri;
   }

   protected function _GetMethod($Method) {
      $ImgSrc = Url('/plugins/VPI/design/'.$Method.'-signin.png');
      $ImgAlt = sprintf(T('Sign In with %s.'), $Method);
      $SigninHref = $this->_AuthorizeHref($Method);
      $PopupSigninHref = $this->_AuthorizeHref($Method, 'display=popup');

      $Result = array(
         'Name' => $Method,
         'SignInHtml' => "<a id=\"{$Method}Auth\" href=\"$SigninHref\" class=\"PopupWindow\" popupHref=\"$PopupSigninHref\" popupHeight=\"326\" popupWidth=\"627\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" /></a>");

      return $Result;
   }

   protected $_RedirectUri = NULL;

   public function RedirectUri($NewValue = NULL) {
      if ($NewValue !== NULL)
         $this->_RedirectUri = $NewValue;
      elseif ($this->_RedirectUri === NULL) {
         $RedirectUri = Url('/entry/connect/vpi', TRUE);
         $Args = array('Target' => GetValue('Target', $_GET, Url('')));

         $RedirectUri .= '?'.http_build_query($Args);
         $this->_RedirectUri = $RedirectUri;
      }

      return $this->_RedirectUri;
   }

   /**
    *
    * @param Gdn_Controller $Sender
    */
   public function EntryController_SignIn_Handler($Sender, $Args) {
      if (!isset($Sender->Data['Methods']))
         return;

      $AccessToken = NULL; //$this->AccessToken();

      // Add the facebook button.
      $FbMethod = $this->_GetMethod('Facebook');
      $Sender->Data['Methods'][] = $FbMethod;

      // Add the twitter button.
      $TwMethod = $this->_GetMethod('Twitter');
      $Sender->Data['Methods'][] = $TwMethod;
   }
}