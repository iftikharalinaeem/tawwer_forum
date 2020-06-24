<?php if (!defined('APPLICATION')) exit();

class GettingStartedHostingPlugin implements Gdn_IPlugin {

/*
   This plugin should:
   
   1. Display 5 tips for getting started on the dashboard
   2. Check off each item as it is completed
   3. Disable itself when "dismiss" is clicked
*/    
    
   // Save various steps, and render the getting started message.
   public function settingsController_render_before($sender) {
      // Save the action if editing registration settings
      if (strtolower($sender->RequestMethod) != 'index')
         $this->saveStep('Plugins.GettingStartedHosting.Dashboard');

      // Save the action if they reviewed plugins
      // if (strcasecmp($Sender->RequestMethod, 'plugins') == 0)
      //    $this->saveStep('Plugins.GettingStarted.Plugins');

      // Save the action if they reviewed plugins
      if (strcasecmp($sender->RequestMethod, 'managecategories') == 0)
         $this->saveStep('Plugins.GettingStartedHosting.Categories');

      // Add messages & their css on dashboard
      if (strcasecmp($sender->RequestMethod, 'index') == 0) {
         $session = Gdn::session();
         $welcomeMessage = '<div class="GettingStartedHosting">'
            .anchor('×', '/plugin/dismissgettingstarted/'.$session->transientKey(), 'Dismiss')
   ."<h1>Tips on how to get started</h1>"
   .'<ul>
      <li class="One'.(c('Plugins.GettingStartedHosting.Dashboard', '0') == '1' ? ' Done' : '').'">
         <strong>'.anchor('Welcome to your Dashboard', 'settings').'</strong>
         <p>This is the administrative dashboard for your new community. Check
         out the configuration options to the left: from here you can configure
         how your community works. <b>Only users in the "Administrator" role can
         see this part of your community.</b></p>
      </li>
      <li class="Two">
         <strong>'.anchor('Your Account', 'http://vanillaforums.com/account').'</strong>
         <p>If you want to change your plan, click the '
         .anchor('My Account', 'http://vanillaforums.com/account').' link on the
         top-right of this page. From there, you can review payments and upgrade,
         downgrade, or cancel your account at any time.</p>
      </li>
      <li class="Three'.(c('Plugins.GettingStartedHosting.Discussions', '0') == '1' ? ' Done' : '').'">
         <strong>'.anchor("Where is your Community Forum?", '/').'</strong>
         <p>Access your community forum by clicking the "Visit Site" link on the
         top-left of this page, or by '.anchor('clicking here', '/').'. The
         community forum is what all of your users &amp; customers will see when
         they visit '.anchor(Gdn::request()->domain(), Gdn::request()->domain()).'.</p>
      </li>
      <li class="Four'.(c('Plugins.GettingStartedHosting.Categories', '0') == '1' ? ' Done' : '').'">
         <strong>'.anchor(t('Organize your Categories'), 'settings/managecategories').'</strong>
         <p>Discussion categories are used to help your users organize their
         discussions in a way that is meaningful for your community.</p>
      </li>
      <li class="Five'.(c('Plugins.GettingStartedHosting.Profile', '0') == '1' ? ' Done' : '').'">
         <strong>'.anchor(t('Customize your Public Profile'), 'profile').'</strong>
         <p>Everyone who signs up for your community gets a public profile page
         where they can upload a picture of themselves, manage their profile
         settings, and track cool things going on in the community. You should
         '.anchor('customize your profile now', 'profile').'.</p>
      </li>
      <li class="Six'.(c('Plugins.GettingStartedHosting.Discussion', '0') == '1' ? ' Done' : '').'">
         <strong>'.anchor(t('Start your First Discussion'), 'post/discussion').'</strong>
         <p>Get the ball rolling in your community by '
         .anchor('starting your first discussion', 'post/discussion').' now.</p>
      </li>
   </ul>
</div>';
         $sender->addAsset('Messages', $welcomeMessage, 'WelcomeMessage');
      }
   }
   
   // Record when the various actions are taken
   // 1. If the user edits the registration settings
   public function saveStep($step) {
      if (Gdn::config($step, '') != '1')
         saveToConfig($step, '1');

      /*
      // If all of the steps are now completed, disable this plugin
      if (
         c('Plugins.GettingStartedHosting.Categories', '0') == '1'
         && c('Plugins.GettingStartedHosting.Profile', '0') == '1'
         && c('Plugins.GettingStartedHosting.Discussion', '0') == '1'
         && c('Plugins.GettingStartedHosting.Discussions', '0') == '1'
      ) {
         $PluginManager = Gdn::factory('PluginManager');
         $PluginManager->disablePlugin('GettingStarted');
      }
     */
   }
   
   // If the user posts back any forms to their profile, they've completed step 4: profile customization
   public function profileController_render_before($sender) {
      if (property_exists($sender, 'Form') && $sender->Form->authenticatedPostBack() === TRUE)
         $this->saveStep('Plugins.GettingStartedHosting.Profile');
   }

   // If the user starts a discussion
   public function postController_render_before($sender) {
      if (strcasecmp($sender->RequestMethod, 'discussion') == 0 && $sender->Form->authenticatedPostBack() === TRUE)
         $this->saveStep('Plugins.GettingStartedHosting.Discussion');
   }

   public function discussionsController_render_before($sender) {
      $this->saveStep('Plugins.GettingStartedHosting.Discussions');
   }
   
   public function pluginController_dismissGettingStarted_create($sender) {
      $pluginManager = Gdn::factory('PluginManager');
      $pluginManager->disablePlugin('GettingStartedHosting');
      echo 'TRUE';
   }
   
   public function setup() {
      // No setup required.
   }
}
