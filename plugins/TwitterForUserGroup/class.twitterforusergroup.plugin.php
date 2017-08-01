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

   public function base_render_before($sender) {
      
      // Only display this in panels loaded from the default master view... prevent loading in admin panel
      if (!in_array($sender->MasterView, ['','default.master.php'])) return;
      
      // Attach Twitter script library to the HeadModule on the $Sender (calling controller)
      $sender->Head->addScript("http://widgets.twimg.com/j/2/widget.js");
      
      // Get the twitter username we want to use. First check the config, and if not found, use the default of 'vanilla'
      $twitterUser = c('Plugin.Twitter.Username', 'vanilla');
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
      $sender->addAsset('Panel', $twitterCode, 'TwitterPlugin');
   }

   public function setup() {
      // Nothing to do here!
   }
   
   public function structure() {
      // Nothing to do here!
   }
   
   public function pluginController_twitter_create($sender) {
      $sender->permission('Garden.Settings.Manage');
      $sender->title('Twitter Plugin Settings');
      $sender->addSideMenu('plugin/twitter');
      $sender->Form = new Gdn_Form();
      $sender->addCssFile('admin.css');
      
      $twitterUsername = c('Plugin.Twitter.Username', 'vanilla');
      
      $validation = new Gdn_Validation();
      $configurationModel = new Gdn_ConfigurationModel($validation);
      $configArray = ['Plugin.Twitter.Username'];
      if ($sender->Form->authenticatedPostBack() === FALSE)
         $configArray['Plugin.Twitter.Username'] = $twitterUsername;
      
      $configurationModel->setField($configArray);
      
      // Set the model on the form.
      $sender->Form->setModel($configurationModel);
      
      // If seeing the form for the first time...
      if ($sender->Form->authenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $sender->Form->setData($configurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $configurationModel->Validation->applyRule('Plugin.Twitter.Username', 'Required');
         
         if ($sender->Form->save() !== FALSE) {
            $sender->informMessage(t("Your changes have been saved."));
         }
      }
      
      $sender->render($this->getView('twitter.php'));
   }
      
}
