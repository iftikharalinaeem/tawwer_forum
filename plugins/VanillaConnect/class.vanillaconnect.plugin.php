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
$PluginInfo['VanillaConnect'] = array(
	'Name' => 'Vanilla Connect',
   'Description' => 'This plugin enables SingleSignOn (SSO) between your forum and other authorized consumers.',
   'Version' => '1.3.1',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => array('Vanilla' => '2.0.7'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'SettingsUrl' => '/dashboard/authentication/handshake',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

Gdn_LibraryMap::SafeCache('library','class.handshakeauthenticator.php',dirname(__FILE__).DS.'class.handshakeauthenticator.php');
class VanillaConnectPlugin extends Gdn_Plugin {
   
   public function SettingsController_VanillaConnect_Create($Sender, $EventArguments = array()) {
      $Sender->Title('Vanilla Connect SSO');
		$Sender->Form = new Gdn_Form();
		
		$this->EnableSlicing($Sender);
      $this->AddSliceAsset($this->GetResource('vanillaconnect.css', FALSE,FALSE));
		$this->Dispatch($Sender, $Sender->RequestArgs);
   }
   
   public function AuthenticationController_AuthenticatorConfigurationHandshake_Handler($Sender) {
      $Sender->AuthenticatorConfigure = '/dashboard/settings/vanillaconnect';
   }
   
   public function Controller_Index($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $SQL = Gdn::Database()->SQL();
      $Provider = $SQL->Select('uap.AuthenticationKey')
         ->From('UserAuthenticationProvider uap')
         ->Where('uap.AuthenticationSchemeAlias', 'handshake')
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
         } else {
			// Commented out this elseif b/c you need to be able to save values to
			// the db even if the authenticator isn't enabled.
			// } else if (C('Plugins.VanillaConnect.Enabled')) {
				$ProviderModel->Validation->ApplyRule('URL',             'Required');
            $ProviderModel->Validation->ApplyRule('RegisterUrl',     'Required');
            $ProviderModel->Validation->ApplyRule('SignInUrl',       'Required');
            $ProviderModel->Validation->ApplyRule('SignOutUrl',      'Required');
				$Sender->Form->SetFormValue('AuthenticationKey', $ConsumerKey);
				$Sender->Form->SetFormValue('AuthenticationSchemeAlias', 'handshake');
            $Sender->Form->Save();
         }
      }
      
      $Sender->ConsumerKey = ($Provider) ? $Provider['AuthenticationKey'] : '';
      $Sender->ConsumerSecret = ($Provider) ? $Provider['AssociationSecret'] : '';
      
      $Sender->SliceConfig = $this->RenderSliceConfig();
      $Sender->Render($this->GetView('vanillaconnect.php'));
   }
   
   public function Controller_Toggle($Sender) {
		$Sender->Permission('Garden.Settings.Manage');
		
		// Enable/Disable VanillaConnect
		if (Gdn::Session()->ValidateTransientKey(GetValue(1, $Sender->RequestArgs))) {
			if (C('Plugins.VanillaConnect.Enabled')) {
				$this->_Disable();
			} else {
				$this->_Enable();
			}
			Redirect('settings/vanillaconnect');
		}
   }
   
   public function Controller_Library($Sender) {
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      $Sender->Render($this->GetResource('js/library.js'));
   }
   
   public function Controller_Bundle($Sender) {
      if (!class_exists('ZipArchive')) die('No zip archive tools!');
      
      $ExternalPath = $this->GetResource('external',FALSE,TRUE);
      $Files = scandir($ExternalPath);
      
      $Resources = array();
      $NeededResources = array_fill_keys(array('vanillaconnect', 'oauth'), array());
      foreach ($Files as $Filename) {
         foreach ($NeededResources as $ResourceFragment => &$ResourceFileList) {
            $FN = CombinePaths(array($ExternalPath,$Filename));
            if (!is_dir($FN) && preg_match("/{$ResourceFragment}/i",$Filename)) {
               $ResourceFileList[] = $FN;
            }
         }
         unset($ResourceFileList);
      }
      
      // Reorder to match NeededResources
      foreach ($NeededResources as $ResourceName => $FileList)
         if (is_array($FileList) && sizeof($FileList))
            foreach ($FileList as $FilePath)
               $Resources[] = $this->_StripLibraryTags($FilePath);
      
      $SuperData = "<?php\n" . implode("\n\n", $Resources) . "\n?>";
      
      $Zip = new ZipArchive();
      $ZipFile = CombinePaths(array(PATH_CACHE,'vanillaconnect.php.zip'));
      if (file_exists($ZipFile)) 
         unlink($ZipFile);
         
      if ($Zip->open($ZipFile, ZIPARCHIVE::CREATE) !== TRUE)
         die('Could not create archive!');
      
      $Zip->addFromString('vanillaconnect.php', $SuperData);
      $Zip->close();
      
      try {
         Gdn_FileSystem::ServeFile($ZipFile, 'vanillaconnect.php.zip');
      } catch (Exception $e) {
         throw new Exception('File could not be streamed: missing file ('.$ZipFile.').');
      }
            
      exit();
   }
   
   protected function _StripLibraryTags($Filename) {
      if (!file_exists($Filename)) return '';
      $FileData = file($Filename);
      
      // Strip opening PHP tag
      if (trim($FileData[0]) == '<?php')
         array_shift($FileData);
         
      // Strip ending PHP tag
      $Index = sizeof($FileData) - 1;
      while ($Index > 0) {
         if (trim($FileData[$Index]) == '?>') {
            array_pop($FileData);
            break;
         }
         
         if (trim($FileData[$Index]) == '')
            array_pop($FileData);
         else
            break;
            
         $Index--;
      }
      
      return implode("", $FileData);
   }
   
   public function EntryController_SignIn_Handler(&$Sender) {
      if (!Gdn::Authenticator()->IsPrimary('handshake')) return;
      $this->SigninLoopback($Sender);
   }
   
   protected function SigninLoopback($Sender) {
      if (!Gdn::Authenticator()->IsPrimary('handshake')) return;
      $Redirect = Gdn::Request()->GetValue('HTTP_REFERER');
      
      $SigninURL = Gdn::Authenticator()->GetURL(Gdn_Authenticator::URL_REMOTE_SIGNIN, $Redirect);
      $SignoutURL = Gdn::Authenticator()->GetURL(Gdn_Authenticator::URL_SIGNOUT, NULL);
      $RealUserID = Gdn::Authenticator()->GetRealIdentity();
      
      $Authenticator = Gdn::Authenticator()->GetAuthenticator('handshake');
      
      // The user really isnt signed in. Delete their cookie and send them to the remote login page.
      $Authenticator->SetIdentity(NULL);
      $Authenticator->DeleteCookie();
      Redirect($SigninURL,302);

      exit();
   }
   
   public function EntryController_SignOut_Handler(&$Sender) {
      if (!Gdn::Authenticator()->IsPrimary('handshake')) return;
      
      $SignoutURL = Gdn::Authenticator()->GetURL(Gdn_Authenticator::URL_REMOTE_SIGNOUT, NULL);
      
/*
      $Authenticator = Gdn::Authenticator()->GetAuthenticator('handshake');
      $Authenticator->SetIdentity(NULL);
      $Authenticator->DeleteCookie();
*/
      
      Redirect($SignoutURL,302);
      exit();
   }
   
   public function Setup() {
      $this->_Enable(FALSE);
   }
   
   public function OnDisable() {
		$this->_Disable();
		Gdn::Authenticator()->DisableAuthenticationScheme('handshake');
		
		RemoveFromConfig('Garden.Authenticators.handshake.Name');
      RemoveFromConfig('Garden.Authenticators.handshake.CookieName');
      RemoveFromConfig('Garden.Authenticators.handshake.TokenLifetime');
   }
   
   protected function _CreateProviderModel() {
      $Key = 'k'.sha1(implode('.',array(
         'vanillaconnect',
         'key',
         microtime(true),
         RandomString(16),
         Gdn::Session()->User->Name
      )));
      
      $Secret = 's'.sha1(implode('.',array(
         'vanillaconnect',
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
   
   public function AuthenticationController_DisableAuthenticatorHandshake_Handler($Sender) {
      $this->_Disable();
   }
   
   private function _Disable() {
      RemoveFromConfig('Plugins.VanillaConnect.Enabled');
		
      $WasEnabled = Gdn::Authenticator()->UnsetDefaultAuthenticator('handshake');
      if ($WasEnabled)
         RemoveFromConfig('Garden.SignIn.Popup');
   }
   
   public function AuthenticationController_EnableAuthenticatorHandshake_Handler($Sender) {
      $this->_Enable();
   }
	
	private function _Enable($FullEnable = TRUE) {
		SaveToConfig('Garden.Authenticators.handshake.Name', 'VanillaConnect');
      SaveToConfig('Garden.Authenticators.handshake.CookieName', 'VanillaHandshake');
      SaveToConfig('Garden.Authenticators.handshake.TokenLifetime', 0);
      
      if ($FullEnable) {
         SaveToConfig('Garden.SignIn.Popup', FALSE);
         SaveToConfig('Plugins.VanillaConnect.Enabled', TRUE);
      }
      
      // Add this authenticator to the list of allowed authenticators, and optionally set it as the default
      Gdn::Authenticator()->EnableAuthenticationScheme('handshake', $FullEnable);
      
      // Create a provider key/secret pair if needed
      $SQL = Gdn::Database()->SQL();
      $Provider = $SQL->Select('uap.*')
         ->From('UserAuthenticationProvider uap')
         ->Where('uap.AuthenticationSchemeAlias', 'handshake')
         ->Get()
         ->FirstRow(DATASET_TYPE_ARRAY);
         
      if (!$Provider)
         $this->_CreateProviderModel();
	}  
}