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
$PluginInfo['vfcom'] = array(
   'Name' => 'VanillaForums.com Hosting',
   'Description' => "This plugin provides the hooks and management tools need to run Vanilla in Infrastructure Mode.",
   'Version' => '1.1',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com',
   'Hidden' => TRUE
);

class VfcomPlugin extends Gdn_Plugin {
   
   public function __construct() {
      
   }

   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      
      if (!Gdn::Session()->CheckPermission('Garden.Settings.Manage'))
         return;

      if (!StringEndsWith(GetValue('Email', Gdn::Session()->User, NULL), "@vanillaforums.com"))
         return;
      
      $LinkText = T('Infrastructure');
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Site Settings', T('Settings'));
      $Menu->AddLink('Site Settings', $LinkText, 'plugin/vfcom', 'Garden.Settings.Manage');
   }

   public function PluginController_Vfcom_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      if (!StringEndsWith(GetValue('Email', Gdn::Session()->User, NULL), "@vanillaforums.com"))
         throw new Exception(T("Sorry, only Vanilla Forums personnel are permitted here."));

      $Sender->Title('Infrastructure');
      $Sender->AddSideMenu('plugin/vfcom');
      $Sender->Form = new Gdn_Form();
      $Sender->AddCssFile('vfcom.css', 'plugins/vfcom');

      $this->EnableSlicing($Sender);
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }

   public function Controller_Index($Sender) {
      
      if ($Sender->Form->AuthenticatedPostBack()) {
         
         if (Gdn::Request()->GetValue("Plugin_vfcom_ClearCache", FALSE) !== FALSE) {
            // Clear all caches
            Gdn::PluginManager()->ClearPluginCache();

            // Rebuild now
            Gdn::PluginManager()->AvailablePlugins(TRUE);
            $Sender->InformMessage("The entire plugin cache has been cleared.");
         }
            
         if (Gdn::Request()->GetValue("Plugin_vfcom_ClearLocalCache", FALSE) !== FALSE) {
            foreach (Gdn::PluginManager()->SearchPaths() as $SearchPath => $SearchPathName) {
               if ($SearchPathName != "local") continue;
               
               // Clear local cache
               Gdn::PluginManager()->ClearPluginCache($SearchPath);
               
               // Rebuild now
               Gdn::PluginManager()->AvailablePlugins(TRUE);
               $Sender->InformMessage("The local plugin cache has been cleared.");
               break;
            }
         }
         
         if (Gdn::Request()->GetValue("Plugin_vfcom_IncrementCacheRevision", FALSE) !== FALSE) {
            $Incremented = Gdn::Cache()->IncrementRevision();
            $Sender->InformMessage("The cache revision has been incremented.");
         }
      }
      
      $Sender->Render('settings','','plugins/vfcom');
   }

   public function Gdn_Upload_GetUrls_Handler($Sender, $Args) {
      
      $VfcomClient = C('VanillaForums.SiteName', NULL);
      if (is_null($VfcomClient)) return;
      
      $StaticFormat = C('VanillaForums.StaticFormat', 'http://%s.static.%s/uploads');
      
      $VfcomHostname = C('VanillaForums.Hostname', 'vanillaforums.com');
      $Args['Urls'][''] = $FinalURL = sprintf($StaticFormat,
         $VfcomClient,
         $VfcomHostname
      );

   }
   
   public function HeadModule_BeforeToString_Handler($Sender) {
      // Only for logged-in users
      if (!Gdn::Session()->UserID) return;
      // Only when enabled (finally)
      if (!C('VanillaForums.ShowInfrastructure', FALSE)) return;
      
      if (stristr(Gdn::Session()->User->Email, 'vanillaforums.com') && Gdn::Session()->User->Admin) {
         echo '<div style="text-align:left;margin:10px;padding:5px;font-size:14px;background-color:white;color:gray;">';
         echo "<div>Upstream: ".GetValue('HTTP_X_UPSTREAM', $_SERVER, 'unknown')."</div>";
         echo "<div>Frontend: ".C('VanillaForums.Frontend', 'unknown')."</div>";
         echo '</div>';
      }
   }
   
   public function Setup() {
      
   }
   
}