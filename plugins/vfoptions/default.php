<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['vfoptions'] = array(
   'Name' => 'VF.com Admin Options',
   'Description' => 'VF.com admin options.',
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class VFOptionsPlugin implements Gdn_IPlugin {

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
      echo Anchor('My Account', 'dashboard/settings/myaccount', 'MyAccountLink');
   }
   
   /**
    * Adds & removes dashboard menu options.
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      // Clean out entire menu & re-add everything
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->ClearGroups();
      
      $Menu->AddItem('Dashboard', T('Dashboard'));
      $Menu->AddLink('Dashboard', T('Dashboard'), 'dashboard/settings', 'Garden.Settings.Manage');
      $Menu->AddLink('Dashboard', 'My Forums', 'dashboard/plugin/myforums', 'Garden.Settings.GlobalPrivs');

      $Menu->AddItem('Appearance', T('Appearance'));
		$Menu->AddLink('Appearance', T('Banner <span class="New">New</span>'), 'dashboard/settings/banner', 'Garden.Settings.Manage');
      $Menu->AddLink('Appearance', T('Themes'), 'dashboard/settings/themes', 'Garden.Themes.Manage');
      $Menu->AddLink('Appearance', 'Custom Theme <span class="New">New</span>', 'settings/customtheme', 'Garden.AdminUser.Only');
      $Menu->AddLink('Appearance', T('Messages'), 'dashboard/message', 'Garden.Messages.Manage');
		
      $Menu->AddItem('Users', T('Users'));
      $Menu->AddLink('Users', T('Users'), 'dashboard/user', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'));
		$Menu->AddLink('Users', T('Roles & Permissions'), 'dashboard/role', 'Garden.Roles.Manage');
			
      if (C('Garden.Registration.Manage', TRUE))
			$Menu->AddLink('Users', T('Registration'), 'dashboard/settings/registration', 'Garden.Registration.Manage');
			
      if (C('Garden.Registration.Method') == 'Approval')
         $Menu->AddLink('Users', T('Applicants'), 'dashboard/user/applicants', 'Garden.Applicants.Manage');
		
		$Menu->AddItem('Forum', T('Forum Settings'));
      $Menu->AddLink('Forum', T('Categories'), 'vanilla/settings/managecategories', 'Vanilla.Categories.Manage');
      $Menu->AddLink('Forum', T('Spam'), 'vanilla/settings/spam', 'Vanilla.Spam.Manage');
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
         $Footer = Anchor('Terms of Service', 'http://'.$Url.'.com/info/termsofservice', '', array('target' => '_New'))
            .' | '
            .Anchor('Privacy Policy', 'http://'.$Url.'.com/info/privacy', '', array('target' => '_New'))
            .' | '
            .Anchor('Refund Policy', 'http://'.$Url.'.com/info/refund', '', array('target' => '_New'))
            .' | '
            .Anchor('Contact', 'http://'.$Url.'.com/info/contact', '', array('target' => '_New'));
         $Sender->AddAsset('Foot', Wrap($Footer, 'div', array('style' => 'float: right;')));
         $Sender->AddCssFile('plugins/vfoptions/vfcomoptions.css', 'dashboard');
      }
      
      // Redirect if the domain in the url doesn't match that in the config (so
      // custom domains can't be accessed from their original subdomain).
      $Domain = Gdn::Config('Garden.Domain', '');
      $ServerName = ArrayValue('SERVER_NAME', $_SERVER, '');
      if ($ServerName == '')
         $ServerName = ArrayValue('HTTP_HOST', $_SERVER, '');
         
      if ($ServerName != '' && $Domain != '') {
         $Domain = str_replace(array('http://', '/'), array('', ''), $Domain);
         $ServerName = str_replace(array('http://', '/'), array('', ''), $ServerName);
         if ($ServerName != $Domain)
            Redirect(Gdn_Url::Request(TRUE, TRUE));
         
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
   
   /**
    * Creates a "Buy Now" url that sends the user directly to checkout for an upgrade.
   public function PluginController_BuyNow_Create(&$Sender, $EventArguments) {
      $SiteID = Gdn::Config('VanillaForums.SiteID', 0);
      $Sender->Permission('Garden.AdminUser.Only');
      $FeatureCode = ArrayValue(0, $Sender->RequestArgs, '');
      if ($SiteID <= 0 || $FeatureCode == '') {
         $this->PluginController_LearnMore_Create($Sender, $EventArguments);
      } else {
         // Select the feature and redirect to the checkout
         $this->_SetSelectionGoToCheckout($FeatureCode);
      }
   }
    */
   
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
    * Creates a "Custom Domain" upgrade offering screen where users can purchase
    * & implement a custom domain.
   public $AddSideMenu = TRUE;
   public function PluginController_CustomDomain_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Premium Upgrades &raquo; Custom Domain Name');
      if ($this->AddSideMenu)
         $Sender->AddSideMenu('dashboard/plugin/upgrades');

      // Send a request to the specified domain, and see if it hits our
      // server (it should return our custom 404 error if it is pointed at
      // us).
      $Sender->Form = new Gdn_Form();
      $Domain = $Sender->Form->GetValue('CustomDomain', '');
      $Response = '';
      if ($Domain != '') {
         // Make sure it isn't already in use
         if (file_exists('/srv/www/vhosts/'.$Domain)) {
            $Sender->Form->AddError('The requested domain is already assigned.');
         } else {
            $FQDN = PrefixString('http://', $Domain);
            $Error = FALSE;
            try {
               $Response = ProxyRequest($FQDN);
               $ExpectedResponse = ProxyRequest('http://reserved.vanillaforums.com');               
            } catch(Exception $e) {
               $Error = TRUE;
               // Don't do anything with the exception
            }
            if ($Error || $Response != $ExpectedResponse) {
               $Sender->Form->AddError("We were unable to verify that ".$Domain." is pointing at VanillaForums.com.");
            } else {
               // Everything is set up properly, so select the upgrade and go to checkout
               $this->_SetSelectionGoToCheckout('customdomain', array('Domain' => $Domain));
            }
         }
      }
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'customdomain.php');
   }
    */

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
            $SiteID = $Site->SiteID;
            $VFSQL = &$this->_GetDatabase()->SQL();
            include('/srv/www/'.$Folder.'/applications/vfcom/utils/deleteforum.php');
         }
         
         $this->_CloseDatabase();
         $Sender->Render('/srv/www/misc/plugins/vfoptions/views/deleteforum.php');
      }
   }
   
   /**
    * Creates a "Learn More" screen that contains more info on upgrade
    * offerings.
   public function PluginController_LearnMore_Create(&$Sender, $EventArguments) {
      $SiteID = Gdn::Config('VanillaForums.SiteID', 0);
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Premium Upgrades &raquo; Learn More');
      $Sender->AddSideMenu('dashboard/plugin/upgrades');
      $Sender->Form = new Gdn_Form();
      $Sender->Form->AddHidden('SiteID', $SiteID);
      $FeatureCode = ArrayValue(0, $Sender->RequestArgs, '');
      if ($SiteID <= 0)
         $FeatureCode = 'error';
      
      if ($FeatureCode == 'customdomain') {
         $this->AddSideMenu = FALSE;
         return $this->PluginController_CustomDomain_Create($Sender, $EventArguments);
      }
         
      if ($Sender->Form->IsPostBack()) {
         // Select the feature and redirect to the checkout
         $this->_SetSelectionGoToCheckout($FeatureCode);
      } else {
         $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'learnmore.php');
      }
   }
    */
   
   /**
    * Creates a "Learn More" screen that contains more info on upgrade
    * offerings.
   public function PluginController_MoreInfo_Create(&$Sender, $EventArguments) {
      $Sender->Title('Premium Upgrades &raquo; Learn More');
      $Sender->AddSideMenu('dashboard/plugin/upgrades');
      $Sender->Form = new Gdn_Form();
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'moreinfo.php');
   }
    */

   /**
    * Creates a "My Forums" management screen where users can review, add, and
    * rename their forums.
    */
   public function PluginController_MyForums_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('My Forums');
      $Sender->AddSideMenu('dashboard/plugin/myforums');

      $Sender->SiteData = $this->_GetDatabase()->SQL()->Select('s.*')
         ->From('Site s')
         ->Where('AccountID', Gdn::Config('VanillaForums.AccountID', -1))
         ->Where('InsertUserID', Gdn::Config('VanillaForums.UserID', -1))
         ->Where('Path <>', '') // Make sure default site or buggy rows are excluded
         ->Get();
      $this->_CloseDatabase();
      
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'myforums.php');
   }

   /**
    * Creates a "Remove Upgrade" screen where users can remove previously
    * purchased upgrade offerings.
   public function PluginController_Remove_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Premium Upgrades &raquo; Remove Upgrade');
      $Sender->AddSideMenu('dashboard/plugin/upgrades');
      $UpgradeToRemove = ArrayValue(0, $Sender->RequestArgs, '');
      $SiteID = Gdn::Config('VanillaForums.SiteID', '0');
      $Sender->Form = new Gdn_Form();
      if ($Sender->Form->IsPostBack()) {
         $Feature = $this->_GetDatabase()->SQL()->Select('FeatureID')->From('Feature')->Where('Code', $UpgradeToRemove)->Get()->FirstRow();
         $FeatureID = is_object($Feature) ? $Feature->FeatureID : 0;
         if ($FeatureID > 0) {
            // Mark the feature for removal
            $Session = Gdn::Session();
            $this->_GetDatabase()->SQL()->Replace(
               'SiteFeature',
               array('Selected' => '0', 'UpdateUserID' => $Session->UserID, 'DateUpdated' => Gdn_Format::ToDateTime()),
               array('SiteID' => $SiteID, 'FeatureID' => $FeatureID)
            );
            
            // Figure out where to send the user for subscription update
            $Site = $this->_GetDatabase()->SQL()
               ->Select()
               ->From('Site')
               ->Where('SiteID', $SiteID)
               ->Get()
               ->FirstRow();
      
            $UpdateUrl = 'https://www.vanillaforums.com/payment/synch/';
            if (is_object($Site)) {
               // Point at vanilladev.com if that's where this site is managed
               if (strpos($Site->Name, 'vanilladev') !== FALSE)
                  $UpdateUrl = 'https://www.vanilladev.com/payment/synch/';
            }
            
            // Set the transient key for authentication on the other side
            $this->_SetTransientKey($Site);
            
            // Close any open db connections
            $this->_CloseDatabase();
            
            // Redirect
            $SiteUrl = $Site->Domain == '' ? $Site->Name : $Site->Domain;
            $Session = Gdn::Session();
            Redirect($UpdateUrl.$SiteID.'/'.$Session->TransientKey().'/?Redirect=http://'.$SiteUrl.'/plugin/removecomplete/');
            return;
         } else {
            $Sender->Form->AddError('Failed to remove upgrade. Please contact support@vanillaforums.com for assistance.');
         }
      }

      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'remove.php');
   }
    */
