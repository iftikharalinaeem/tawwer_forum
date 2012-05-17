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
$PluginInfo['TwitterForUserGroup'] = array(
   'Name' => 'Twitter',
   'Description' => 'This plugin provides the capability to deploy a twitter feed box to the forum panel.',
   'Version' => '1.1',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'SettingsUrl' => '/dashboard/plugin/twitter',
   'SettingsPermission' => 'Garden.AdminUser.Only',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class TwitterPlugin extends Gdn_Plugin {

   public function Base_Render_Before(&$Sender) {
      
      // Only display this in panels loaded from the default master view... prevent loading in admin panel
      if (!in_array($Sender->MasterView, array('','default.master.php'))) return;
      
      // Attach Twitter script library to the HeadModule on the $Sender (calling controller)
      $Sender->Head->AddScript("http://widgets.twimg.com/j/2/widget.js");
      
      // Get the twitter username we want to use. First check the config, and if not found, use the default of 'vanilla'
      $TwitterUser = C('Plugin.Twitter.Username', 'vanilla');
      $NumTweets = 4;
      
      $TwitterCode = <<<TWITCODE
<script>
new TWTR.Widget({
  version: 2,
  type: 'profile',
  rpp: {$NumTweets},
  interval: 6000,
  width: '100%',
  height: 300,
  theme: {
    shell: {
      background: '#1E79A7',
      color: '#CFECFF'
    },
    tweets: {
      background: '#E3F4FF',
      color: '#1E79A7',
      links: '#38ABE3'
    }
  },
  features: {
    scrollbar: false,
    loop: false,
    live: false,
    hashtags: true,
    timestamp: true,
    avatars: false,
    behavior: 'all'
  }
}).render().setUser('{$TwitterUser}').start();
</script>
TWITCODE;

      // Add the resulting Javascript to the panel
      $Sender->AddAsset('Panel', $TwitterCode, 'TwitterPlugin');
   }

   public function Setup() {
      // Nothing to do here!
   }
   
   public function Structure() {
      // Nothing to do here!
   }
   
   public function PluginController_Twitter_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Twitter Plugin Settings');
      $Sender->AddSideMenu('plugin/twitter');
      $Sender->Form = new Gdn_Form();
      $Sender->AddCssFile('admin.css');
      
      $TwitterUsername = C('Plugin.Twitter.Username', 'vanilla');
      
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigArray = array('Plugin.Twitter.Username');
      if ($Sender->Form->AuthenticatedPostBack() === FALSE)
         $ConfigArray['Plugin.Twitter.Username'] = $TwitterUsername;
      
      $ConfigurationModel->SetField($ConfigArray);
      
      // Set the model on the form.
      $Sender->Form->SetModel($ConfigurationModel);
      
      // If seeing the form for the first time...
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $Sender->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Plugin.Twitter.Username', 'Required');
         
         if ($Sender->Form->Save() !== FALSE) {
            $Sender->InformMessage(T("Your changes have been saved."));
         }
      }
      
      $Sender->Render($this->GetView('twitter.php'));
   }
      
}