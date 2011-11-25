<?php if (!defined('APPLICATION')) exit();

/**
 * Custom Domain Plugin
 * 
 * This plugin allows VanillaForums.com customers to enable access to their site
 * from a custom domain pointing at our servers.
 * 
 * Changes: 
 *  2.0     Compatibility with Infrastructure
 *  2.1     Improvement to UI
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 */

$PluginInfo['CustomDomain'] = array(
   'Name' => 'Custom Domain',
   'Description' => 'Make your Vanilla Forum accessible from a different domain.',
   'Version' => '2.0',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class CustomDomainPlugin extends Gdn_Plugin {
   
   public function __construct() {}

   /**
    * Add Custom Domain link to panel
    * 
    * @param Gdn_Controller $Sender
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Appearance', 'Custom Domain', 'settings/customdomain', 'Garden.Settings.Manage');
   }
   
   /**
    * Virtual Controller Dispatcher
    * 
    * @param Gdn_Controller $Sender
    */
   public function SettingsController_CustomDomain_Create($Sender, $EventArguments = array()) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->AddSideMenu('settings/customdomain');
      $Sender->Form = new Gdn_Form();
      
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }
   
   /**
    * Creates a "Custom Domain" upgrade offering screen where users can purchase
    * & implement a custom domain.
    * 
    * @param Gdn_Controller $Sender 
    */
   public function Controller_Index($Sender) {
      $Sender->Title('Custom Domain Name');
      $Sender->AddCssFile('customdomain.css', 'plugins/CustomDomain');
      
      $Site = Infrastructure::Site();
      $Sender->SetData('Site', $Site);
      $Sender->SetData('ForumName', Infrastructure::Client());
      $ExistingDomain = GetValue('Domain', $Site, FALSE);
      
      $ClusterName = Infrastructure::Cluster();
      $ClusterLoadbalancer = Infrastructure::Server('www');
      $ClusterLoadbalancerAddress = gethostbyname($ClusterLoadbalancer);
      $Sender->SetData('ClusterName', $ClusterName);
      $Sender->SetData('ClusterLoadbalancer', $ClusterLoadbalancer);
      $Sender->SetData('ClusterLoadbalancerAddress', $ClusterLoadbalancerAddress);
      
      $Sender->SetData('Steps', TRUE);
      $Sender->SetData('Attempt', $Sender->Form->IsPostBack());
      
      if ($Sender->Form->IsPostBack()) {
         try {
            $RequestedDomain = $Sender->Form->GetValue('CustomDomain');
            
            $this->CheckAvailable($RequestedDomain);
            $this->CheckConfiguration($RequestedDomain);
            $this->CustomDomain($RequestedDomain);
            
         } catch (Exception $Ex) {
            $Sender->SetData('Failed', TRUE);
            $Sender->SetData('ErrorType', get_class($Ex));
            $Sender->SetData('ErrorText', $Ex->getMessage());
            $Sender->Form->AddError($Ex);
         }
      }
      
      $Sender->Render('customdomain','','plugins/CustomDomain');
   }
   
   private function CheckAvailable($Domain) {
      
      $IllegalDomains = array(
         'vanillaforums.com',
         'vanilladev.com'
      );
      foreach ($IllegalDomains as $IllegalDomain) {
         $RegexIllegalDomain = str_replace('.', '\.', $IllegalDomain);
         if (preg_match("/(?:.*\.)?{$RegexIllegalDomain}/i", $Domain))
            throw new IllegalDomainException("The domain you requested is prohibited");
      }
      
      $DomainAvailableQuery = Communication::DataServerRequest('api/forum/domainavailable')
         ->AutoToken()
         ->Parameter('Domain', $Domain)
         ->Send();
      
      if (!Communication::ResponseClass($DomainAvailableQuery, '2xx'))
         throw new CommunicationErrorException("Problem communicating with data server. Please contact support.", $DomainAvailableQuery);
      
      if (!GetValueR('Response.Available', $DomainAvailableQuery, FALSE))
         throw new UnavailableDomainException("The domain you requested is currently in use");
      
      return TRUE;
   }
   
   private function CheckConfiguration($Domain) {
      $Loadbalancer = Infrastructure::Server('www');
      $LoadbalancerAddress = gethostbyname($Loadbalancer);
      
      $DomainAddress = gethostbyname($Domain);
      $DomainType = explode('.', $Domain);
      $DomainType = sizeof($DomainType) > 2 ? 'subdomain' : 'domain';
      
      if ($DomainAddress != $LoadbalancerAddress)
         throw new AddressMismatchDomainException("That {$DomainType} does not resolve to the correct IP");
      
      $ExpectedRecordType = $DomainType == 'domain' ? 'a' : 'cname';
      
      if ($ExpectedRecordType == 'cname') {
         $LookupHostname = dns_get_record($Domain, DNS_CNAME);
         $Matched = FALSE;
         foreach ($LookupHostname as $DnsRecord) {
            $Target = GetValue('target', $DnsRecord);
            if (strtolower($Target) == Infrastructure::Client()) $Matched = TRUE;
         }
         
         if (!$Matched)
            throw new RecordConfigurationException("No valid CNAME exists for this {$DomainType}");
      }
      
      if ($ExpectedRecordType == 'a') {
         $LookupHostname = dns_get_record($Domain, DNS_A);
         $Matched = FALSE;
         foreach ($LookupHostname as $DnsRecord) {
            $Target = GetValue('target', $DnsRecord);
            if ($Target == $LoadbalancerAddress) $Matched = TRUE;
         }
         
         if (!$Matched)
            throw new RecordConfigurationException("No valid A Record exists for this {$DomainType}");
      }
      
      return TRUE;
   }
   
   public function Controller_Remove($Sender) {
      
      // Do removal
      Redirect(Url('settings/customdomain'));
   }
   
   public function CustomDomain($Domain) {
      $SiteID = C('VanillaForums.SiteID', 0);
      $SetDomainQuery = Communication::DataServerRequest('api/forum/setdomain')
         ->AutoToken()
         ->Parameter('SiteID', $SiteID)
         ->Parameter('Domain', $Domain)
         ->Send();
      
      if (!Communication::ResponseClass($SetDomainQuery, '2xx'))
         throw new Exception("Failed to set custom domain. Please contact support.");
      
      return TRUE;
   }
   
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
   
   /**
    * No setup required.
    */
   public function Setup() {}
}

class IllegalDomainException extends Exception {}
class UnavailableDomainException extends Exception {}
class AddressMismatchDomainException extends Exception {}
class RecordConfigurationException extends Exception {}