/*   
   public function PluginController_RemoveComplete_Create(&$Sender, $EventArguments) {
      $Sender->Title('Premium Upgrades &raquo; Remove Upgrade');
      $Sender->AddSideMenu('dashboard/plugin/upgrades');
      // Remove the feature from the forum
      $this->_ApplyUpgrades();
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'removecomplete.php');
   }
*/   
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
    * Allows you to spoof the admin user if you have admin access in the
    * VanillaForums.com database.
    */
   public function PluginController_Spoof_Create(&$Sender) {
      $Sender->Title('Spoof User');
      $Sender->AddSideMenu('dashboard/user');
      $Sender->Form = new Gdn_Form();
      $Email = $Sender->Form->GetValue('Email', '');
      $Password = $Sender->Form->GetValue('Password', '');
      $UserIDToSpoof = ArrayValue(0, $Sender->RequestArgs, '1');
      if ($Email != '' && $Password != '') {
         // Validate the username & password
         $UserModel = Gdn::UserModel();
         $UserModel->SQL = $this->_GetDatabase()->SQL();
         $UserData = $UserModel->ValidateCredentials($Email, 0, $Password);
         if (is_object($UserData) && $UserData->Admin == '1') {
            $Identity = new Gdn_CookieIdentity();
            $Identity->Init(array(
               'Salt' => Gdn::Config('Garden.Cookie.Salt'),
               'Name' => Gdn::Config('Garden.Cookie.Name'),
               'Domain' => Gdn::Config('Garden.Cookie.Domain')
            ));
            $Identity->SetIdentity($UserIDToSpoof, TRUE);
            $this->_CloseDatabase();
            Redirect('settings');
         } else {
            $Sender->Form->AddError('Bad Credentials');
         }
      }
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'spoof.php');
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
		$Features['free'] = array();
		$Features['basic'] = array(
			'NoAds', 'PremiumThemes', 'UserManagement', 'BannerLogo'
		);
		$Features['plus'] = array(
			'NoAds', 'PremiumThemes', 'UserManagement', 'BannerLogo',
			'CustomTheme', 'PrivateCommunity', 'VanillaConnect', 'Backups'
		);
		$Features['premium'] = array(
			'NoAds', 'PremiumThemes', 'UserManagement', 'BannerLogo',
			'CustomTheme', 'PrivateCommunity', 'VanillaConnect', 'Backups',
			'FileUpload', 'SpamControl'
		);

      // See what plan the site has
      $SiteID = C('VanillaForums.SiteID', '0');
      $FeatureData = $this->_GetDatabase()->SQL()
         ->Select('sf.*, f.Name, f.Code')
         ->From('SiteFeature sf')
         ->Join('Feature f', 'sf.FeatureID = f.FeatureID')
         ->Where('sf.SiteID', $SiteID)
         ->Get();
         
      foreach ($FeatureData as $Feature) {
			$Items = GetValue($Feature->Code, $Features);
			if (is_array($Items)) {

				// No Advertisements - This is polarized (enabling this feature means turning off the ads plugin).
				$IsEnabled = C('EnabledPlugins.GoogleAdSense', '') == '' ? TRUE : FALSE;
				$IsInPlan = in_array('NoAds', $Items);
				if ($IsInPlan && !$IsEnabled) {
					$PluginManager->DisablePlugin('GoogleAdSense');
				} else if (!$IsInPlan && $IsEnabled) {
					$PluginManager->EnablePlugin('GoogleAdSense');
				}
            // Other features
				$this->_ApplyFeature('CustomTheme', $Items);
				$this->_ApplyFeature('CustomDomain', $Items);
				// BannerLogo
				$IsEnabled = C('VanillaForums.BannerLogo.CanUpload');
				$IsInPlan = in_array('BannerLogo', $Items);
				if ($IsInPlan && !$IsEnabled) {
					SaveToConfig('VanillaForums.BannerLogo.CanUpload', TRUE);
				} else if (!$IsInPlan && $IsEnabled) {
					RemoveFromConfig('VanillaForums.BannerLogo.CanUpload');
				}
				$this->_ApplyFeature('VanillaConnect', $Items);
				$this->_ApplyFeature('FileUpload', $Items);
				// TODO: UserManagement
				// TODO: PrivateCommunity
				// TODO: Backups
				// TODO: SpamControl
         }
      }
		Redirect('/dashboard/settings/thankyou');
   }

   /**
    * Redirect to the user's account page when accessing this url.
    */
   public function SettingsController_MyAccount_Create($Sender) {
      $AccountUrl = 'https://www.vanillaforums.com/myaccount/';
      $SiteID = C('VanillaForums.SiteID', 0);
      $Site = $this->_GetDatabase()->SQL()
         ->Select()
         ->From('Site')
         ->Where('SiteID', $SiteID)
         ->Get()
         ->FirstRow();      
      if (is_object($Site)) {
         // Point at vanilladev.com if that's where this site is managed
         if (strpos($Site->Name, 'vanilladev') !== FALSE)
            $UpdateUrl = 'https://www.vanilladev.com/myaccount/';
      }
      
      // Set the transient key for authentication on the other side
      $this->_SetTransientKey($Site);
      
      // Close any open db connections
      $this->_CloseDatabase();
      
      // Redirect
      $SiteUrl = $Site->Domain == '' ? $Site->Name : $Site->Domain;
      $Session = Gdn::Session();
      Redirect($UpdateUrl.$SiteID.'/'.$Session->TransientKey());
   }
   
   /**
    * Don't let the users access the items under the "Add-ons" menu section of
    * the dashboard: applications & plugins (themes was moved to the "
    * Appearance" section.
    */
   public function SettingsController_Render_Before(&$Sender) {
      if (
         strcasecmp($Sender->RequestMethod, 'plugins') == 0
         || strcasecmp($Sender->RequestMethod, 'applications') == 0
      ) $Sender->AddAsset('Content', '<span style="color: red; font-weight: bold;">REDIRECT</span>');// Redirect('/dashboard/home/permission');
      
      // TODO: Logo upload is enabled if C('VanillaForums.BannerLogo.CanUpload') is TRUE.
      if ($Sender->RequestMethod == 'banners')
         $Sender->View = PATH_PLUGINS.'/vfoptions/views/banner.php';
   }

   /**
    * Creates a "Thank You" page that users can be directed to after they have
    * purchased upgrades.
    **/
   public function SettingsController_ThankYou_Create(&$Sender, $EventArguments) {
      $Sender->Title('Premium Upgrades &raquo; Thank You!');
      $Sender->AddSideMenu('dashboard/settings');
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'thankyou.php');
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
   
	private function _ApplyFeature($FeatureName, $Features) {
		$IsEnabled = C('EnabledPlugins.'.$FeatureName);
		$IsInPlan = in_array($FeatureName, $Features);
		if ($IsInPlan && !$IsEnabled) {
			$PluginManager->EnablePlugin($FeatureName);
		} else if (!$IsInPlan && $IsEnabled) {
			$PluginManager->DisablePlugin($FeatureName);
		}
	}
   
   /**
    * Opens a connection to the VanillaForums.com database.
    */
   private $_Database = FALSE;
   private function _GetDatabase() {
      if (!is_object($this->_Database)) {
         $this->_Database = new Gdn_Database(array(
            'Name' => Gdn::Config('VanillaForums.Database.Name', 'vfcom'),
            'Host' => Gdn::Config('Database.Host'),
            'User' => Gdn::Config('Database.User'),
            'Password' => Gdn::Config('Database.Password')
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
    * Selects an upgrade for activation, records the appropriate information,
    * and sends the user to the checkout page.
   private function _SelectUpgrade($FeatureCode, $Attributes = '') {
      // Define the feature that was selected
      $SiteID = Gdn::Config('VanillaForums.SiteID', '0');
      $Session = Gdn::Session();
      $ExistingRow = $this->_GetDatabase()->SQL()
         ->Select('sf.*')
         ->From('SiteFeature sf')
         ->Join('Feature f', 'sf.FeatureID = f.FeatureID')
         ->Where('sf.SiteID', $SiteID)
         ->Where('f.Code', $FeatureCode)
         ->Get()
         ->FirstRow();
         
      // If the row didn't exist for this site...
      if (!$ExistingRow) {
         // Make sure that the feature does exist
         $Feature = $this->_GetDatabase()->SQL()->Select('FeatureID')->From('Feature')->Where('Code', $FeatureCode)->Get()->FirstRow();
         if (is_object($Feature)) {
            // If the feature does exist, add the row as selected
            $this->_GetDatabase()->SQL()->Insert(
               'SiteFeature',
               array(
                  'SiteID' => $SiteID,
                  'FeatureID' => $Feature->FeatureID,
                  'Selected' => 1,
                  'Active' => 0,
                  'DateInserted' => Gdn_Format::ToDateTime(),
                  'InsertUserID' => $Session->UserID,
                  'Attributes' => Gdn_Format::Serialize($Attributes)
                  )
               );
         } else {
            // If the feature doesn't exist, throw an error
            $Sender->Form->AddError('The requested upgrade does not have an associated record in the features table.');
         }
      } else {
         // Update the row as selected
         $this->_GetDatabase()->SQL()->Put(
            'SiteFeature',
            array(
               'Selected' => 1,
               'Active' => 0,
               'Attributes' => Gdn_Format::Serialize($Attributes),
               'DateUpdated' => Gdn_Format::ToDateTime(),
               'UpdateUserID' => $Session->UserID
            ),
            array(
               'SiteID' => $SiteID,
               'FeatureID' => $ExistingRow->FeatureID
            )
         );
      }
      $this->_CloseDatabase();
   }
    */
   
   /**
    * Applies a selection and redirects to the checkout.
   private function _SetSelectionGoToCheckout($FeatureCode, $Attributes = '') {
      $this->_SelectUpgrade($FeatureCode, $Attributes);
      
      // Figure out which checkout to send them to (dev or production):
      $SiteID = Gdn::Config('VanillaForums.SiteID', 0);
      $Site = $this->_GetDatabase()->SQL()
         ->Select()
         ->From('Site')
         ->Where('SiteID', $SiteID)
         ->Get()
         ->FirstRow();
      
      $CheckoutUrl = 'https://www.vanillaforums.com/payment/pay/';
      if (is_object($Site)) {
         // Point at vanilladev.com if that's where this site is managed
         if (strpos($Site->Name, 'vanilladev') !== FALSE)
            $CheckoutUrl = 'https://www.vanilladev.com/payment/pay/';
            
         $this->_SetTransientKey($Site);
      }
      
      $this->_CloseDatabase();
      $Session = Gdn::Session();
      Redirect($CheckoutUrl.$SiteID.'/'.$Session->TransientKey());
   }
    */
   
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
}