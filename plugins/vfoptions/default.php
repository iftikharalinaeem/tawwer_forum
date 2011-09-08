<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['vfoptions'] = array(
   'Name' => 'VF.com Admin Options',
   'Description' => 'VF.com admin options.',
   'Version' => '1.1',
   'MobileFriendly' => TRUE,
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com',
   'Hidden' => FALSE
);

Gdn_LibraryMap::SafeCache('library','class.tokenauthenticator.php',dirname(__FILE__).DS.'class.tokenauthenticator.php');
class VFOptionsPlugin implements Gdn_IPlugin {

   public function __construct() {
      Gdn::Authenticator()->EnableAuthenticationScheme('token');
   }
   
   /**
    *
    * @param Gdn_Controller $Sender 
    */
   public function ActivityController_Resend_Create($Sender) {
      // Grab all of the activities that did not send.
      $Data = Gdn::SQL()
         ->Select('ActivityID')
         ->From('Activity')
         ->Where('Emailed', 4)
         ->Limit(25)
         ->Get()->ResultArray();
      
      $ActivityModel = new ActivityModel();
      $Count = 0;
      foreach ($Data as $Row) {
         $ActivityID = $Row['ActivityID'];
         $ActivityModel->SendNotification($ActivityID);
         $Count++;
      }
      $Sender->SetData('Count', $Count);
      if ($Sender->DeliveryMethod() == DELIVERY_METHOD_XHTML)
         echo "$Count processed.";
      else
         $Sender->Render();
   }
   
   // Make sure token authenticator is never activated as the primary authentication scheme
   public function AuthenticationController_EnableAuthenticatorToken_Handler(&$Sender) {
      Gdn::Authenticator()->UnsetDefaultAuthenticator('token');
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
		$New = ' <span class="New">New</span>';
      // Clean out options hosting customers should not see
      $Menu = &$Sender->EventArguments['SideMenu'];
		$Menu->RemoveLink('Add-ons', T('Plugins'));
		$Menu->RemoveLink('Add-ons', T('Applications'));
		$Menu->RemoveLink('Add-ons', T('Locales'));
		$Menu->RemoveLink('Site Settings', T('Routes'));
		$Menu->RemoveLink('Site Settings', T('Outgoing Email'));
		$Menu->RemoveLink('Users', T('Authentication'));
		$Menu->AddLink('Users', T('Authentication').$New, 'dashboard/authentication', 'Garden.Settings.Manage');

      if (C('EnabledPlugins.embedvanilla')) {
			$Menu->RemoveLink('Add-ons', T('&lt;Embed&gt; Vanilla'));
			$Menu->AddLink('Add-ons', T('&lt;Embed&gt; Vanilla').$New, 'plugin/embed', 'Garden.Settings.Manage');
      }
	
		$Menu->RemoveLink('Forum', T('Statistics'));
      $Menu->RemoveLink('Site Settings', T('Statistics'));

      $Menu->AddLink('Add-ons', T('Browse Addons').' <span class="New">New</span>', 'dashboard/settings/addons', 'Garden.Settings.Manage');
      $Menu->AddItem('Vanilla Support', 'Vanilla Support', FALSE, array('class' => 'Support'));
      $Menu->AddLink('Vanilla Support', FALSE, '/dashboard/settings/vanillasupport', 'Garden.AdminUser.Only');
		
		
   		
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
    */
   public function Base_Render_Before($Sender) {
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
         $Sender->AddCssFile('plugins/vfoptions/design/vfoptions.css', 'dashboard');
      }
      
      // Redirect if the domain in the url doesn't match that in the config (so
      // custom domains can't be accessed from their original subdomain).
      if (!defined('CLIENT_NAME')) {
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
      
      if ($TrackerCode && $TrackerCode != '' && $Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
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

   public function AddonBrowserPlugin_AddonSettings_Handler($Sender) {
      $Sender->Installer = new LinkAddonInstaller('/srv/www/source/stable/addons');
      $Sender->ShowHidden = FALSE;
   }
   
   public function PluginController_RunnerPlugins_Create($Sender) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);
      
      $Enable = explode(',',Gdn::Request()->GetValue('Enable',''));
      $Enable = array_fill_keys($Enable, TRUE);
      
      $Disable = explode(',',Gdn::Request()->GetValue('Disable',''));
      $Disable = array_fill_keys($Disable, FALSE);
      
      $PluginList = array();
      $PluginList = array_merge($PluginList, $Enable, $Disable);
      
      $PluginOperations = array_fill_keys(array_keys($PluginList), NULL);
      $OperationStatus = TRUE;
      try {
         
         if (!Gdn::Session()->IsValid() || !GetValue('Token', Gdn::Session()->User, FALSE))
            throw new Exception(FALSE);
         
         foreach ($PluginList as $PluginName => $Action) {
            $PluginOperations[$PluginName] = NULL;
            $PluginStatus = TRUE;
            switch ($Action) {
               case TRUE:  // Enable
                  try {
                     if (Gdn::PluginManager()->GetPluginInfo($PluginName) && !Gdn::PluginManager()->CheckPlugin($PluginName))
                        $PluginStatus = Gdn::PluginManager()->EnablePlugin($PluginName);
                  } catch (Exception $e){ $PluginStatus = FALSE; }
                  break;
               case FALSE: // Disable
                  try {
                     if (Gdn::PluginManager()->CheckPlugin($PluginName))
                        Gdn::PluginManager()->DisablePlugin($PluginName);
                  } catch (Exception $e){ $PluginStatus = FALSE; }
                  break;
               default:
                  break;
            }
            $PluginOperations[$PluginName] = $PluginStatus;
         }
      } catch(Exception $e) { $OperationStatus = FALSE; }
      
      $Sender->SetData("Operation", $OperationStatus);
      $Sender->SetData("Plugins", $PluginOperations);
      $Sender->Render('blank', 'utility', 'dashboard');
   }
   
   public function PluginController_ForceEnablePlugin_Create($Sender) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);
      
