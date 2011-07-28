<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['CustomDomain'] = array(
   'Name' => 'Custom Domain',
   'Description' => 'Allows users to define a CName address for their VanillaForums.com hosted forum.',
   'Version' => '1.0.1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class CustomDomainPlugin implements Gdn_IPlugin {

   /**
    * Adds & removes dashboard menu options.
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Appearance', 'Custom Domain', 'settings/customdomain', 'Garden.Settings.GlobalPrivs');
   }
   
   /**
    * Creates a "Custom Domain" upgrade offering screen where users can purchase
    * & implement a custom domain.
    */
   public $AddSideMenu = TRUE;
   public function SettingsController_CustomDomain_Create($Sender, $EventArguments) {
      $Session = Gdn::Session();
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Custom Domain Name');
      if ($this->AddSideMenu)
         $Sender->AddSideMenu('settings/customdomain');
         
      $Sender->Site = $this->_GetSite();
      
      // Remove custom domain?
      if (
         GetValue(0, $Sender->RequestArgs, '') == 'remove'
         && $Session->ValidateTransientKey(GetValue(1, $Sender->RequestArgs, ''))
         )
         $this->_RemoveDomain();

      // Send a request to the specified domain, and see if it hits our
      // server (it should return our custom 404 error if it is pointed at
      // us).
      $Sender->Form = new Gdn_Form();
      $Domain = $Sender->Form->GetValue('CustomDomain', '');
      if (!$Sender->Form->IsPostBack())
         $Sender->Form->SetFormValue('CustomDomain', $Sender->Site->Domain);

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
               $this->_SetDomain($Domain);
            }
         }
      }
      $Sender->Render(PATH_PLUGINS . DS . 'CustomDomain' . DS . 'views' . DS . 'customdomain.php');
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
   private function _SetDomain($Domain) {
      // Get all upgrades for this site
//      $OldDomain = str_replace(array('http://', '/'), array('', ''), C('Garden.Domain', ''));
      $Root = rtrim(realpath(PATH_ROOT), '/');
      $OldDomain = trim(strrchr($Root, '/'), '/');
      
      $IsEnabled = $Domain == $OldDomain ? TRUE : FALSE;
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
            exec('/bin/ln -s "'.$Root.'" "/srv/www/vhosts/'.$Domain.'"');
            
            // Make sure it exists
            if (file_exists('/srv/www/vhosts/'.$Domain)) {
               // Change the domain in the conf file
               $CookieDomain = substr($Domain, strpos($Domain, '.'));
               SaveToConfig(array(
                   'Garden.Cookie.Domain' => $CookieDomain,
                   'Garden.Domain' => $Domain));
               
               // Update the domain in the VanillaForums.GDN_Site table
               $this->_GetDatabase()->SQL()->Put(
                  'Site',
                  array(
                     'Domain' => $Domain,
                     'Path' => '/srv/www/vhosts/'.$Domain
                  ),
                  array('SiteID' => C('VanillaForums.SiteID'))
               );
               
               // Redirect to the new domain
               $Session = Gdn::Session();
               $this->_ReAuthenticate($FQDN.'/dashboard/settings/customdomain');
            }
         }
      }
   }
   
   private function _RemoveDomain() {
      $Site = $this->_GetSite();
      if (is_object($Site) && $Site->Domain != '') {
         // Update the Site record to remove the domain entry & revert the path
         $this->_GetDatabase()->SQL()->Put(
            'Site',
            array(
               'Domain' => '',
               'Path' => '/srv/www/vhosts/'.$Site->Name
            ),
            array('SiteID' => $Site->SiteID)
         );
         
         // Update the config file
         $CookieDomain = substr($Site->Name, strpos($Site->Name, '.'));
         SaveToConfig(array(
             'Garden.Cookie.Domain' => $CookieDomain,
             'Garden.Domain' => $Site->Name));
         
         // Remove the symlinked folder
         // WARNING: Do not use a trailing slash on symlinked folders when rm'ing, or it will remove the source!
         $SymLinkedFolder = '/srv/www/vhosts/'.trim($Site->Domain, '/');
         if (file_exists($SymLinkedFolder))
            unlink($SymLinkedFolder);
         
         // Redirect to the new domain
         $Session = Gdn::Session();
         $this->_ReAuthenticate('http://'.$Site->Name.'/dashboard/settings/customdomain');
      }
   }
   
   private function _GetSite() {
      return $this->_GetDatabase()->SQL()->Select()->From('Site')->Where('SiteID', C('VanillaForums.SiteID', '0'))->Get()->FirstRow();      
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
   private function _GetUserIDByName($Name) {
      $UserModel = Gdn::UserModel();
      $User = $UserModel->GetByUsername($Name);
      return (is_object($User) && property_exists($User, 'UserID')) ? $User->UserID : -1;
   }
    */
   
   /**
    * Re-authenticates a user with the current configuration.
    */
   private function _ReAuthenticate($RedirectTo = '') {
      // If there was a request to reauthenticate (ie. we've been shifted to a custom domain and the user needs to reauthenticate)
      // Check the user's transientkey to make sure they're not a spoofer, and then authenticate them.
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