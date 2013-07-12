<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['vfoptions'] = array(
   'Name' => 'VF.com Admin Options',
   'Description' => 'VF.com admin options.',
   'Version' => '1.2.1',
   'MobileFriendly' => TRUE,
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com',
   'Hidden' => FALSE
);

class VFOptionsPlugin implements Gdn_IPlugin {

   public function __construct() {
      $ForumName = Gdn::Request()->Host();

      $HostingDomain = C('VanillaForums.Hostname', 'vanillaforums.com');
      $RegexHostingDomain = str_replace('.','\.', $HostingDomain);
      $HasCluster = preg_match("/\.cl[0-9]+\.{$RegexHostingDomain}\$/i", $ForumName);
      if ($HasCluster) {
         $ForumName = preg_replace("/\.cl[0-9]+\.{$RegexHostingDomain}\$/i", ".{$HostingDomain}", $ForumName);
         SaveToConfig('Garden.AutoDomainSwitch', FALSE);
      }

      // Vanilla's Akismet key
      SaveToConfig('Plugins.Akismet.MasterKey', '6f09cb8ec580', FALSE);
   }

/*
   This plugin should:
   
   1. Make sure that admin passwords are always updated across forums (including parent vf.com forum)
   2. Make sure that administrators are signed into every one of their forums when they sign in
   3. Make sure that administrators are signed out of every one of their forums when they sign out
   4. Only show the admin top-panel when the administrator is "root".
   6. Show the form that allows users to delete a forum
   8. Don't allow email to be changed to one that is already being used in master db.
   11. Include Google analytics code if the appropriate settings are in their conf file (see Base_Render_Before)
*/

   public function Base_BeforeUserOptionsMenu_Handler($Sender) {
		$Url = 'https://vanillaforums.com/account';
		if (strpos(Gdn::Request()->Domain(), 'vanilladev') !== FALSE)
         $Url = 'https://www.vanilladev.com/account/';

      echo Anchor('My Account', $Url, 'MyAccountLink');
   }
   