      $OperationStatus = TRUE;
      try {
         if (!Gdn::Session()->IsValid() || !GetValue('Token',Gdn::Session()->User, FALSE))
            throw new Exception();
         
         list($PluginName) = $Sender->RequestArgs;
         $Sender->SetData("Plugin", $PluginName);
         if (Gdn::PluginManager()->GetPluginInfo($PluginName) && !Gdn::PluginManager()->CheckPlugin($PluginName))
            $OperationStatus = Gdn::PluginManager()->EnablePlugin($PluginName);
      } catch(Exception $e) { $OperationStatus = FALSE; }
      
      $Sender->SetData("Operation", $OperationStatus);
      $Sender->Render('blank', 'utility', 'dashboard');
   }
   
   public function PluginController_ForceDisablePlugin_Create($Sender) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);
      
      $OperationStatus = TRUE;
      try {
         if (!Gdn::Session()->IsValid() || !GetValue('Token',Gdn::Session()->User, FALSE)) 
            throw new Exception('FALSE');
         
         list($PluginName) = $Sender->RequestArgs;
         $Sender->SetData("Plugin", $PluginName);
         if (Gdn::PluginManager()->CheckPlugin($PluginName))
            Gdn::PluginManager()->DisablePlugin($PluginName);
      } catch(Exception $e) { $OperationStatus = FALSE; }
      
      $Sender->SetData("Operation", $OperationStatus);
      $Sender->Render('blank', 'utility', 'dashboard');
   }
   
   /**
    * Creates a "Create a New Forum" page where users can do just that.
    */
   public function PluginController_CreateForum_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Create a New Forum');
      $Sender->AddSideMenu('dashboard/plugin/myforums');
      
      $Session = Gdn::Session();
      if (!$Session->CheckPermission('Garden.Settings.GlobalPrivs')) {
         $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'permission.php');
      } else {
         $Sender->Form = new Gdn_Form();
         if ($Sender->Form->AuthenticatedPostback()) {
            // $Form - so we can add errors to it if they are encountered
            $Form = &$Sender->Form;
            // $Subdomain - the name of the subdomain to create
            $Subdomain = strtolower($Sender->Form->GetFormValue('Name'));
            // $VFSQL - A SQL object for the vfcom database
            $VFSQL = &$this->_GetDatabase()->SQL();
            $UserID = Gdn::Config('VanillaForums.UserID', -1);
            // $User - the user creating the forum (FALSE if creating a new user)
            $User = $VFSQL->Select()->From('User')->Where('UserID', $UserID)->Get()->FirstRow();
            
            // Depending on the domain of the forum this plugin resides in, use a different hosting domain & codebase (vanilladev is for testing)
            $Domain = strpos(Gdn::Config('Garden.Domain', '.vanillaforums.com'), 'vanilladev') > 0 ? 'vanilladev' : 'vanillaforums';
            $Folder = $Domain == 'vanillaforums' ? 'vanillaforumscom' : 'vanilladev';

            // $VanillaForumsPath - The path to the main VanillaForums.com garden installation (Not required, Defaults to /srv/www/vanillaforumscom)
            $VanillaForumsPath = '/srv/www/'.$Folder;
            // $HostingDomain - the domain that the new forum will be hosted on (Not required, Defaults to .vanillaforums.com)
            $HostingDomain = '.'.$Domain.'.com';
            // $SpawnForum - The path to the spawn forum script (Not required, Defaults to /srv/www/spawnforum)
            $SpawnForum = '/srv/www/misc/utils/spawnforum';
            include('/srv/www/'.$Folder.'/applications/vfcom/utils/createforum.php');

            if ($Form->ErrorCount() == 0) {
               $Sender->StatusMessage = T("The forum was created successfully.");
               $Sender->RedirectUrl = 'http://'.$Subdomain.$HostingDomain.'/dashboard/settings/applyplan';
            }
         }
         
         $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'createforum.php');
      }
   }

   /**
    * Creates a "Delete Forum" page where users can completely remove their
    * forum.
    */
   public function PluginController_DeleteForum_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Delete Forum');
      $Sender->AddSideMenu('dashboard/plugin/myforums');
      $Domain = strpos(Gdn::Config('Garden.Domain', '.vanillaforums.com'), 'vanilladev') > 0 ? 'vanilladev' : 'vanillaforums';
      $Folder = $Domain == 'vanillaforums' ? 'vanillaforumscom' : 'vanilladev';
      
      $Session = Gdn::Session();
      $SiteID = ArrayValue(0, $EventArguments, '');
      $TransientKey = ArrayValue(1, $EventArguments, '');
      $Site = FALSE;
      if (is_numeric($SiteID) && $SiteID > 0) {
         $Site = $this->_GetDatabase()->SQL()->Select('s.*')
            ->From('Site s')
            ->Where('SiteID', $SiteID)
            ->Where('InsertUserID', Gdn::Config('VanillaForums.UserID', -1))
            ->Get()
            ->FirstRow();
         $Sender->Site = $Site;
      }
      
      if (!$Session->ValidateTransientKey($TransientKey)
          || !$Session->CheckPermission('Garden.Settings.GlobalPrivs')
          || !$Site
         ) {
         $this->_CloseDatabase();
         $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'permission.php');
      } else {
         $Sender->Form = new Gdn_Form();
         if ($Sender->Form->AuthenticatedPostback()) {
            $Sender->StatusMessage = T("The forum has been deleted.");
            $Sender->RedirectUrl = Url('plugin/myforums');
            
            // If we are in that forum right now, redirect to another forum the user owns
            if ($SiteID == Gdn::Config('VanillaForums.SiteID', -1)) {
               $NewSite = $this->_GetDatabase()->SQL()
                  ->Select()
                  ->From('Site')
                  ->Where('AccountID', Gdn::Config('VanillaForums.AccountID'))
                  ->Where('SiteID <>', $SiteID)
                  ->Where('Path <>', '')
                  ->Get()
                  ->FirstRow();
               
               // If the user doesn't own any other forums, send them back out to the homepage
               if (!$NewSite) {
                  $Sender->RedirectUrl = 'http://'.$Domain.'.com';
               } else {
                  $Sender->RedirectUrl = 'http://'.$NewSite->Name.'/plugin/myforums';
               }
            }
            // We delete the forum *after* the redirects have been defined so we
            // can use the conf file to determine some things.
            // $SiteID = $Site->SiteID;
            // $VFSQL = &$this->_GetDatabase()->SQL();
            include('/srv/www/'.$Folder.'/applications/vfcom/utils/deleteforum.php');
         }
         
         $this->_CloseDatabase();
         $Sender->Render('/srv/www/misc/plugins/vfoptions/views/deleteforum.php');
      }
   }

   /**
    * Creates a "Rename Forum" page where users can rename their forum's
    * VanillaForums.com subdomain. Note: this ONLY works for vf.com subdomains,
    * and it will cause symlinks to break on custom-domains if used.
    */
   public function PluginController_RenameForum_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Rename Forum');
      $Sender->AddSideMenu('dashboard/plugin/myforums');
      
      $Session = Gdn::Session();
      $SiteID = ArrayValue(0, $EventArguments, '');
      $TransientKey = ArrayValue(1, $EventArguments, '');
      $Site = FALSE;
      if (is_numeric($SiteID) && $SiteID > 0) {
         $Site = $this->_GetDatabase()->SQL()->Select('s.*')
            ->From('Site s')
            ->Where('SiteID', $SiteID)
            ->Where('InsertUserID', Gdn::Config('VanillaForums.UserID', -1))
            ->Get()
            ->FirstRow();
      }
      
      if (!$Session->ValidateTransientKey($TransientKey)
          || !$Session->CheckPermission('Garden.Settings.GlobalPrivs')
          || !$Site
         ) {
         $this->_CloseDatabase();
         $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'permission.php');
      } else {
         $Sender->Form = new Gdn_Form();
         $Parts = explode('.', $Site->Name);
         $Name = array_shift($Parts);
         $Sender->HostingDomain = implode('.', $Parts);

         // Assign the site name from the db if the page has not yet been posted back
         if (!$Sender->Form->AuthenticatedPostback()) {
            $Sender->Form->SetFormValue('Name', $Name);
         } else {
            // $Form - so we can add errors to it if they are encountered
            $Form = &$Sender->Form;
            // $Subdomain - the name of the subdomain to rename to
            $Subdomain = strtolower($Sender->Form->GetFormValue('Name'));
            // $VFSQL - A SQL object for the vfcom database
            $VFSQL = &$this->_GetDatabase()->SQL();
            // $Site - the old site record
            // (loaded above)
            
            // Depending on the domain of the forum this plugin resides in, use a different hosting domain & codebase (vanilladev is for testing)
            $Domain = strpos(Gdn::Config('Garden.Domain', '.vanillaforums.com'), 'vanilladev') > 0 ? 'vanilladev' : 'vanillaforums';
            $Folder = $Domain == 'vanillaforums' ? 'vanillaforumscom' : 'vanilladev';

            // $VanillaForumsPath - The path to the main VanillaForums.com garden installation (Not required, Defaults to /srv/www/vanillaforumscom)
            $VanillaForumsPath = '/srv/www/'.$Folder;
            // $HostingDomain - the domain that the new forum will be hosted on (Not required, Defaults to .vanillaforums.com)
            $HostingDomain = '.'.$Domain.'.com';
            include('/srv/www/'.$Folder.'/applications/vfcom/utils/renameforum.php');

            if ($Sender->Form->ErrorCount() == 0) {
               $Sender->StatusMessage = T("The forum was renamed successfully.");
               $Sender->RedirectUrl = Url('plugin/myforums');
            
               // If we are in that forum right now, Redirect to the new forum
               // domain, and make sure that the view is loaded properly
               if ($SiteID == Gdn::Config('VanillaForums.SiteID', -1))
                  $Sender->RedirectUrl = 'http://'.$Subdomain.'.'.$Domain.'.com/plugin/myforums';

            }
         }
         
         $this->_CloseDatabase();
         $Sender->Render('/srv/www/misc/plugins/vfoptions/views/renameforum.php');
      }
   }
   
   /**
    * Grabs the features for this site from the vfcom database and makes sure
    * that their db status matches their actual status (enables or disables
    * them). This may redirect away if required (ie. the domain has been changed).
	*/ 
   public function SettingsController_ApplyPlan_Create() {
		$PluginManager = Gdn::Factory('PluginManager');

		// Define all of the features to be enabled/disabled
		$Features = array();
		// Old A-La-Carte features
		$Features['customdomain'] = array('CustomDomain');
		$Features['customcss'] = array('CustomCSS', 'CustomTheme'); // Munge these two upgrades
		$Features['adremoval'] = array('NoAds');
		
		// Plans
		$Features['free'] = array();
		$Features['basic'] = array(
			'NoAds', 'PremiumThemes', 'UserManagement', 'BannerLogo',
			'CustomDomain', 'FileUpload', 'PrivateCommunity'
		);
		$Features['plus'] = array(
			'NoAds', 'PremiumThemes', 'UserManagement', 'BannerLogo',
			'CustomDomain', 'FileUpload', 'CustomTheme', 'PrivateCommunity',
			'VanillaConnect', 'Backups'
		);
		$Features['smallgroup'] = array(
			'NoAds', 'PremiumThemes', 'UserManagement', 'BannerLogo',
			'CustomDomain', 'CustomTheme', 'PrivateCommunity',
			'VanillaConnect', 'Backups'
		);
		$Features['premium'] = array(
			'NoAds', 'PremiumThemes', 'UserManagement', 'BannerLogo',
			'CustomDomain', 'FileUpload', 'CustomTheme', 'PrivateCommunity',
			'VanillaConnect', 'Backups', 'SpamControl'
		);
		$Features['smallbusiness'] = $Features['premium'];
		$Features['enterprise'] = $Features['premium'];

      // See what plan the site has
      $SiteID = C('VanillaForums.SiteID', '0');
      $FeatureData = $this->_GetDatabase()->SQL()
         ->Select('sf.*, f.Name, f.Code')
         ->From('SiteFeature sf')
         ->Join('Feature f', 'sf.FeatureID = f.FeatureID')
         ->Where('sf.SiteID', $SiteID)
         ->Where('sf.Selected', '1')
         ->Get();
      
		$ApplyFeatures = array();   
      foreach ($FeatureData as $Feature) {
			$Items = GetValue($Feature->Code, $Features);
			if (is_array($Items))
				$ApplyFeatures = array_merge($ApplyFeatures, $Items);
		}

		// No Advertisements - This is polarized (enabling this feature means turning off the ads plugin).
		$IsEnabled = C('EnabledPlugins.googleadsense', C('EnabledPlugins.GoogleAdSense', '')) == '' ? TRUE : FALSE;
		$IsInPlan = in_array('NoAds', $ApplyFeatures);
		if ($IsInPlan && !$IsEnabled) {
			RemoveFromConfig('EnabledPlugins.googleadsense');
         RemoveFromConfig('EnabledPlugins.GoogleAdSense');
		} else if (!$IsInPlan && $IsEnabled) {
			SaveToConfig('EnabledPlugins.googleadsense', 'googleadsense');
		}
		// Other features
		$this->_ApplyFeature('CustomTheme', array('CustomTheme'), $PluginManager); // Everyone gets CustomTheme plugin turned on
		// But only paying customers get it fully enabled
		$IsInPlan = in_array('CustomTheme', $ApplyFeatures);
		if ($IsInPlan)
			SaveToConfig('Plugins.CustomTheme.Enabled', TRUE);
		else
			RemoveFromConfig('Plugins.CustomTheme.Enabled');

		// $this->_ApplyFeature('CustomCSS', $ApplyFeatures, $PluginManager);
		$this->_ApplyFeature('CustomDomain', $ApplyFeatures, $PluginManager);
		// BannerLogo
		$this->_ApplyConfig('BannerLogo', $ApplyFeatures, 'VanillaForums.BannerLogo.CanUpload');
		$this->_ApplyFeature('VanillaConnect', $ApplyFeatures, $PluginManager);
		$this->_ApplyFeature('FileUpload', $ApplyFeatures, $PluginManager);
		$this->_ApplyConfig('UserManagement', $ApplyFeatures, 'Garden.Roles.Manage');
		$this->_ApplyConfig('UserManagement', $ApplyFeatures, 'Garden.Registration.Manage');
		$this->_ApplyFeature('PrivateCommunity', $ApplyFeatures, $PluginManager);
		// TODO: PrivateCommunity
		// TODO: Backups
		// TODO: SpamControl

		// Remove & Re-Add the vfoptions plugin (so it goes last to have better control over what gets added to the dashboard menu)
		RemoveFromConfig('EnabledPlugins.vfoptions');
		SaveToConfig('EnabledPlugins.vfoptions', 'vfoptions');

		Redirect('/dashboard/settings/gettingstarted');
   }

   /**
    * Don't let the users access the items under the "Add-ons" menu section of
    * the dashboard: applications & plugins (themes was moved to the "
    * Appearance" section.
    * @param Gdn_Controller $Sender
    */
   public function SettingsController_Render_Before(&$Sender) {
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


      if (strcasecmp($Sender->RequestMethod, 'themes') == 0) {
         $ClientName = defined('CLIENT_NAME') ? CLIENT_NAME : '';
         // Remove any themes that are not available.
         $Themes = $Sender->Data('AvailableThemes');
         $Remove = array();
         foreach ($Themes as $Index => $Theme) {
            $Site = GetValue('Site', $Theme);
            if ($Site && $Site != $ClientName)
               $Remove[] = $Index;
         }
         foreach ($Remove as $Index) {
            unset($Sender->Data['AvailableThemes'][$Index]);
         }
      }
      
      if ($Sender->RequestMethod == 'banner')
         $Sender->View = PATH_PLUGINS.'/vfoptions/views/banner.php';

      if ($Sender->RequestMethod == 'registration')
         $Sender->View = PATH_PLUGINS.'/vfoptions/views/registration.php';
   }

   /**
    * Creates a Support form that customers can use to send us support requests.
    */
   public function SettingsController_VanillaSupport_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Vanilla Support');
      $Sender->AddSideMenu('dashboard/settings/vanillasupport');
		$DateLastSupport = C('VanillaForums.DateLastSupportRequest');
		
		// Reset the # of used support requests if in a new month since the last support request
		$UsedSupportRequests = (!$DateLastSupport || date('m', $DateLastSupport) != date('m') ? 0 : C('VanillaForums.UsedSupportRequests', 0));
		$Sender->SetData('UsedSupportRequests', $UsedSupportRequests);
		
		// See how many support requests this account has per month
      $SiteID = C('VanillaForums.SiteID', '0');
      $FeatureData = $this->_GetDatabase()->SQL()
         ->Select('sf.*, f.Name, f.Attributes')
         ->From('SiteFeature sf')
         ->Join('Feature f', 'sf.FeatureID = f.FeatureID')
         ->Where('sf.SiteID', $SiteID)
         ->Where('sf.Selected', '1')
         ->Get();

		$FeatureAttribs = array();   
      foreach ($FeatureData as $Feature) {
			$Items = unserialize($Feature->Attributes);
			if (is_array($Items))
				$FeatureAttribs  = array_merge($FeatureAttribs, $Items);
		}
		$Sender->SetData('SupportRequests', GetValue('SupportRequests', $FeatureAttribs, 0));
      
      $Session = Gdn::Session();
		$Sender->Form = new Gdn_Form();
		if (!$Sender->Form->AuthenticatedPostback()) {
			$Sender->Form->SetFormValue('FromEmail', $Session->User->Email);
		} else {
			// Validate that all fields are populated
			$FromEmail = trim($Sender->Form->GetFormValue('FromEmail'));
			if (!ValidateEmail($FromEmail))
				$Sender->Form->AddError('You must provide your email address.');
			
			$Subject = trim($Sender->Form->GetFormValue('Subject'));
			if (StringIsNullOrEmpty($Subject))
				$Sender->Form->AddError('You must provide a summary of your problem.');

			$Message = trim($Sender->Form->GetFormValue('Message'));
			if (StringIsNullOrEmpty($Message))
				$Sender->Form->AddError('You must provide a detailed summary of your problem.');

			// Build the email to be sent to support
			
			// Include: SiteID, Url, Browser
			$Browser = $Sender->Form->GetFormValue('Browser');
			
			$Email = new Gdn_Email();
			$Email->Subject(sprintf(T('[%1$s] %2$s'), $SiteID, $Subject));
			$Email->From($FromEmail, $FromEmail);
			$Email->To('support@vanillaforums.com', 'VanillaForums.com Support');
			$Email->Message(
				$Message."\r\n"
				."\r\nUrl: ".Url('/', TRUE)
				."\r\nBrowser: ".$Browser
				."\r\nUsername: ".$Session->User->Name
				."\r\nSiteID: ".$SiteID
			);
			
			try {
				$Email->Send();
				SaveToConfig('VanillaForums.UsedSupportRequests', $UsedSupportRequests+1);
				SaveToConfig('VanillaForums.DateLastSupportRequest', mktime());
			} catch (Exception $ex) {
				// GIVE THEM THE support email address!
				$Sender->Form->AddError('Ooops. We had a problem sending your support request. Please use our support email address instead: support@vanillaforums.com');
			}
		}
		
		$Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'vanillasupport.php');
   }

   /**
    * When an administrative user (UserID == 1) is saved, make sure to save the
    * changes across all of the user's forums, including the VanillaForums.com
    * database.
    */
   public function UserModel_AfterSave_Handler(&$Sender, $EventArguments = '') {
      $Fields = ArrayValue('Fields', $EventArguments);
      $UserID = ArrayValue('UserID', $Fields, -1);
      if ($UserID == -1)
         $UserID = $this->_GetUserIDByName(ArrayValue('Name', $Fields, ''));
         
      $VFUserID = Gdn::Config('VanillaForums.UserID', -1);
      $VFAccountID = Gdn::Config('VanillaForums.AccountID', -1);
      $Email = ArrayValue('Email', $Fields);
      $Password = ArrayValue('Password', $Fields); // <-- This was encrypted in the model
      $SaveFields = array();
      if (is_numeric($UserID) && $UserID == 1 && is_numeric($VFUserID) && $VFUserID > 0) {
         // If a new password was specified, save it
         if ($Password !== FALSE)
            $SaveFields['Password'] = $Password;
            
         // If a new email was specified, save that too
         if ($Email !== FALSE)
            $SaveFields['Email'] = $Email;
            
         $this->_SaveAcrossForums($SaveFields, $VFUserID, $VFAccountID);
      }
   }

   /**
    * Before any forum's administrative user (UserID == 1) is saved, validate
    * that the email address being saved isn't being used by any other user in
    * any of their forums, or in the VanillaForums.com database.
    */
   public function UserModel_BeforeSave_Handler(&$Sender, $EventArguments = '') {
      $Fields = ArrayValue('Fields', $EventArguments);
      $UserID = ArrayValue('UserID', $Fields, -1);
      if ($UserID == -1)
         $UserID = $this->_GetUserIDByName(ArrayValue('Name', $Fields, ''));

      $VFUserID = Gdn::Config('VanillaForums.UserID', -1);
      $VFAccountID = Gdn::Config('VanillaForums.AccountID', -1);
      $Email = ArrayValue('Email', $Fields);
      if (is_numeric($UserID) && $UserID == 1 && is_numeric($VFUserID) && $VFUserID > 0) {
         // Retrieve all of the user's sites
         $SiteData = $this->_GetDatabase()->SQL()
            ->Select('DatabaseName, Path')
            ->From('Site')
            ->Where('AccountID', $VFAccountID)
            ->Get();

         // If the user is trying to change the email address...
         if ($this->_GetDatabase()->SQL()
            ->Select('UserID')
            ->From('User')
            ->Where('UserID <> ', $VFUserID)
            ->Where('Email', $Email)
            ->Get()
            ->NumRows() > 0) {
            $Sender->Validation->AddValidationResult('Email', 'Email address is already taken by another user.');
         } else {
            // Now check it against all forums the user owns, as well
            if (is_numeric($VFAccountID) && $VFAccountID > 0) {
               $Cnn = @mysql_connect(
                  Gdn::Config('Database.Host', ''),
                  Gdn::Config('Database.User', ''),
                  Gdn::Config('Database.Password', '')
               );
               if ($Cnn) {
                  foreach ($SiteData as $Site) {
                     if ($Site->Path != '') {
                        mysql_select_db($Site->DatabaseName, $Cnn);
                        $Result = mysql_query("select UserID from GDN_User where UserID <> 1 and Email = '".mysql_real_escape_string($Email, $Cnn)."'");
                        if ($Result && mysql_num_rows($Result) > 0) {
                           $Sender->Validation->AddValidationResult('Email', 'Email address is already taken by another user.');
                           break;
                        }
                     }
                  }
                  mysql_close($Cnn);
               }
            }
         }
         $this->_CloseDatabase();
      }
   }

   /**
    * No setup required.
    */
   public function Setup() {}
   
	private function _ApplyFeature($FeatureName, $Features, $PluginManager) {
		$IsEnabled = C('EnabledPlugins.'.$FeatureName);
		$IsInPlan = in_array($FeatureName, $Features);
		if ($IsInPlan && !$IsEnabled) {
			// Make sure the plugin symlink exists
			$SourcePath = '/srv/www/misc/plugins/'.$FeatureName;
			$DestPath = PATH_PLUGINS.'/'.$FeatureName;
			if (!file_exists($DestPath))
				symlink($SourcePath, $DestPath);
			
			// Enable it.
			$PluginManager->EnablePlugin($FeatureName);
		} else if (!$IsInPlan && $IsEnabled) {
			$PluginManager->DisablePlugin($FeatureName);
		}
	}
	
	private function _ApplyConfig($FeatureName, $Features, $ConfigSetting) {
		SaveToConfig($ConfigSetting, in_array($FeatureName, $Features) ? TRUE : FALSE);
	}
   
   /**
    * Opens a connection to the VanillaForums.com database.
    */
   private $_Database = FALSE;
   private function _GetDatabase() {
      if (!is_object($this->_Database)) {
         $this->_Database = new Gdn_Database(array(
            'Name' => C('VanillaForums.Database.Name', 'vfcom'),
            'Host' => C('VanillaForums.Database.Host', C('Database.Host')),
            'User' => C('VanillaForums.Database.User', C('Database.User')),
            'Password' => C('VanillaForums.Database.Password', C('Database.Password'))
         ));
      }
         
      return $this->_Database;
   }
   private function _CloseDatabase() {
      if (is_object($this->_Database)) {
         $this->_Database->CloseConnection();
         $this->_Database = FALSE;
      }
   }
   
   /**
    * Retrieves a User record by Name from the current database.
    */
   private function _GetUserIDByName($Name) {
      $UserModel = Gdn::UserModel();
      $User = $UserModel->GetByUsername($Name);
      return (is_object($User) && property_exists($User, 'UserID')) ? $User->UserID : -1;
   }
   
   /**
    * Re-authenticates a user with the current configuration.
    */
   private function _ReAuthenticate(&$Sender, $RedirectTo = '') {
      // If there was a request to reauthenticate (ie. we've been shifted to a custom domain and the user needs to reauthenticate)
      // Check the user's transientkey to make sure they're not a spoofer, and then authenticate them.
      if (ArrayValue(0, $Sender->RequestArgs, '') == 'auth') {
         $PostBackKey = ArrayValue(1, $Sender->RequestArgs, '');
         $UserModel = Gdn::UserModel();
         $AdminUser = $UserModel->GetSession(1);
         $Attributes = Gdn_Format::Unserialize($AdminUser->Attributes);
         $TransientKey = is_array($Attributes) ? ArrayValue('TransientKey', $Attributes) : FALSE;
         if ($TransientKey == $PostBackKey) {
            $Identity = new Gdn_CookieIdentity();
            $Identity->Init(array(
               'Salt' => Gdn::Config('Garden.Cookie.Salt'),
               'Name' => Gdn::Config('Garden.Cookie.Name'),
               'Domain' => Gdn::Config('Garden.Cookie.Domain')
            ));
            $Identity->SetIdentity(1, TRUE);
            
            // Now that the identity has been set, redirect again so that the page loads properly
            if ($RedirectTo != '') {
               $this->_CloseDatabase();
               Redirect($RedirectTo);
            }
         }
      }
   }
   
   /**
    * Save $FieldsToSave to all of the admin users across databases for
    * $VFAccountID, as well as the appropriate VanillaForums.GDN_User row.
    */
   private function _SaveAcrossForums($FieldsToSave, $VFUserID, $VFAccountID) {
      // Retrieve all of the user's sites
      $SiteData = $this->_GetDatabase()->SQL()
         ->Select('DatabaseName')
         ->From('Site')
         ->Where('AccountID', $VFAccountID)
         ->Where('Path <>', '')
         ->Get();

      // Save to VF.com db.
      $this->_GetDatabase()->SQL()->Put('User', $FieldsToSave, array('UserID' => $VFUserID));
      
      // Save to all user's forums
      $Cnn = @mysql_connect(
         Gdn::Config('Database.Host', ''),
         Gdn::Config('Database.User', ''),
         Gdn::Config('Database.Password', '')
      );
      if ($Cnn) {
         foreach ($SiteData as $Site) {
            mysql_select_db($Site->DatabaseName, $Cnn);
            $Query = 'update GDN_User set ';
            foreach ($FieldsToSave as $Field => $Value) {
               $Query .= $Field." = '".mysql_real_escape_string($Value, $Cnn)."' ";
            }
            $Query .= 'where UserID = 1';
            mysql_query($Query, $Cnn);
         }
         mysql_close($Cnn);
      }
      $this->_CloseDatabase();
   }

   /**
    * When going to the home site for a payment transaction
    * (increasing/decreasing subscription amounts, updating cc info, etc) it is
    * necessary to call this method so that the user's transient key can be used
    * to authenticate them in relation to their site.
    */
   private function _SetTransientKey($Site = FALSE) {
      $Session = Gdn::Session();
      $SiteID = Gdn::Config('VanillaForums.SiteID', 0);
      if (!is_object($Site)) {
         $Site = $this->_GetDatabase()->SQL()
            ->Select()
            ->From('Site')
            ->Where('SiteID', $SiteID)
            ->Get()
            ->FirstRow();
      }
      if (is_object($Site)) {
         // Update the site attributes with the user's transientkey so we can authenticate them at the checkout
         $Attributes = Gdn_Format::Unserialize($Site);
         if (!is_array($Attributes))
            $Attributes = array();
            
         $Attributes['UserTransientKey'] = $Session->TransientKey();
         $this->_GetDatabase()->SQL()->Put('Site', array('Attributes' => Gdn_Format::Serialize($Attributes)), array('SiteID' => $SiteID));
      }      
   }


   /**
    *
    * @param SettingsController $Sender
    * @param array $Args
    */
   public function SettingsController_Addons_Create($Sender, $Args) {
      $Sender->Title('Addons');
      $Sender->Permission('Garden.Applications.Manage');
      $Sender->AddSideMenu('dashboard/settings/addons');

      $Action = GetValue(0, $Args);
      $Key = GetValue(1, $Args);
      if (Gdn::Session()->ValidateTransientKey($Sender->Request->Get('TransientKey')) && $Key) {
         try {
            switch (strtolower($Action)) {
               case 'enable':
                  Gdn::PluginManager()->EnablePlugin($Key, NULL);
                  break;
               case 'disable':
                  Gdn::PluginManager()->DisablePlugin($Key, NULL);
            }
         } catch (Exception $Ex) {
            $Sender->Form->AddError($Ex);
         }
      }

      $Sender->Render('Addons', '', 'plugins/vfoptions');
   }
}