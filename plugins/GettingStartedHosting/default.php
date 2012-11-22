<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['GettingStartedHosting'] = array(
   'Name' => 'VanillaForums.com Getting Started',
   'Description' => 'Adds a welcome message to the dashboard showing new administrators things they can do to get started using their forum. Checks off each item as it is completed.',
   'Version' => '1.1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class GettingStartedHostingPlugin implements Gdn_IPlugin {

/*
   This plugin should:
   
   1. Display 5 tips for getting started on the dashboard
   2. Check off each item as it is completed
   3. Disable itself when "dismiss" is clicked
*/    
    
   // Save various steps, and render the getting started message.
   public function SettingsController_Render_Before(&$Sender) {
      // Save the action if editing registration settings
      if (strtolower($Sender->RequestMethod) != 'index')
         $this->SaveStep('Plugins.GettingStartedHosting.Dashboard');

      // Save the action if they reviewed plugins
      // if (strcasecmp($Sender->RequestMethod, 'plugins') == 0)
      //    $this->SaveStep('Plugins.GettingStarted.Plugins');

      // Save the action if they reviewed plugins
      if (strcasecmp($Sender->RequestMethod, 'managecategories') == 0)
         $this->SaveStep('Plugins.GettingStartedHosting.Categories');

      // Add messages & their css on dashboard
      if (strcasecmp($Sender->RequestMethod, 'index') == 0) {
         $Sender->AddCssFile('style.css', 'plugins/GettingStartedHosting');
         
         $Session = Gdn::Session();
         $WelcomeMessage = '<div class="GettingStartedHosting">'
            .Anchor('×', '/dashboard/plugin/dismissgettingstarted/'.$Session->TransientKey(), 'Dismiss')
   ."<h1>Tips on how to get started</h1>"
   .'<ul>
      <li class="One'.(C('Plugins.GettingStartedHosting.Dashboard', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor('Welcome to your Dashboard', 'settings').'</strong>
         <p>This is the administrative dashboard for your new community. Check
         out the configuration options to the left: from here you can configure
         how your community works. <b>Only users in the "Administrator" role can
         see this part of your community.</b></p>
      </li>
      <li class="Two">
         <strong>'.Anchor('Your Account', 'http://vanillaforums.com/account').'</strong>
         <p>If you want to change your plan, click the '
         .Anchor('My Account', 'http://vanillaforums.com/account').' link on the
         top-right of this page. From there, you can review payments and upgrade,
         downgrade, or cancel your account at any time.</p>
      </li>
      <li class="Three'.(C('Plugins.GettingStartedHosting.Discussions', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor("Where is your Community Forum?", '/').'</strong>
         <p>Access your community forum by clicking the "Visit Site" link on the
         top-left of this page, or by '.Anchor('clicking here', '/').'. The
         community forum is what all of your users &amp; customers will see when
         they visit '.Anchor(Gdn::Request()->Domain(), Gdn::Request()->Domain()).'.</p>
      </li>
      <li class="Four'.(C('Plugins.GettingStartedHosting.Categories', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor(T('Organize your Categories'), 'vanilla/settings/managecategories').'</strong>
         <p>Discussion categories are used to help your users organize their
         discussions in a way that is meaningful for your community.</p>
      </li>
      <li class="Five'.(C('Plugins.GettingStartedHosting.Profile', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor(T('Customize your Public Profile'), 'profile').'</strong>
         <p>Everyone who signs up for your community gets a public profile page
         where they can upload a picture of themselves, manage their profile
         settings, and track cool things going on in the community. You should
         '.Anchor('customize your profile now', 'profile').'.</p>
      </li>
      <li class="Six'.(C('Plugins.GettingStartedHosting.Discussion', '0') == '1' ? ' Done' : '').'">
         <strong>'.Anchor(T('Start your First Discussion'), 'post/discussion').'</strong>
         <p>Get the ball rolling in your community by '
         .Anchor('starting your first discussion', 'post/discussion').' now.</p>
      </li>
   </ul>
</div>';
         $Sender->AddAsset('Messages', $WelcomeMessage, 'WelcomeMessage');
      }
   }
   
   // Record when the various actions are taken
   // 1. If the user edits the registration settings
   public function SaveStep($Step) {
      if (Gdn::Config($Step, '') != '1')
         SaveToConfig($Step, '1');

      /*
      // If all of the steps are now completed, disable this plugin
      if (
         C('Plugins.GettingStartedHosting.Categories', '0') == '1'
         && C('Plugins.GettingStartedHosting.Profile', '0') == '1'
         && C('Plugins.GettingStartedHosting.Discussion', '0') == '1'
         && C('Plugins.GettingStartedHosting.Discussions', '0') == '1'
      ) {
         $PluginManager = Gdn::Factory('PluginManager');
         $PluginManager->DisablePlugin('GettingStarted');
      }
     */
   }
   
   // If the user posts back any forms to their profile, they've completed step 4: profile customization
   public function ProfileController_Render_Before(&$Sender) {
      if (property_exists($Sender, 'Form') && $Sender->Form->AuthenticatedPostBack() === TRUE)
         $this->SaveStep('Plugins.GettingStartedHosting.Profile');
   }

   // If the user starts a discussion
   public function PostController_Render_Before(&$Sender) {
      if (strcasecmp($Sender->RequestMethod, 'discussion') == 0 && $Sender->Form->AuthenticatedPostBack() === TRUE)
         $this->SaveStep('Plugins.GettingStartedHosting.Discussion');
   }

   public function DiscussionsController_Render_Before(&$Sender) {
      $this->SaveStep('Plugins.GettingStartedHosting.Discussions');
   }
   
   public function PluginController_DismissGettingStarted_Create($Sender) {
      $PluginManager = Gdn::Factory('PluginManager');
      $PluginManager->DisablePlugin('GettingStartedHosting');
      echo 'TRUE';
   }
   
   public function Setup() {
      // No setup required.
   }
}