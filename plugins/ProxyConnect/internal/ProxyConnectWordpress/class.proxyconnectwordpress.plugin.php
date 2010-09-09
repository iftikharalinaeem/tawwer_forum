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
$PluginInfo['ProxyConnectWordpress'] = array(
   'Name' => 'Wordpress Integration',
   'Description' => 'This plugin tightens the integration between Wordpress and Vanilla when ProxyConnect is enabled.',
   'Version' => '0.9',
   'RequiredApplications' => array('Vanilla' => '2.0.6'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'SettingsPermission' => 'Garden.AdminUser.Only',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class ProxyConnectWordpressPlugin extends Gdn_Plugin {
   
   public function Controller_Index($Sender) {
      $this->AddSliceAsset($this->GetResource('css/wordpress.css', FALSE, FALSE));

      $Provider = $this->ProxyConnect->LoadProviderData($Sender);
      $ProviderModel = new Gdn_AuthenticationProviderModel();
      
      do {
         $State = $this->State($Provider);
         
         switch ($State) {
            case 'Address':
               // Gather remote address from user.
               
               // User has submitted the form and provided a URL
               if ($Sender->Form->AuthenticatedPostBack()) {
                  
                  // Address supplied. Query the remote blog.
                  $Address = $Sender->Form->GetValue('WordpressUrl', NULL);
                  if (is_null($Address)) break 2;
                  
                  $Provider['URL'] = $Address;
                  $Response = $this->QueryRemote($Provider, 'Check', NULL, FALSE);
                  
                  Boop($Response);
                  die();
                  if (GetValue('X-ProxyConnect-Enabled', $Response) == 'yes') {
                     // Proxyconnect is enabled at the provided URL.
                     die('saved');
                     $ProviderModel->Save($Provider);
                  } else {
                     unset ($Provider['URL']);
                  }
               } else {
                  
                  // break out of the loop and let the form render
                  break 2;
               }
               
            break;
            
            case 'Exchange':
               // 1: push challenge key to remote.
               // 2: gather urls
               
               // 1
               $Response = $this->QueryRemote($Provider, 'Secure', array(
                  'Challenge' => GetValue('AssociationSecret', $Provider)
               ));
               
               if (GetValue('X-Autoconfigure-Challenge', $Response) != 'set') {
                  return $this->GetView('wordpress.php');
               }
               
            break;
            
            case NULL:
               // provider is fully configured. test it in phase 2.
            break;
            
            case 'Error':
               return $this->GetView('providerfailed.php');
            break;
         }
      } while (!is_null($State));
      
      $Sender->SetData('IntegrationState', $State);
            
      return $this->GetView('wordpress.php');
   }
   
   protected function QueryRemote($Provider, $Task, $Arguments = array(), $Secure = TRUE) {
      if (is_null($Arguments)) $Arguments = array();
      
      $Arguments = array_merge($Arguments, array(
         'ProxyConnectAutoconfigure'   => 'configure',
         'Task'                        => $Task
      ));
      
      if ($Secure) {
         $Arguments = array_merge($Arguments, array(
            'Key'                      => GetValue('AssociationSecret', $Provider)
         ));
      }
      
      $RealURL = GetValue('URL', $Provider)."?".http_build_query($Arguments);
      $Response = ProxyHead($RealURL);
      return $Response;
   }
   
   protected function State($Provider = NULL) {
      if (is_null($Provider))
         return 'Error';
         
      if (GetValue('URL', $Provider) == NULL) {
         return 'Address';
      }
         
      if (is_null($State)) {
         if (
            GetValue('RegisterUrl', $Provider) == NULL ||
            GetValue('RegisterUrl', $Provider) == NULL ||
            GetValue('RegisterUrl', $Provider) == NULL
         ) {
            return 'Exchange';
         }
      }
      
      return NULL;
   }
   
   public function ProxyConnectPlugin_ConfigureIntegrationManager_Handler(&$Sender) {
      $this->ProxyConnect = $Sender;
            
      // Check that we should be handling this
      if ($this->ProxyConnect->IntegrationManager != strtolower($this->GetPluginIndex()))
         return;
   
      $this->Controller = $Sender->Controller;
      $this->EnableSlicing($Sender->Controller);

      $Sender->LoadProviderData($Sender->Controller);

      $SubController = 'Controller_'.ucfirst($Sender->SubController);
      if (!method_exists($this, $SubController))
         $SubController = 'Controller_Index';
         
      // Set view path
      $Sender->IntegrationConfigurationPath = $this->$SubController($Sender->Controller);
      $Sender->Controller->SliceConfig = $this->RenderSliceConfig();
   }
   
   public function Setup() {
      
   }
   
}