   /**
    * Adds & removes dashboard menu options.
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      
      // Give Vanilla admins option to suspend this plugin per session
      $IsSytemUser = (Gdn::Session()->UserID == Gdn::UserModel()->GetSystemUserID());
//      if (CheckPermission('Garden.Admin.Only') && $IsSytemUser) {
//         $SuspendText = (Gdn::Session()->Stash('SuspendVFOptions', '', FALSE)) ? 'Resume' : 'Suspend';
//         $Menu->AddLink('Dashboard', T($SuspendText.' VFOptions'), 'plugin/suspendvfoptions', 'Garden.Admin.Only');
//      }
         
      // If suspended, quit
//      if (Gdn::Session()->Stash('SuspendVFOptions', '', FALSE))
//         return;
   
		$New = ' <span class="New">New</span>';
      // Clean out options hosting customers should not see
		$Menu->RemoveLink('Add-ons', T('Plugins'));
		$Menu->RemoveLink('Add-ons', T('Applications'));
		$Menu->RemoveLink('Add-ons', T('Locales'));
		$Menu->RemoveLink('Site Settings', T('Routes'));
//		$Menu->RemoveLink('Site Settings', T('Outgoing Email'));
//		$Menu->RemoveLink('Users', T('Authentication'));
//		$Menu->AddLink('Users', T('Authentication').$New, 'dashboard/authentication', 'Garden.Settings.Manage');

//      if (C('EnabledPlugins.embedvanilla')) {
//			$Menu->RemoveLink('Add-ons', T('&lt;Embed&gt; Vanilla'));
//			$Menu->AddLink('Add-ons', T('&lt;Embed&gt; Vanilla').$New, 'plugin/embed', 'Garden.Settings.Manage');
//      }
	
		$Menu->RemoveLink('Forum', T('Statistics'));
      $Menu->RemoveLink('Site Settings', T('Statistics'));

      $Menu->AddLink('Add-ons', T('Browse Addons').' <span class="New">New</span>', 'dashboard/settings/addons', 'Garden.Settings.Manage');
		
		// Add stats menu option.
//      if (C('Garden.Analytics.Advanced')) {
//         $Menu->AddLink('Dashboard', 'Statistics', '/dashboard/settings/statistics', 'Garden.Settings.Manage');
//      }
   		
		Gdn::Locale()->SetTranslation('You can place files in your /uploads folder.', 'If your file is
   too large to upload directly to this page you can
   <a href="mailto:support@vanillaforums.com?subject=Importing+to+VanillaForums">contact us</a>
   to import your data for you.');
	}
   
   /**
    * If the domain in the config doesn't match that in the url, this will
    * redirect to the domain in the config. Also includes Google Analytics on
    * all pages if the conf file contains Plugins.GoogleAnalytics.TrackerCode
    * and Plugins.GoogleAnalytics.TrackerDomain.
    * @param Gdn_Controller $Sender
    */
   public function Base_Render_Before($Sender) {
//      if (Gdn::Session()->Stash('SuspendVFOptions', '', FALSE)) return; // Temp suspend option
      
      Gdn::Locale()->SetTranslation('PluginHelp', "Plugins allow you to add functionality to your site.");
      Gdn::Locale()->SetTranslation('ApplicationHelp', "Applications allow you to add large groups of functionality to your site.");
      Gdn::Locale()->SetTranslation('ThemeHelp', "Themes allow you to change the look &amp; feel of your site.");
      Gdn::Locale()->SetTranslation('AddonProblems', '');
      
      // If we're using the admin master view, make sure to add links to the footer for T's & C's
      if ($Sender->MasterView == 'admin') {
         $Domain = C('Garden.Domain', '');
         $Url = strpos($Domain, 'vanilladev') > 0 ? 'vanilladev' : 'vanillaforums';
			$Style = 'background: none; height: auto; width: auto; margin: 0; display: inline; color: #ACDDF8; font-size: 12px; font-weight: normal;';
         $Footer = Anchor('<strong style="color: #ff0;">Customer Support Forum</strong>', 'http://vanillaforums.com/help', '', array('target' => '_New', 'style' => $Style))
            .' | '
				.Anchor('Terms of Service', 'http://'.$Url.'.com/info/termsofservice', '', array('target' => '_New', 'style' => $Style))
            .' | '
            .Anchor('Privacy Policy', 'http://'.$Url.'.com/info/privacy', '', array('target' => '_New', 'style' => $Style))
            .' | '
            .Anchor('Refund Policy', 'http://'.$Url.'.com/info/refund', '', array('target' => '_New', 'style' => $Style))
            .' | '
            .Anchor('Contact', 'http://'.$Url.'.com/info/contact', '', array('target' => '_New', 'style' => $Style));
         $Sender->AddAsset('Foot', Wrap($Footer, 'div', array('style' => 'position: absolute; bottom: 15px; right: 140px;')));
         $Sender->AddCssFile('vfoptions.css', 'plugins/vfoptions');
//      } else {
//         $AnalyticsServer = C('Garden.Analytics.Remote','http://analytics.vanillaforums.com');
//         $Version = GetValue('Version', Gdn::PluginManager()->GetPluginInfo('vfoptions'));
//         $Sender->AddJsFile($AnalyticsServer.'/applications/vanillastats/js/track'.(Debug() ? '' : '.min').'.js?v='.$Version);
      }
//      $Sender->AddDefinition('StatsUrl', self::StatsUrl('{p}'));
      
      // Redirect if the domain in the url doesn't match that in the config (so
      // custom domains can't be accessed from their original subdomain).
      if (!defined('CLIENT_NAME') && C('Garden.AutoDomainSwitch', TRUE)) {
         $Domain = Gdn::Config('Garden.Domain', '');
         $ServerName = ArrayValue('SERVER_NAME', $_SERVER, '');
         if ($ServerName == '')
            $ServerName = ArrayValue('HTTP_HOST', $_SERVER, '');

         if ($ServerName != '' && $Domain != '') {
            $Domain = str_replace(array('http://', '/'), array('', ''), $Domain);
            $ServerName = str_replace(array('http://', '/'), array('', ''), $ServerName);
            if ($ServerName != $Domain)
               Redirect('http://' . $Domain . Gdn::Request()->Url(), 301);
         }
      }
      
      $TrackerCode = Gdn::Config('Plugins.GoogleAnalytics.TrackerCode');
      $TrackerDomain = Gdn::Config('Plugins.GoogleAnalytics.TrackerDomain');
      
      $VanillaCode = 'UA-12713112-1';
      
      if ($TrackerCode && $TrackerCode != '' && $TrackerCode != $VanillaCode && $Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
         $Script = "<script type=\"text/javascript\">
var gaJsHost = ((\"https:\" == document.location.protocol) ? \"https://ssl.\" : \"http://www.\");
document.write(unescape(\"%3Cscript src='\" + gaJsHost + \"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E\"));
</script>
<script type=\"text/javascript\">
try {
var pageTracker = _gat._getTracker(\"".$TrackerCode."\");";
         if ($TrackerDomain)
            $Script .= '
pageTracker._setDomainName("'.$TrackerDomain.'");';
         
         $Script .= "
pageTracker._trackPageview();
} catch(err) {}</script>";

         $Sender->AddAsset('Content', $Script);
      }
   }

   /**
    * Suspend this plugin for the rest of this session.
    */
   public function PluginController_SuspendVFOptions_Create($Sender) {
      // Permission check
      $IsSytemUser = (Gdn::Session()->UserID == Gdn::UserModel()->GetSystemUserID());
      if (!CheckPermission('Garden.Admin.Only') || !$IsSytemUser) 
         return;
      
      // Toggle
//      $Active = Gdn::Session()->Stash('SuspendVFOptions', '', FALSE);
//      if (!$Active)
//         Gdn::Session()->Stash('SuspendVFOptions', TRUE);
//      else
//         Gdn::Session()->Stash('SuspendVFOptions', FALSE);
         
      Redirect('/dashboard/settings');
   }
   
   /**
    * Overrides Outgoing Email management screen.
    *
    * @access public
    */
   public function SettingsController_Email_Create($Sender, $Args = array()) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->AddSideMenu('dashboard/settings/email');
      $Sender->AddJsFile('email.js');
      $Sender->Title(T('Outgoing Email'));
      
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Garden.Email.SupportName',
         'Garden.Email.SupportAddress'
      ));
      
      // Set the model on the form.
      $Sender->Form->SetModel($ConfigurationModel);
      
      // If seeing the form for the first time...
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $Sender->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Garden.Email.SupportName', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Garden.Email.SupportAddress', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Garden.Email.SupportAddress', 'Email');
         
         if ($Sender->Form->Save() !== FALSE)
            $Sender->InformMessage(T("Your settings have been saved."));
      }
      
      $Sender->Render('Email', '', 'plugins/vfoptions');      
   }      

   /**
    * Don't let the users access the items under the "Add-ons" menu section of
    * the dashboard: applications & plugins (themes was moved to the "
    * Appearance" section.
    * @param Gdn_Controller $Sender
    */
   public function SettingsController_Render_Before($Sender) {
//      if (Gdn::Session()->Stash('SuspendVFOptions', '', FALSE)) return; // Temp suspend option

      if (
         strcasecmp($Sender->RequestMethod, 'plugins') == 0
         || strcasecmp($Sender->RequestMethod, 'applications') == 0
      ) {
			if (Debug()) {
				$Sender->InformMessage('You can see this page because the site is in debug mode.');
            return;
			} else
				throw PermissionException();
		}

      // Theme pruning
      if (strcasecmp($Sender->RequestMethod, 'themes') == 0) {
         
         $VisibleThemes = strtolower(C('Garden.Themes.Visible', ''));
         $VisibleThemes = explode(',', $VisibleThemes);
         $ClientName = defined('CLIENT_NAME') ? CLIENT_NAME : '';
         
         // Remove any themes that are not available.
         $Themes = $Sender->Data('AvailableThemes');
         $Remove = array();
         foreach ($Themes as $Index => $Theme) {
            
            // Check site-specific themes
            $Site = GetValue('Site', $Theme);
            if ($Site && $Site != $ClientName)
               $Remove[] = $Index;
            
            // Check site explicit unhides
            $Hidden = GetValue('Hidden', $Theme, false);
            //$Sender->Data['AvailableThemes'][$Index]['Hidden'] = false;
            if ($Hidden && !in_array(strtolower($Index), $VisibleThemes)) {
               $Remove[] = $Index;
            }
         }
         
         // Remove orphans
         foreach ($Remove as $Index) {
            unset($Sender->Data['AvailableThemes'][$Index]);
         }
      }
      
//      if ($Sender->RequestMethod == 'banner')
//         $Sender->View = PATH_PLUGINS.'/vfoptions/views/banner.php';

      if ($Sender->RequestMethod == 'registration')
         $Sender->View = PATH_PLUGINS.'/vfoptions/views/registration.php';
   }

   /**
    * No setup required.
    */
   public function Setup() {}
   
   /**
    * Gets a url suitable to ping the statistics server.
    * @param type $Path
    * @param type $Params
    * @return string 
    */
   public static function StatsUrl($Path, $Params = array()) {
      $AnalyticsServer = C('Garden.Analytics.Remote','http://analytics.vanillaforums.com');
      
      $Path = '/'.trim($Path, '/');
      
      $Timestamp = time();
      $DefaultParams = array(
          'vid' => Gdn::InstallationID(),
          't' => $Timestamp,
          's' => md5($Timestamp.Gdn::InstallationSecret()));
      
      $Params = array_merge($DefaultParams, $Params);
      
      $Result = $AnalyticsServer.$Path.'?'.http_build_query($Params);
      return $Result;
   }

   /**
    *
    * @param SettingsController $Sender
    * @param array $Args
    */
   public function SettingsController_Addons_Create($Sender, $Args = array()) {
      $Sender->Title('Vanilla Addons');
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->AddSideMenu('dashboard/settings/addons');
      
      // Parameters
		$Filter = GetValue(0, $Args);
      $Action = strtolower(GetValue(1, $Args));
      $Key = GetValue(2, $Args);
      $TransientKey = GetValue(3, $Args);
		
		// Filtering
      if (!in_array($Filter, array('enabled', 'disabled')))
         $Filter = 'all';
      $Sender->Filter = $Filter;

      if (Gdn::Session()->ValidateTransientKey($TransientKey) && $Key) {
         try {            
            switch ($Action) {
               case 'enable':
                  if (GetValue($Key, Gdn::PluginManager()->AvailablePlugins()))
                     Gdn::PluginManager()->EnablePlugin($Key, NULL);
                  else
                     Gdn::ApplicationManager()->EnableApplication($Key, NULL);
                  
                  if ($Filter != 'all')
                     $Filter = 'enabled';
                  break;
               case 'disable':
                  if (GetValue($Key, Gdn::PluginManager()->AvailablePlugins()))
                     Gdn::PluginManager()->DisablePlugin($Key, NULL);
                  else
                     Gdn::ApplicationManager()->DisableApplication($Key, NULL);
                  if ($Filter != 'all')
                     $Filter = 'disabled';
                  break;
            }
            $Url = '/settings/addons/'.rawurlencode($Filter).'#'.urlencode(strtolower($Key)).'-plugin';
            Redirect($Url);
         } catch (Exception $Ex) {
            $Sender->Form->AddError($Ex);
         }
      }
      
      // Build available / enabled lists
      $AvailablePlugins = Gdn::PluginManager()->AvailablePlugins();
      //$AvailableApps = Gdn::ApplicationManager()->AvailableApplications();
      $EnabledPlugins = Gdn::PluginManager()->EnabledPlugins();
      $EnabledApps = Gdn::ApplicationManager()->EnabledApplications();
      
      // Determine plan's plugin availability
      $PlanPlugins = FALSE;
      if (class_exists('Infrastructure')) {
         $Plan = Infrastructure::Plan();
         $PlanPlugins = @json_decode(GetValueR('Plan.Addons', $Plan, ''));
         if ($PlanPlugins)
            $PlanPlugins = GetValue('Plugins', $PlanPlugins);
      }
      if (!$PlanPlugins) {
         $PlanPlugins = C('VFCom.Plugins.Default', array("ButtonBar","Emotify","Facebook",
            "GoogleSignIn","Gravatar","OpenID","StopForumSpam","Twitter","vanillicon",
            "AllViewed","Disqus","GooglePrettify","IndexPhotos","Liked","Participated","PostCount","PrivateCommunity",
            "QnA","Quotes","Reactions","RoleTitle","ShareThis","Signatures","Spinx","Tagging","Voting")
         );
      }
      $AllowedPlugins = array();
      foreach($PlanPlugins as $Key) {
         if ($Info = GetValue($Key, $AvailablePlugins))
            $AllowedPlugins[$Key] = $Info;
      }
      
      // Addons to show 'Contact Us' instead of 'Enable'
      $LockedPlugins = C('VFCom.Plugins.Locked', array('jsConnect', 'Multilingual', 'Pockets', 'Sphinx', 'TrackingCodes', 'VanillaPop', 'Whispers'));
      
      // Addons to hide even when enabled
      $HiddenPlugins = C('VFCom.Plugins.Hidden', array('CustomTheme', 'CustomDomain', 'CustomizeText', 'HtmLawed', 'cloudfiles', 'rackmonkey'));
      
      // Exclude hidden and vf* from enabled plugins
      foreach($EnabledPlugins as $Key => $Name) {
         // Skip all vf* plugins
         if (in_array($Key, $HiddenPlugins) || strpos($Key, 'vf') === 0)
            unset($EnabledPlugins[$Key]);
      }
      
      // Show allowed + previously enabled
      $Addons = array_merge($AllowedPlugins, $EnabledPlugins);
      
      // Filter & add conditional data to plugins
      foreach ($Addons as $Key => &$Info) {
         // Enabled?
         $Info['Enabled'] = $Enabled = array_key_exists($Key, $EnabledPlugins);
         
         // Find icon
         if (!$IconUrl = GetValue('IconUrl', $Info)) {
            $IconPath = '/plugins/'.GetValue('Folder', $Info, '').'/icon.png';
            $IconPath = file_exists(PATH_ROOT.$IconPath) ? $IconPath : 'applications/dashboard/design/images/plugin-icon.png';
            $IconPath = file_exists(PATH_ROOT.$IconPath) ? $IconPath : 'plugins/vfoptions/design/plugin-icon.png';
            $Info['IconUrl'] = $IconPath;
         }
         
         // Toggle button
         if (!$Enabled && in_array($Key, $LockedPlugins)) {
            // Locked plugins need admin intervention to enable. Doesn't stop URL circumvention.
            $Info['ToggleText'] = 'Contact Us';
            $Info['ToggleUrl'] = '/dashboard/settings/vanillasupport';
         }
         else {
            $Info['ToggleText'] = $ToggleText = $Enabled ? 'Disable' : 'Enable';
            $Info['ToggleUrl'] = "/dashboard/settings/addons/".$Sender->Filter."/".strtolower($ToggleText)."/$Key/".Gdn::Session()->TransientKey();
         }
      }

      $PlanCode = $Sender->Data('Plan.Subscription.PlanCode');

      // Kludge on the Reputation app as 'Badges'
      if ($PlanCode != 'free') {
         $Enabled = array_key_exists('Reputation', $EnabledApps);
         $Reputation = array('Reputation' => array(
            'Name' => 'Badges',
            'Description' => 'Give badges to your users to reward them for contributing to your community.',
            'IconUrl' => 'http://badges.vni.la/100/lol-2.png',
            'ToggleText' => ($Enabled ? 'Disable' : 'Enable'),
            'ToggleUrl' => "/dashboard/settings/addons/".$Sender->Filter."/".($Enabled ? 'disable' : 'enable')."/Reputation/".Gdn::Session()->TransientKey(),
            'Enabled' => $Enabled
         ));
         $Addons = array_merge($Reputation, $Addons);
      }

      // Kludge on the Groups app
      if ($PlanCode == 'enterprise' || $PlanCode == 'advanced' || $PlanCode == 'vip1') {
         $Enabled = array_key_exists('Groups', $EnabledApps);
         $Groups = array('Groups' => array(
            'Name' => 'Groups',
            'Description' => 'Create user groups and schedule events within those groups.',
            'IconUrl' => 'http://badges.vni.la/100/users.png',
            'ToggleText' => ($Enabled ? 'Disable' : 'Enable'),
            'ToggleUrl' => "/dashboard/settings/addons/".$Sender->Filter."/".($Enabled ? 'disable' : 'enable')."/Groups/".Gdn::Session()->TransientKey(),
            'Enabled' => $Enabled
         ));
         $Addons = array_merge($Groups, $Addons);
      }
      
      // Sort & set Addons
      uasort($Addons, 'AddonSort');
      $Sender->SetData('Addons', $Addons);
      
      // Get counts
      $PluginCount = 0;
      $EnabledCount = 0;
      foreach ($Addons as $PluginKey => &$Info) {
         if (GetValue($PluginKey, $AvailablePlugins)) {
            $PluginCount++;
            if (array_key_exists($PluginKey, $EnabledPlugins)) {
               $EnabledCount++;
               $Info['Enabled'] = TRUE;
            }
         }
      }
      $Sender->SetData('PluginCount', $PluginCount);
      $Sender->SetData('EnabledCount', $EnabledCount);
      $Sender->SetData('DisabledCount', $PluginCount - $EnabledCount);
      
      $Sender->Render('Addons', '', 'plugins/vfoptions');
   }   
}

/**
 * Sorting function for plugin info array.
 */
function AddonSort($PluginInfo, $PluginInfoCompare) {
   return strcmp(GetValue('Name', $PluginInfo), GetValue('Name', $PluginInfoCompare));
}