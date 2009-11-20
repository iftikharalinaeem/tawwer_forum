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
*/

   // Adds a "My Forums" menu option to the dashboard area
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Dashboard', 'My Forums', 'garden/plugin/myforums', 'Garden.Settings.GlobalPrivs');
      $Menu->AddLink('Dashboard', 'Premium Upgrades', 'garden/plugin/upgrades', 'Garden.Settings.GlobalPrivs', array('class' => 'HighlightButton'));
      
      // Remove the addons menu items
      $Menu->RemoveGroup('Add-ons');
   }
   
   // Don't let the users access applications, plugins, or themes
   public function SettingsController_Render_Before(&$Sender) {
      if (
         strcasecmp($Sender->RequestMethod, 'plugins') == 0
         || strcasecmp($Sender->RequestMethod, 'applications') == 0
         || strcasecmp($Sender->RequestMethod, 'themes') == 0
      ) Redirect($Sender->Routes['DefaultPermission']);
   }
   

   public function PluginController_MyForums_Create(&$Sender, $EventArguments) {
      $Sender->Title('My Forums');
      $Sender->AddSideMenu('garden/plugin/myforums');

      $Database = $this->GetDatabase();
      $Sender->SiteData = $Database->SQL()->Select('s.*')
         ->From('Site s')
         ->Where('AccountID', Gdn::Config('VanillaForums.AccountID', -1))
         ->Where('InsertUserID', Gdn::Config('VanillaForums.UserID', -1))
         ->Where('Path <>', '') // Make sure default site or buggy rows are excluded
         ->Get();
      $Database->CloseConnection();
      
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'myforums.php');
   }

   public function PluginController_Upgrades_Create(&$Sender, $EventArguments) {
      $Sender->Title('Premium Upgrades');
      $Sender->AddSideMenu('garden/plugin/upgrades');
      $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'upgrades.php');
   }
   
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
            $VFSQL = &$this->GetDatabase()->SQL();
            $UserID = Gdn::Config('VanillaForums.UserID', -1);
            // $User - the user creating the forum (FALSE if creating a new user)
            $User = $VFSQL->Select()->From('User')->Where('UserID', $UserID)->Get()->FirstRow();
            
            // Depending on the domain of the forum this plugin resides in, use a different hosting domain & codebase (chochy is for testing)
            $Domain = strpos(Gdn::Config('Garden.Domain', '.vanillaforums.com'), 'chochy') > 0 ? 'chochy' : 'vanillaforums';

            // $VanillaForumsPath - The path to the main VanillaForums.com garden installation (Not required, Defaults to /srv/www/vanillaforumscom)
            $VanillaForumsPath = '/srv/www/'.$Domain;
            // $HostingDomain - the domain that the new forum will be hosted on (Not required, Defaults to .vanillaforums.com)
            $HostingDomain = '.'.$Domain.'.com';
            // $SpawnForum - The path to the spawn forum script (Not required, Defaults to /srv/www/spawnforum)
            $SpawnForum = '/srv/www/misc/utils/'.($Domain == 'chochy' ? 'chochy' : '').'spawnforum';
            include('/srv/www/'.$Domain.'/applications/vfcom/utils/createforum.php');

            if ($Form->ErrorCount() == 0) {
               $Sender->StatusMessage = Translate("The forum was created successfully.");
               $Sender->RedirectUrl = 'http://'.$Subdomain.$HostingDomain.'/gardensetup/first';
            }
         }
         
         $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'createforum.php');
      }
   }

   public function PluginController_RenameForum_Create(&$Sender, $EventArguments) {
      $Sender->Title('Rename Forum');
      $Sender->AddSideMenu('garden/plugin/myforums');
      $PathPlugins = PATH_PLUGINS;
      
      $Session = Gdn::Session();
      $SiteID = ArrayValue(0, $EventArguments, '');
      $TransientKey = ArrayValue(1, $EventArguments, '');
      $Site = FALSE;
      if (is_numeric($SiteID) && $SiteID > 0) {
         $Site = $this->GetDatabase()->SQL()->Select('s.*')
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
         $this->GetDatabase()->CloseConnection();
         $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'permission.php');
      } else {
         $Sender->Form = new Gdn_Form();
         // Assign the site name from the db if the page has not yet been posted back
         if (!$Sender->Form->AuthenticatedPostback()) {
            $Sender->Form->SetFormValue('Name', $Site->Name);
         } else {
            // $Form - so we can add errors to it if they are encountered
            $Form = &$Sender->Form;
            // $Subdomain - the name of the subdomain to rename to
            $Subdomain = strtolower($Sender->Form->GetFormValue('Name'));
            // $VFSQL - A SQL object for the vfcom database
            $VFSQL = &$this->GetDatabase()->SQL();
            // $Site - the old site record
            // (loaded above)
            
            // Depending on the domain of the forum this plugin resides in, use a different hosting domain & codebase (chochy is for testing)
            $Domain = strpos(Gdn::Config('Garden.Domain', '.vanillaforums.com'), 'chochy') > 0 ? 'chochy' : 'vanillaforums';
            // $VanillaForumsPath - The path to the main VanillaForums.com garden installation (Not required, Defaults to /srv/www/vanillaforumscom)
            $VanillaForumsPath = '/srv/www/'.$Domain;
            // $HostingDomain - the domain that the new forum will be hosted on (Not required, Defaults to .vanillaforums.com)
            $HostingDomain = '.'.$Domain.'.com';
            include('/srv/www/'.$Domain.'/applications/vfcom/utils/renameforum.php');

            if ($Sender->Form->ErrorCount() == 0) {
               $Sender->StatusMessage = Translate("The forum was renamed successfully.");
               $Sender->RedirectUrl = Url('plugin/myforums');
            
               // If we are in that forum right now, Redirect to the new forum
               // domain, and make sure that the view is loaded properly
               if ($SiteID = Gdn::Config('VanillaForums.SiteID', -1)) {
                  $Sender->RedirectUrl = 'http://'.$Subdomain.'.'.$Domain.'.com/plugin/myforums';
                  $PathPlugins = '/srv/www/subdomains/'.$Subdomain.'/plugins';
               }
            }
         }
         
         $this->GetDatabase()->CloseConnection();
         $Sender->Render($PathPlugins . DS . 'vfoptions' . DS . 'views' . DS . 'renameforum.php');
      }
   }

   public function PluginController_DeleteForum_Create(&$Sender, $EventArguments) {
      $Sender->Title('Delete Forum');
      $Sender->AddSideMenu('garden/plugin/myforums');
      
      $Session = Gdn::Session();
      $SiteID = ArrayValue(0, $EventArguments, '');
      $TransientKey = ArrayValue(1, $EventArguments, '');
      $Site = FALSE;
      if (is_numeric($SiteID) && $SiteID > 0) {
         $Site = $this->GetDatabase()->SQL()->Select('s.*')
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
         $this->GetDatabase()->CloseConnection();
         $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'permission.php');
      } else {
         $Sender->Form = new Gdn_Form();
         if ($Sender->Form->AuthenticatedPostback()) {
            $Sender->StatusMessage = Translate("The forum has been deleted.");
            $Sender->RedirectUrl = Url('plugin/myforums');
            
            // If we are in that forum right now, redirect to another forum the user owns
            if ($SiteID == Gdn::Config('VanillaForums.SiteID', -1)) {
               $NewSite = $this->GetDatabase()->SQL()
                  ->Select()
                  ->From('Site')
                  ->Where('AccountID', Gdn::Config('VanillaForums.AccountID'))
                  ->Get()
                  ->FirstRow();
               
               // If the user doesn't own any other forums, send them back out to the homepage
               if (!$NewSite)
                  $this->RedirectUrl = 'http://vanillaforums.com';
               else
                  $this->RedirectUrl = 'http://'.$NewSite->Name.'.vanillaforums.com/plugin/myforums';
            }
            // We delete the forum *after* the redirects have been defined so we
            // can use the conf file to determine somethings.
            $SiteID = $Site->SiteID;
            $VFSQL = &$this->GetDatabase()->SQL();
            include('/srv/www/chochy/applications/vfcom/utils/deleteforum.php');
         }
         
         $this->GetDatabase()->CloseConnection();
         $Sender->Render(PATH_PLUGINS . DS . 'vfoptions' . DS . 'views' . DS . 'deleteforum.php');
      }
   }
   
   public function Setup() {
      // No setup required.
   }
   
   private $_Database = FALSE;
   private function GetDatabase() {
      if (!$this->_Database) {
         // Depending on the domain of the forum this plugin resides in, use a different database (chochy is for testing)
         $DbName = strpos(Gdn::Config('Garden.Domain', '.vanillaforums.com'), 'chochy') > 0 ? 'chochy' : 'vanillaforumscom';

         $this->_Database = new Gdn_Database(array(
            'Name' => $DbName,
            'Host' => Gdn::Config('Database.Host'),
            'User' => Gdn::Config('Database.User'),
            'Password' => Gdn::Config('Database.Password')
         ));
      }
         
      return $this->_Database;
   }
   
}