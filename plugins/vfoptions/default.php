<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['vfoptions'] = array(
   'Name' => 'VF.com Admin Options',
   'Description' => 'Adds global admin options to the admin screens at VanillaForums.com, allowing administrators to add, edit, and purchase premium upgrades for their hosted forums.',
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
   5. Show the form that allows upgrades
   6. Show the form that allows users to delete a forum
   7. Show the domain name form (if purchased)
   8. Don't allow email to be changed to one that is already being used in master db.
   9. Show upgrade offerings & more info on each
   10. Allow purchase & enabling of upgrade offerings
   11. Include Google analytics code if the appropriate settings are in their conf file (see Base_Render_Before)
*/

   /**
    * Adds "My Forums", "Premium Upgrades", and "Appearance" menu options to the
    * dashboard. Removes the "Add-ons" menu option from the dashboard area.
    */
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Dashboard', 'My Forums', 'garden/plugin/myforums', 'Garden.Settings.GlobalPrivs');
      $Menu->AddLink('Dashboard', 'Premium Upgrades ↪', 'garden/plugin/upgrades', 'Garden.Settings.GlobalPrivs', array('class' => 'HighlightButton'));
      
      // Remove the addons menu items
      $Menu->RemoveGroup('Add-ons');
      
      // Add the "Appearance" menu group & items
      $Menu->AddItem('Appearance', 'Appearance');
      $Menu->AddLink('Appearance', 'Themes', 'settings/themes', 'Garden.Themes.Manage');
   }
   
   /**
    * If the domain in the config doesn't match that in the url, this will
    * redirect to the domain in the config. Also includes Google Analytics on
    * all pages if the conf file contains Plugins.GoogleAnalytics.TrackerCode
    * and Plugins.GoogleAnalytics.TrackerDomain.
    */
   public function Base_Render_Before(&$Sender) {
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
    * When an administrative user (UserID == 1) is saved, make sure to save the
    * changes across all of the user's forums, including the VanillaForums.com
    * database.
    */
   public function Gdn_UserModel_AfterSave_Handler(&$Sender, $EventArguments = '') {
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
   public function Gdn_UserModel_BeforeSave_Handler(&$Sender, $EventArguments = '') {
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
    * Creates a "Buy Now" url that sends the user directly to checkout for an upgrade.
    */
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
   
   /**
    * Creates a "Create a New Forum" page where users can do just that.
    */
   public function PluginController_CreateForum_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Create a New Forum');
      $Sender->AddSideMenu('garden/plugin/myforums');
      
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
               $Sender->StatusMessage = Translate("The forum was created successfully.");
               $Sender->RedirectUrl = 'http://'.$Subdomain.$HostingDomain.'/gardensetup/first';
            }
         }
         
         $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'createforum.php');
      }
   }

   /**
    * Creates a "Custom Domain" upgrade offering screen where users can purchase
    * & implement a custom domain.
    */
   public function PluginController_CustomDomain_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Premium Upgrades &raquo; Custom Domain Name');
      $Sender->AddSideMenu('garden/plugin/upgrades');

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

   /**
    * Creates a "Delete Forum" page where users can completely remove their
    * forum.
    */
   public function PluginController_DeleteForum_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Delete Forum');
      $Sender->AddSideMenu('garden/plugin/myforums');
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
            $Sender->StatusMessage = Translate("The forum has been deleted.");
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
    */
   public function PluginController_LearnMore_Create(&$Sender, $EventArguments) {
      $SiteID = Gdn::Config('VanillaForums.SiteID', 0);
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Premium Upgrades &raquo; Learn More');
      $Sender->AddSideMenu('garden/plugin/upgrades');
      $Sender->Form = new Gdn_Form();
      $Sender->Form->AddHidden('SiteID', $SiteID);
      $FeatureCode = ArrayValue(0, $Sender->RequestArgs, '');
      if ($SiteID <= 0)
         $FeatureCode = 'error';
      
      if ($FeatureCode == 'customdomain')
         return $this->PluginController_CustomDomain_Create($Sender, $EventArguments);
         
      if ($Sender->Form->IsPostBack()) {
         // Select the feature and redirect to the checkout
         $this->_SetSelectionGoToCheckout($FeatureCode);
      } else {
         $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'learnmore.php');
      }
   }
   
   /**
    * Creates a "Learn More" screen that contains more info on upgrade
    * offerings.
    */
   public function PluginController_MoreInfo_Create(&$Sender, $EventArguments) {
      $Sender->Title('Premium Upgrades &raquo; Learn More');
      $Sender->AddSideMenu('garden/plugin/upgrades');
      $Sender->Form = new Gdn_Form();
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'moreinfo.php');
   }

   /**
    * Creates a "My Forums" management screen where users can review, add, and
    * rename their forums.
    */
   public function PluginController_MyForums_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('My Forums');
      $Sender->AddSideMenu('garden/plugin/myforums');

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
    */
   public function PluginController_Remove_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Premium Upgrades &raquo; Remove Upgrade');
      $Sender->AddSideMenu('garden/plugin/upgrades');
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
               array('Selected' => '0', 'UpdateUserID' => $Session->UserID, 'DateUpdated' => Format::ToDateTime()),
               array('SiteID' => $SiteID, 'FeatureID' => $FeatureID)
            );
            
            // Figure out where to send the user for subscription update
            $Site = $this->_GetDatabase()->SQL()
               ->Select()
               ->From('Site')
               ->Where('SiteID', $SiteID)
               ->Get()
               ->FirstRow();
      
            $UpdateUrl = 'http://vanillaforums.com/payment/synch/';
            if (is_object($Site)) {
               // Point at vanilladev.com if that's where this site is managed
               if (strpos($Site->Name, 'vanilladev') !== FALSE)
                  $UpdateUrl = 'http://vanilladev.com/payment/synch/';
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
   
   public function PluginController_RemoveComplete_Create(&$Sender, $EventArguments) {
      $Sender->Title('Premium Upgrades &raquo; Remove Upgrade');
      $Sender->AddSideMenu('garden/plugin/upgrades');
      // Remove the feature from the forum
      $this->_ApplyUpgrades();
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'removecomplete.php');
   }
   
   /**
    * Creates a "Rename Forum" page where users can rename their forum's
    * VanillaForums.com subdomain. Note: this ONLY works for vf.com subdomains,
    * and it will cause symlinks to break on custom-domains if used.
    */
   public function PluginController_RenameForum_Create(&$Sender, $EventArguments) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Rename Forum');
      $Sender->AddSideMenu('garden/plugin/myforums');
      
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
               $Sender->StatusMessage = Translate("The forum was renamed successfully.");
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
      $Sender->AddSideMenu('garden/user');
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
    * Creates a "Thank You" page that users can be directed to after they have
    * purchased upgrades.
    */
   public function PluginController_ThankYou_Create(&$Sender, $EventArguments) {
      $this->_ReAuthenticate($Sender, 'garden/plugin/thankyou');
      $Sender->Title('Premium Upgrades &raquo; Thank You!');
      $Sender->AddSideMenu('garden/plugin/upgrades');
      
      // Update the SiteFeature table so that all selected items are now marked active
      $SiteID = Gdn::Config('VanillaForums.SiteID', '0');
      $this->_GetDatabase()->SQL()->Put('SiteFeature', array('Active' => '1'), array('SiteID' => $SiteID, 'Selected' => '1'));
      
      // Now apply upgrades
      $this->_ApplyUpgrades();
      
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'thankyou.php');
   }
   
   /**
    * Creates a "Premium Upgrades" management screen where users can review &
    * purchase upgrade offerings.
    */
   public function PluginController_Upgrades_Create(&$Sender, $EventArguments) {
      $this->_ReAuthenticate($Sender, 'garden/plugin/upgrades');
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Premium Upgrades');
      $Sender->AddCssFile('/plugins/vfoptions/style.css');
      $Sender->AddSideMenu('garden/plugin/upgrades');
      $SiteID = Gdn::Config('VanillaForums.SiteID', 0);
      // Reset upgrade selections
      $SiteFeatureData = $this->_GetDatabase()->SQL()->Select('SiteFeatureID, Active')->From('SiteFeature')->Where('SiteID', $SiteID)->Get();
      foreach ($SiteFeatureData as $SiteFeature) {
         $this->_GetDatabase()->SQL()->Put('SiteFeature', array('Selected' => $SiteFeature->Active), array('SiteFeatureID' => $SiteFeature->SiteFeatureID));
      }
      
      $View = Gdn::Config('Plugins.VFOptions.UpgradeView', 'upgrades.php');
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . $View);
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
      ) Redirect($Sender->Routes['DefaultPermission']);
   }

   /**
    * No setup required.
    */
   public function Setup() {}
   
   /**
    * Grabs the features for this site from the vfcom database and makes sure
    * that their db status matches their actual status (enables or disables
    * them). This may redirect away if required (ie. the domain has been changed).
    */
   private function _ApplyUpgrades() {
      $Redirect = '';
      
      // Get all upgrades for this site
      $SiteID = Gdn::Config('VanillaForums.SiteID', '0');
      $FeatureData = $this->_GetDatabase()->SQL()
         ->Select('sf.*, f.Name, f.Code')
         ->From('SiteFeature sf')
         ->Join('Feature f', 'sf.FeatureID = f.FeatureID')
         ->Where('sf.SiteID', $SiteID)
         ->Get();
         
      foreach ($FeatureData as $Feature) {

// --== AD REMOVAL ==--

         if ($Feature->Code == 'adremoval') {
            $IsEnabled = Gdn::Config('EnabledPlugins.GoogleAdSense', '') == '' ? TRUE : FALSE;
            if ($Feature->Active == '1' && !$IsEnabled) {
               // ---- ENABLE ----
               $PluginManager = Gdn::Factory('PluginManager');
               $PluginManager->DisablePlugin('GoogleAdSense');
            } else if ($Feature->Active == '0' && $IsEnabled) {
               // ---- DISABLE ----
               $Conf = PATH_CONF . DS . 'config.php';
               $Contents = file_get_contents($Conf);
               $Contents = str_replace(
                  "\$Configuration['EnabledPlugins']['GoogleAdSense'] = 'googleadsense';\n",
                  '',
                  $Contents
               );
               $Contents = str_replace(
                  "// EnabledPlugins",
                  "// EnabledPlugins
\$Configuration['EnabledPlugins']['GoogleAdSense'] = 'googleadsense';",
                  $Contents
               );
               
               file_put_contents($Conf, $Contents);
            }
            
// --== CUSTOM DOMAINS ==--

         } else if ($Feature->Code == 'customdomain') {
            $Attributes = Format::Unserialize($Feature->Attributes);
            $Domain = ArrayValue('Domain', $Attributes, '');
            $OldDomain = str_replace(array('http://', '/'), array('', ''), Gdn::Config('Garden.Domain', ''));
            $IsEnabled = $Domain == $OldDomain ? TRUE : FALSE;
            if ($Feature->Active == '1' && !$IsEnabled) {
               // ---- ENABLE ----
               if ($Domain != '' && !file_exists('/srv/www/vhosts/'.$Domain)) {
                  $FQDN = PrefixString('http://', $Domain);
                  $Error = FALSE;
                  try {
                     $Response = ProxyRequest($FQDN);
                     $ExpectedResponse = ProxyRequest('http://reserved.vanillaforums.com');               
                  } catch(Exception $e) {
                     $Error = TRUE;
                     // Don't do anything with the exception
                  }
                  if (!$Error && $Response == $ExpectedResponse) {
                     // It is pointing at the correct place, so...
                     // Create the symlink folder
                     exec('/bin/ln -s "/srv/www/vhosts/'.$OldDomain.'" "/srv/www/vhosts/'.$Domain.'"');
                     
                     // Make sure it exists
                     if (file_exists('/srv/www/vhosts/'.$Domain)) {
                        // Change the domain in the conf file
                        $CookieDomain = substr($Domain, strpos($Domain, '.'));
                        $Contents = file_get_contents(PATH_CONF. DS . 'config.php');
                        $Contents = str_replace(
                           array(
                              "\$Configuration['Garden']['Cookie']['Domain'] = '".Gdn::Config('Garden.Cookie.Domain')."';",
                              "\$Configuration['Garden']['Domain'] = '".$OldDomain."';"
                           ),
                           array(
                              "\$Configuration['Garden']['Cookie']['Domain'] = '$CookieDomain';",
                              "\$Configuration['Garden']['Domain'] = '$Domain';"
                           ),
                           $Contents
                        );
                        file_put_contents(PATH_CONF . DS . 'config.php', $Contents);
                        
                        // Update the domain in the VanillaForums.GDN_Site table
                        $this->_GetDatabase()->SQL()->Put(
                           'Site',
                           array(
                              'Domain' => $Domain,
                              'Path' => '/srv/www/vhosts/'.$Domain
                           ),
                           array('SiteID' => Gdn::Config('VanillaForums.SiteID'))
                        );
                        
                        // Redirect to the new domain
                        $Session = Gdn::Session();
                        $Redirect = $FQDN.'/garden/plugin/thankyou/auth/'.$Session->TransientKey();
                     }
                  }
               }
            } else if ($Feature->Active == '0' && $IsEnabled) {
               // ---- DISABLE ----
               $Site = $this->_GetDatabase()->SQL()->Select()->From('Site')->Where('SiteID', $SiteID)->Get()->FirstRow();
               
               if (is_object($Site) && $Site->Domain != '') {
                  // Update the Site record to remove the domain entry & revert the path
                  $this->_GetDatabase()->SQL()->Put(
                     'Site',
                     array(
                        'Domain' => '',
                        'Path' => '/srv/www/vhosts/'.$Site->Name
                     ),
                     array('SiteID' => $SiteID)
                  );
                  
                  // Update the config file
                  $CookieDomain = substr($Site->Name, strpos($Site->Name, '.'));
                  $Contents = file_get_contents(PATH_CONF. DS . 'config.php');
                  $Contents = str_replace(
                     array(
                        "\$Configuration['Garden']['Cookie']['Domain'] = '".Gdn::Config('Garden.Cookie.Domain')."';",
                        "\$Configuration['Garden']['Domain'] = '".Gdn::Config('Garden.Domain')."';"
                     ),
                     array(
                        "\$Configuration['Garden']['Cookie']['Domain'] = '$CookieDomain';",
                        "\$Configuration['Garden']['Domain'] = '".$Site->Name."';"
                     ),
                     $Contents
                  );
                  file_put_contents(PATH_CONF . DS . 'config.php', $Contents);
                  
                  // Remove the symlinked folder
                  // WARNING: Do not use a trailing slash on symlinked folders when rm'ing, or it will remove the source!
                  $SymLinkedFolder = '/srv/www/vhosts/'.$Site->Domain;
                  if (file_exists($SymLinkedFolder))
                     unlink($SymLinkedFolder);
                  
                  // Redirect to the new domain
                  $Session = Gdn::Session();
                  $Redirect = 'http://'.$Site->Name.'/garden/plugin/upgrades/auth/'.$Session->TransientKey();
               }
            }
         }
      }
      if ($Redirect != '') {
         $this->_CloseDatabase();
         Redirect($Redirect);
      }
   }
   
   /**
    * Opens a connection to the VanillaForums.com database.
    */
   private $_Database = FALSE;
   private function _GetDatabase() {
      if (!is_object($this->_Database)) {
         $this->_Database = new Gdn_Database(array(
            'Name' => Gdn::Config('VanillaForums.Database.Name', 'vanillaforumscom'),
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
      $User = $UserModel->Get($Name);
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
         $Attributes = Format::Unserialize($AdminUser->Attributes);
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
    */
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
                  'DateInserted' => Format::ToDateTime(),
                  'InsertUserID' => $Session->UserID,
                  'Attributes' => Format::Serialize($Attributes)
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
               'Attributes' => Format::Serialize($Attributes),
               'DateUpdated' => Format::ToDateTime(),
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
   
   /**
    * Applies a selection and redirects to the checkout.
    */
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
      
      $CheckoutUrl = 'http://vanillaforums.com/payment/pay/';
      if (is_object($Site)) {
         // Point at vanilladev.com if that's where this site is managed
         if (strpos($Site->Name, 'vanilladev') !== FALSE)
            $CheckoutUrl = 'http://vanilladev.com/payment/pay/';
            
         $this->_SetTransientKey($Site);
      }
      
      $this->_CloseDatabase();
      $Session = Gdn::Session();
      Redirect($CheckoutUrl.$SiteID.'/'.$Session->TransientKey());
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
         $Attributes = Format::Unserialize($Site);
         if (!is_array($Attributes))
            $Attributes = array();
            
         $Attributes['UserTransientKey'] = $Session->TransientKey();
         $this->_GetDatabase()->SQL()->Put('Site', array('Attributes' => Format::Serialize($Attributes)), array('SiteID' => $SiteID));
      }      
   }
}