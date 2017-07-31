<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class TwitterForUserGroupPlugin extends Gdn_Plugin {

   public function Base_Render_Before($sender) {
      
      // Only display this in panels loaded from the default master view... prevent loading in admin panel
      if (!in_array($sender->MasterView, ['','default.master.php'])) return;
      
      // Attach Twitter script library to the HeadModule on the $Sender (calling controller)
      $sender->Head->AddScript("http://widgets.twimg.com/j/2/widget.js");
      
      // Get the twitter username we want to use. First check the config, and if not found, use the default of 'vanilla'
      $twitterUser = C('Plugin.Twitter.Username', 'vanilla');
      $numTweets = 4;
      
      $twitterCode = <<<TWITCODE
<script>
new TWTR.Widget({
  version: 2,
  type: 'profile',
  rpp: {$numTweets},
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
}).render().setUser('{$twitterUser}').start();
</script>
TWITCODE;

      // Add the resulting Javascript to the panel
      $sender->AddAsset('Panel', $twitterCode, 'TwitterPlugin');
   }

   public function Setup() {
      // Nothing to do here!
   }
   
   public function Structure() {
      // Nothing to do here!
   }
   
   public function PluginController_Twitter_Create($sender) {
      $sender->Permission('Garden.Settings.Manage');
      $sender->Title('Twitter Plugin Settings');
      $sender->AddSideMenu('plugin/twitter');
      $sender->Form = new Gdn_Form();
      $sender->AddCssFile('admin.css');
      
      $twitterUsername = C('Plugin.Twitter.Username', 'vanilla');
      
      $validation = new Gdn_Validation();
      $configurationModel = new Gdn_ConfigurationModel($validation);
      $configArray = ['Plugin.Twitter.Username'];
      if ($sender->Form->AuthenticatedPostBack() === FALSE)
         $configArray['Plugin.Twitter.Username'] = $twitterUsername;
      
      $configurationModel->SetField($configArray);
      
      // Set the model on the form.
      $sender->Form->SetModel($configurationModel);
      
      // If seeing the form for the first time...
      if ($sender->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $sender->Form->SetData($configurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $configurationModel->Validation->ApplyRule('Plugin.Twitter.Username', 'Required');
         
         if ($sender->Form->Save() !== FALSE) {
            $sender->InformMessage(T("Your changes have been saved."));
         }
      }
      
      $sender->Render($this->GetView('twitter.php'));
   }
      
}
