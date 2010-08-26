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
   'Version' => '1.6',
   'RequiredApplications' => array('Vanilla' => '2.0.2a'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'SettingsUrl' => '/dashboard/authentication/proxy',
   'SettingsPermission' => 'Garden.AdminUser.Only',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

Gdn_LibraryMap::SafeCache('library','class.proxyauthenticator.php',dirname(__FILE__).DS.'class.proxyauthenticator.php');
class ProxyConnectPlugin extends Gdn_Plugin {

   public function SettingsController_ProxyConnect_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Proxy Connect SSO');
		$Sender->Form = new Gdn_Form();
		
		$this->EnableSlicing($Sender);
		$this->Dispatch($Sender, $Sender->RequestArgs);
   }
   
   public function AuthenticationController_AuthenticatorConfigurationProxy_Handler(&$Sender) {
      $Sender->AuthenticatorConfigure = '/dashboard/settings/proxyconnect';
   }
   
   public function Controller_Index(&$Sender) {
      $this->AddSliceAsset($this->GetResource('proxyconnect.css', FALSE,FALSE));
      
      $SQL = Gdn::Database()->SQL();
      $Provider = $SQL->Select('uap.AuthenticationKey')
         ->From('UserAuthenticationProvider uap')
         ->Where('uap.AuthenticationSchemeAlias', 'proxy')
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
            $Provider['AuthenticateURL'] = C('Garden.Authenticator.AuthenticateURL');
            $Sender->Form->SetData($Provider);
         } else if (C('Plugins.ProxyConnect.Enabled')) {
            $ProviderModel->Validation->ApplyRule('URL',             'Required');
            $ProviderModel->Validation->ApplyRule('RegisterUrl',     'Required');
            $ProviderModel->Validation->ApplyRule('SignInUrl',       'Required');
            $ProviderModel->Validation->ApplyRule('SignOutUrl',      'Required');
				$Sender->Form->SetFormValue('AuthenticationKey', $ConsumerKey);
				$Sender->Form->SetFormValue('AuthenticationSchemeAlias', 'proxy');
            $Saved = $Sender->Form->Save();
            
            SaveToConfig('Garden.Authenticator.AuthenticateURL', $Sender->Form->GetValue('AuthenticateURL'));
         }
      }
      
      $Sender->ConsumerKey = ($Provider) ? $Provider['AuthenticationKey'] : '';
      $Sender->ConsumerSecret = ($Provider) ? $Provider['AssociationSecret'] : '';
      
      $Sender->SliceConfig = $this->RenderSliceConfig();
      $Sender->Render($this->GetView('proxyconnect.php'));
   }
   
   public function Controller_Cookie(&$Sender) {
      $ExplodedDomain = explode('.',Gdn::Request()->RequestHost());
      if (sizeof($ExplodedDomain) == 1)
         $GuessedCookieDomain = '';
      else {
         $GuessedCookieDomain = '.'.implode('.',array_slice($ExplodedDomain,-2,2));
      }
      
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('Plugin.ProxyConnect.NewCookieDomain'));
      
      // Set the model on the form.
      $Sender->Form->SetModel($ConfigurationModel);
      
      if ($Sender->Form->AuthenticatedPostBack()) {
         $NewCookieDomain = $Sender->Form->GetValue('Plugin.ProxyConnect.NewCookieDomain', '');
         SaveToConfig('Garden.Cookie.Domain', $NewCookieDomain);
      } else { 
         $NewCookieDomain = $GuessedCookieDomain;
      }
      
      $Sender->SetData('GuessedCookieDomain', $GuessedCookieDomain);
      $CurrentCookieDomain = C('Garden.Cookie.Domain');
      $Sender->SetData('CurrentCookieDomain', $CurrentCookieDomain);
      
      $Sender->Form->SetData(array(
         'Plugin.ProxyConnect.NewCookieDomain'  => $NewCookieDomain
      ));
      
      $Sender->Render($this->GetView('cookie.php'));
   }
   
   public function EntryController_SigninLoopback_Create(&$Sender) {
      $Args = $Sender->RequestArgs;
      $Redirect = (sizeof($Args)) ? $Args[0] : '/';

      $RealSigninURL = Gdn::Authenticator()->GetURL('Real'.Gdn_Authenticator::URL_SIGNIN, $Redirect);
      $RealUserID = Gdn::Authenticator()->GetRealIdentity();
      $Authenticator = Gdn::Authenticator()->GetAuthenticator('proxy');
      if ($RealUserID == -1) {
         $Authenticator->Authenticate();
         if (Gdn::Authenticator()->GetIdentity()) {
            Redirect(Gdn::Router()->GetDestination('DefaultController'), 302);
         } else {
            $Authenticator->SetIdentity(NULL);
            Redirect($RealSigninURL,302);
         }
      } else {
         if ($RealUserID) Redirect(Gdn::Router()->GetDestination('DefaultController'), 302);
         else {
            $Authenticator->SetIdentity(NULL);
            Redirect($RealSigninURL,302);
         }
      }
   }
   
   public function Setup() {
		$NumLookupMethods = 0;
		
		if (function_exists('fsockopen')) $NumLookupMethods++;
		if (function_exists('curl_init')) $NumLookupMethods++;

		if (!$NumLookupMethods)
		   throw new Exception(T("Unable to initialize plugin: required connectivity libraries not found, need either 'fsockopen' or 'curl'."));
		   
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
      
      $this->_Enable(FALSE);
   }
   
   public function OnDisable() {
		$this->_Disable();
		
		// Remove this authenticator from the enabled schemes collection.
      $EnabledSchemes = Gdn::Config('Garden.Authenticator.EnabledSchemes', array());
      foreach ($EnabledSchemes as $SchemeIndex => $SchemeKey) {
         if ($SchemeKey == 'proxy')
            unset($EnabledSchemes[$SchemeIndex]);
      }
      SaveToConfig('Garden.Authenticator.EnabledSchemes', $EnabledSchemes);
		
		RemoveFromConfig('Garden.Authenticators.proxy.Name');
      RemoveFromConfig('Garden.Authenticators.proxy.CookieName');
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
         'AuthenticationSchemeAlias'   => 'proxy',
         'URL'                         => 'Enter your site url',
         'AssociationSecret'           => $Secret,
         'AssociationHashMethod'       => 'HMAC-SHA1'
      ));
      
      return $Provider; 
   }
   
   public function AuthenticationController_DisableAuthenticatorProxy_Handler(&$Sender) {
      $this->_Disable();
   }
   
   private function _Disable() {
      RemoveFromConfig('Plugins.ProxyConnect.Enabled');
		RemoveFromConfig('Garden.SignIn.Popup');
		RemoveFromConfig('Garden.Authenticator.DefaultScheme');
   }
	
   public function AuthenticationController_EnableAuthenticatorProxy_Handler(&$Sender) {
      $this->_Enable();
   }
	
	private function _Enable($FullEnable = TRUE) {
		SaveToConfig('Garden.SignIn.Popup', FALSE);
		SaveToConfig('Garden.Authenticators.proxy.Name', 'ProxyConnect');
      SaveToConfig('Garden.Authenticators.proxy.CookieName', 'VanillaProxy');
      
      if ($FullEnable) {
         SaveToConfig('Garden.Authenticator.DefaultScheme', 'proxy');
         SaveToConfig('Plugins.ProxyConnect.Enabled', TRUE);
      }
      
      // Create a provider key/secret pair if needed
      $SQL = Gdn::Database()->SQL();
      $Provider = $SQL->Select('uap.*')
         ->From('UserAuthenticationProvider uap')
         ->Where('uap.AuthenticationSchemeAlias', 'proxy')
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
         
      if (!$Provider)
         $this->_CreateProviderModel();
	}  
}