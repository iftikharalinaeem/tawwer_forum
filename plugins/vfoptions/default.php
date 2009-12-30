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
*/

   public function Base_Render_Before(&$Sender) {
      $TrackerCode = Gdn::Config('Plugins.GoogleAnalytics.TrackerCode');
      $TrackerDomain = Gdn::Config('Plugins.GoogleAnalytics.TrackerDomain');
      
      if ($TrackerCode && $TrackerCode != '') {
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

   // Adds a "My Forums" menu option to the dashboard area
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Dashboard', 'My Forums', 'garden/plugin/myforums', 'Garden.Settings.GlobalPrivs');
      $Menu->AddLink('Dashboard', 'Premium Upgrades', 'garden/plugin/upgrades', 'Garden.Settings.GlobalPrivs', array('class' => 'HighlightButton'));
      
      // Remove the addons menu items
      $Menu->RemoveGroup('Add-ons');
      
      // Add the "Appearance" menu group & items
      $Menu->AddItem('Appearance', 'Appearance');
      $Menu->AddLink('Appearance', 'Themes', 'settings/themes', 'Garden.Themes.Manage');
   }
   
   // Don't let the users access applications, plugins, or themes
   public function SettingsController_Render_Before(&$Sender) {
      if (
         strcasecmp($Sender->RequestMethod, 'plugins') == 0
         || strcasecmp($Sender->RequestMethod, 'applications') == 0
      ) Redirect($Sender->Routes['DefaultPermission']);
   }

   // "My Forums" mgmt screen
   public function PluginController_MyForums_Create(&$Sender, $EventArguments) {
      $Sender->Title('My Forums');
      $Sender->AddSideMenu('garden/plugin/myforums');

      $Database = $this->_GetDatabase();
      $Sender->SiteData = $Database->SQL()->Select('s.*')
         ->From('Site s')
         ->Where('AccountID', Gdn::Config('VanillaForums.AccountID', -1))
         ->Where('InsertUserID', Gdn::Config('VanillaForums.UserID', -1))
         ->Where('Path <>', '') // Make sure default site or buggy rows are excluded
         ->Get();
      $Database->CloseConnection();
      
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'myforums.php');
   }

   // "Premium Upgrades" mgmt screen
   public function PluginController_Upgrades_Create(&$Sender, $EventArguments) {
      $Sender->Title('Premium Upgrades');
      $Sender->AddCssFile('/plugins/vfoptions/style.css');
      $Sender->AddSideMenu('garden/plugin/upgrades');
      $View = Gdn::Config('Plugins.VFOptions.UpgradeView', 'upgrades.php');
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . $View);
   }
   
   public function PluginController_CustomDomain_Create(&$Sender, $EventArguments) {
      $Sender->Title('Premium Upgrades &raquo; Custom Domain Name');
      $Sender->AddSideMenu('garden/plugin/upgrades');

      // Send a request to the specified domain, and see if it hits our
      // server (it should return our custom 404 error if it is pointed at
      // us).
      $Sender->Form = new Gdn_Form();
      $Domain = $Sender->Form->GetValue('CustomDomain', '');
      $Response = '';
      if ($Domain != '') {
         $Domain = PrefixString('http://', $Domain);
         $Response = ProxyRequest($Domain);
         $ExpectedResponse = ProxyRequest('http://reserved.vanillaforums.com');
         if ($Response != $ExpectedResponse) {
            $Sender->Form->AddError("We were unable to verify that ".$Domain." is pointing at VanillaForums.com.");
         } else {
            // It is pointing at the correct place, so change the name and create a symlink folder.
         }
      }
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'customdomain.php');
   }

   public function PluginController_Remove_Create(&$Sender, $EventArguments) {
      $Sender->Title('Premium Upgrades &raquo; Remove Upgrade');
      $Sender->AddSideMenu('garden/plugin/upgrades');
      $About = ArrayValue(0, $Sender->RequestArgs, '');
      $Sender->Form = new Gdn_Form();
      if ($Sender->Form->IsPostBack()) {
         if ($About == 'adremoval') {
            $Validation = new Gdn_Validation();
            $PluginManager = Gdn::Factory('PluginManager');
            $PluginManager->EnablePlugin('GoogleAdSense', $Validation);
            Redirect('garden/plugin/upgrades');
         }
      }

      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'remove.php');
   }
   
   public function PluginController_ThankYou_Create(&$Sender, $EventArguments) {
      $Sender->Title('Premium Upgrades &raquo; Thank You!');
      $Sender->AddSideMenu('garden/plugin/upgrades');
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'thankyou.php');
   }
   
   public function PluginController_LearnMore_Create(&$Sender, $EventArguments) {
      $Sender->Title('Premium Upgrades &raquo; Learn More');
      $Sender->AddSideMenu('garden/plugin/upgrades');
      $Sender->Form = new Gdn_Form();
      $About = ArrayValue(0, $Sender->RequestArgs, '');
      if ($About == 'customdomain')
         return $this->PluginController_CustomDomain_Create($Sender, $EventArguments);
         
      if ($Sender->Form->IsPostBack()) {
         if ($About == 'adremoval') {
            $PluginManager = Gdn::Factory('PluginManager');
            $PluginManager->DisablePlugin('GoogleAdSense');
         }
         $this->PluginController_ThankYou_Create($Sender, $EventArguments);
      } else {
         $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'learnmore.php');
      }
   }
   
   // Create a New Forum screen
   public function PluginController_CreateForum_Create(&$Sender, $EventArguments) {
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

   // Rename forum screen
   public function PluginController_RenameForum_Create(&$Sender, $EventArguments) {
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
         $this->_GetDatabase()->CloseConnection();
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
         
         $this->_GetDatabase()->CloseConnection();
         $Sender->Render('/srv/www/misc/plugins/vfoptions/views/renameforum.php');
      }
   }

   // Delete forum screen
   public function PluginController_DeleteForum_Create(&$Sender, $EventArguments) {
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
         $this->_GetDatabase()->CloseConnection();
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
         
         $this->_GetDatabase()->CloseConnection();
         $Sender->Render('/srv/www/misc/plugins/vfoptions/views/deleteforum.php');
      }
   }
   
   // Before UserID 1 saves are processed, validate the email address across forums.
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
         // $this->_GetDatabase()->CloseConnection();
      }
   }
   
   // Save UserID 1 password & email changes across all user's forums (including vanillaforums.com db)
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

   public function Setup() {
      // No setup required.
   }
   
   // Main VanillaForums.com database connection
   private $_Database = FALSE;
   private function _GetDatabase() {
      if (!$this->_Database) {
         $this->_Database = new Gdn_Database(array(
            'Name' => 'vanillaforumscom',
            'Host' => Gdn::Config('Database.Host'),
            'User' => Gdn::Config('Database.User'),
            'Password' => Gdn::Config('Database.Password')
         ));
      }
         
      return $this->_Database;
   }
   
   private function _GetUserIDByName($Name) {
      $UserModel = Gdn::UserModel();
      $User = $UserModel->Get($Name);
      return (is_object($User) && property_exists($User, 'UserID')) ? $User->UserID : -1;
   }
   
   // Save the specified fields to the appropriate vf.com GDN_User row, as well
   // as all of the related forums for GDN_User.UserID = 1
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
      // $this->_GetDatabase()->CloseConnection();
   }
}