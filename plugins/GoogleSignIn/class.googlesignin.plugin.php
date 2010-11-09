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
$PluginInfo['GoogleSignIn'] = array(
	'Name' => 'Google Sign In',
   'Description' => 'This plugin allows users to sign in with their Google accounts. You must register your domain with Google for this plugin to work.',
   'Version' => '0.1a',
   'RequiredApplications' => array('Vanilla' => '2.0.14'),
   'RequiredPlugins' => array('OpenID' => '0.1a'),
   'RequiredTheme' => FALSE,
	'MobileFriendly' => TRUE,
   'SettingsUrl' => '/dashboard/plugin/googlesignin',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class GoogleSignInPlugin extends Gdn_Plugin {

   /// Properties ///

   protected function _AuthorizeHref($Popup = FALSE) {
      $Result = Url('/entry/openid', TRUE);
      $Query = array('url' => 'https://www.google.com/accounts/o8/id');
      if (isset($_GET['Target']))
         $Query['Target'] = $_GET['Target'];
      if ($Popup)
         $Query['display'] = 'popup';

      if (count($Query) > 0)
         $Result .= '?'.http_build_query ($Query);
      return $Result;
   }
   
   /**
    * Act as a mini dispatcher for API requests to the plugin app
    */
   public function PluginController_GoogleSignIn_Create(&$Sender) {
		$this->Dispatch($Sender, $Sender->RequestArgs);
   }
   
   public function Controller_Toggle($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $this->AutoToggle($Sender);
   }
   
   public function AuthenticationController_Render_Before($Sender, $Args) {
      if (isset($Sender->ChooserList)) {
         $Sender->ChooserList['googlesignin'] = 'Google';
      }
      if (is_array($Sender->Data('AuthenticationConfigureList'))) {
         $List = $Sender->Data('AuthenticationConfigureList');
         $List['googlesignin'] = '/dashboard/plugin/googlesignin';
         $Sender->SetData('AuthenticationConfigureList', $List);
      }
   }

   /// Plugin Event Handlers ///

   /**
    *
    * @param Gdn_Controller $Sender
    */
   public function EntryController_SignIn_Handler($Sender, $Args) {
      if (!$this->IsEnabled()) return;
      
      if (isset($Sender->Data['Methods'])) {
         $ImgSrc = Asset('/plugins/GoogleSignIn/design/google-signin.png');
         $ImgAlt = T('Sign In with Google');
         $SigninHref = $this->_AuthorizeHref();
         $PopupSigninHref = $this->_AuthorizeHref(TRUE);

         // Add the twitter method to the controller.
         $Method = array(
            'Name' => 'Google',
            'SignInHtml' => "<a id=\"GoogleAuth\" href=\"$SigninHref\" class=\"PopupWindow\" popupHref=\"$PopupSigninHref\" popupHeight=\"400\" popupWidth=\"800\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" /></a>");

         $Sender->Data['Methods'][] = $Method;
      }
   }

   public function Base_BeforeSignInButton_Handler($Sender, $Args) {
      if (!$this->IsEnabled()) return;
      
      $ImgSrc = Asset('/plugins/GoogleSignIn/design/google-icon.png');
      $ImgAlt = T('Sign In with Google');
      $SigninHref = $this->_AuthorizeHref();
      $PopupSigninHref = $this->_AuthorizeHref(TRUE);

      $Html = "\n<a id=\"GoogleAuth\" href=\"$SigninHref\" class=\"PopupWindow\" title=\"$ImgAlt\" popupHref=\"$PopupSigninHref\" popupHeight=\"400\" popupWidth=\"800\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" /></a>";

      echo $Html;
   }
}