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
$PluginInfo['Twitter'] = array(
   'Name' => 'Twitter',
   'Description' => 'This plugin provides the capability to deploy a twitter feed box to the forum proper.',
   'Version' => '1.0',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'SettingsUrl' => '/dashboard/plugin/fileupload',
   'SettingsPermission' => 'Garden.AdminUser.Only',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class TwitterPlugin extends Gdn_Plugin {

   public function Base_Render_Before(&$Sender) {
      $Sender->Head->AddScript("http://widgets.twimg.com/j/2/widget.js");
      
      $TwitterUser = C('Plugin.Twitter.Username', 'vanilla');
      $NumTweets = 4;
      $BoxWidth = 250;
      $RefreshInterval = 6000;
      $TwitterCode = <<<TWITCODE
<script>
new TWTR.Widget({
  version: 2,
  type: 'profile',
  rpp: {$NumTweets},
  interval: {$RefreshInterval},
  width: {$BoxWidth},
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
      $Sender->AddAsset('Panel', $TwitterCode, 'TwitterPlugin');
   }

   public function Setup() {
      // Nothing to do here!
   }
   
   public function Structure() {
      // Nothing to do here!
   }
   
}