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
   'Version' => '1.0',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'SettingsUrl' => '/dashboard/settings/vanillaconnect',
   'SettingsPermission' => 'Garden.AdminUser.Only',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

Gdn_FileCache::SafeCache('library','class.handshakeauthenticator.php',dirname(__FILE__).DS.'class.handshakeauthenticator.php');
class VanillaConnectPlugin extends Gdn_Plugin {

   /**
    * Adds "VanillaConnect" menu option to the dashboard.
    */
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Users', 'Vanilla Connect', 'settings/vanillaconnect', 'Garden.AdminUser.Only');
   }
   
   public function SettingsController_VanillaConnect_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
		
      $Sender->Title('Vanilla Connect');
      $Sender->AddSideMenu('settings/vanillaconnect');
		$Sender->AddCssFile('/plugins/VanillaConnect/vanillaconnect.css');
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
         } else if (C('Plugins.VanillaConnect.Enabled')) {
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
      
      $Sender->Render($this->GetView('vanillaconnect.php'));
   }
   
   public function Controller_Toggle(&$Sender) {
		
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
   
   public function Controller_Library(&$Sender) {
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      $Sender->Render($this->GetResource('js/library.js'));
   }
   
   public function Controller_Bundle(&$Sender) {
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
   
   public function Setup() {
		// Do nothing
   }
   
   public function OnDisable() {
		$this->_Disable();
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
   
   private function _Disable() {
		RemoveFromConfig('Garden.SignIn.Popup');
		RemoveFromConfig('Plugins.VanillaConnect.Enabled');
		RemoveFromConfig('Garden.Authenticator.DefaultScheme');
      RemoveFromConfig('Garden.Authenticators.handshake.CookieName');
      RemoveFromConfig('Garden.Authenticators.handshake.TokenLifetime');

      $EnabledSchemes = Gdn::Config('Garden.Authenticator.EnabledSchemes', array());
      foreach ($EnabledSchemes as $SchemeIndex => $SchemeKey) {
         if ($SchemeKey == 'handshake')
            unset($EnabledSchemes[$SchemeKey]);
      }
      SaveToConfig('Garden.Authenticator.EnabledSchemes', $EnabledSchemes);
   }
	
	private function _Enable() {
		SaveToConfig('Garden.SignIn.Popup', FALSE);
		SaveToConfig('Plugins.VanillaConnect.Enabled', TRUE);
		SaveToConfig('Garden.Authenticator.DefaultScheme', 'handshake');
      SaveToConfig('Garden.Authenticators.handshake.CookieName', 'VanillaHandshake');
      SaveToConfig('Garden.Authenticators.handshake.TokenLifetime', 0);
      
      $EnabledSchemes = Gdn::Config('Garden.Authenticator.EnabledSchemes', array());
      array_push($EnabledSchemes, 'handshake');
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