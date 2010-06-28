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
$PluginInfo['ProxyConnect'] = array(
	'Name' => 'Proxy Connect SSO',
   'Description' => 'This plugin enables SingleSignOn (SSO) between your forum and other authorized consumers on the same domain, via cookie sharing.',
   'Version' => '1.2',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'SettingsUrl' => '/dashboard/settings/proxyconnect',
   'SettingsPermission' => 'Garden.AdminUser.Only',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Mark O'Sullivan, Tim Gunter",
   'AuthorEmail' => 'mark@vanillaforums.com, tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

Gdn_LibraryMap::SafeCache('library','class.proxyauthenticator.php',dirname(__FILE__).DS.'class.proxyauthenticator.php');
class ProxyConnectPlugin extends Gdn_Plugin {

   /**
    * Adds "ProxyConnect" menu option to the dashboard.
    */
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Users', 'Proxy Connect', 'settings/proxyconnect', 'Garden.AdminUser.Only');
   }
   
   public function SettingsController_ProxyConnect_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
		
      $Sender->Title('Proxy Connect SSO');
      $Sender->AddSideMenu('settings/proxyconnect');
		$Sender->AddCssFile('/plugins/ProxyConnect/proxyconnect.css');
		$Sender->Form = new Gdn_Form();
		$this->Dispatch($Sender, $Sender->RequestArgs);
   }
   
   public function Controller_Index(&$Sender) {
      $SQL = Gdn::Database()->SQL();
      $Provider = $SQL->Select('uap.AuthenticationKey')
         ->From('UserAuthenticationProvider uap')
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
         
      if (!$Provider)
         $Provider = $this->_CreateProviderModel();
      
      if ($Provider) {
         $ConsumerKey = $Provider['AuthenticationKey'];
         $ProviderModel = new Gdn_AuthenticationProviderModel();
         $Provider = $ProviderModel->GetProviderByKey($ConsumerKey);
         $Sender->Form->SetModel($ProviderModel);
         
         if (!$Sender->Form->AuthenticatedPostBack()) {
            $Sender->Form->SetData($Provider);
         } else if (C('Plugins.ProxyConnect.Enabled')) {
            $ProviderModel->Validation->ApplyRule('URL',             'Required');
            $ProviderModel->Validation->ApplyRule('RegisterUrl',     'Required');
            $ProviderModel->Validation->ApplyRule('SignInUrl',       'Required');
            $ProviderModel->Validation->ApplyRule('SignOutUrl',      'Required');
				$Sender->Form->SetFormValue('AuthenticationKey', $ConsumerKey);
            $Sender->Form->Save();
         }
      }
      
      $Sender->ConsumerKey = ($Provider) ? $Provider['AuthenticationKey'] : '';
      $Sender->ConsumerSecret = ($Provider) ? $Provider['AssociationSecret'] : '';
      
      $Sender->Render($this->GetView('proxyconnect.php'));
   }
   
   public function Controller_Toggle(&$Sender) {
		
		// Enable/Disable VanillaConnect
		if (Gdn::Session()->ValidateTransientKey(GetValue(1, $Sender->RequestArgs))) {
			if (C('Plugins.ProxyConnect.Enabled')) {
				$this->_Disable();
			} else {
				$this->_Enable();
			}
			Redirect('settings/proxyconnect');
		}
   }
   
   public function Setup() {
		// Do nothing
   }
   
   public function OnDisable() {
		$this->_Disable();
   }
   
   protected function _CreateProviderModel() {
      $Key = 'k'.sha1(implode('.',array(
         'proxyconnect',
         'key',
         microtime(true),
         RandomString(16),
         Gdn::Session()->User->Name
      )));
      
      $Secret = 's'.sha1(implode('.',array(
         'proxyconnect',
         'secret',
         md5(microtime(true)),
         RandomString(16),
         Gdn::Session()->User->Name
      )));
      
      $ProviderModel = new Gdn_AuthenticationProviderModel();
      $ProviderModel->Insert($Provider = array(
         'AuthenticationKey'           => $Key,
         'AuthenticationSchemeAlias'   => 'handshake',
         'URL'                         => 'Enter your site url',
         'AssociationSecret'           => $Secret,
         'AssociationHashMethod'       => 'HMAC-SHA1'
      ));
      
      return $Provider; 
   }
   
   private function _Disable() {
		RemoveFromConfig('Garden.SignIn.Popup');
		RemoveFromConfig('Plugins.ProxyConnect.Enabled');
		RemoveFromConfig('Garden.Authenticator.DefaultScheme');
      RemoveFromConfig('Garden.Authenticators.proxy.CookieName');

      $EnabledSchemes = Gdn::Config('Garden.Authenticator.EnabledSchemes', array());
      foreach ($EnabledSchemes as $SchemeIndex => $SchemeKey) {
         if ($SchemeKey == 'proxy')
            unset($EnabledSchemes[$SchemeIndex]);
      }
      SaveToConfig('Garden.Authenticator.EnabledSchemes', $EnabledSchemes);
   }
	
	private function _Enable() {
		SaveToConfig('Garden.SignIn.Popup', FALSE);
		SaveToConfig('Plugins.ProxyConnect.Enabled', TRUE);
		SaveToConfig('Garden.Authenticator.DefaultScheme', 'proxy');
      SaveToConfig('Garden.Authenticators.proxy.CookieName', 'VanillaProxy');
      
      $EnabledSchemes = Gdn::Config('Garden.Authenticator.EnabledSchemes', array());
      $HaveProxy = FALSE;
      foreach ($EnabledSchemes as $SchemeIndex => $SchemeKey) {
         if ($SchemeKey == 'proxy') {
            if ($HaveProxy === TRUE)
               unset($EnabledSchemes[$SchemeIndex]);
            $HaveProxy = TRUE;
         }
      }
      if (!$HaveProxy)
         array_push($EnabledSchemes, 'proxy');
      
      SaveToConfig('Garden.Authenticator.EnabledSchemes', $EnabledSchemes);
      
      // Create a provider key/secret pair if needed
      $SQL = Gdn::Database()->SQL();
      $Provider = $SQL->Select('uap.*')
         ->From('UserAuthenticationProvider uap')
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
         
      if (!$Provider)
         $this->_CreateProviderModel();
	}  
}