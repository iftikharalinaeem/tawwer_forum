<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['CASAuthentication'] = array(
   'Name' => 'CAS Authentication for CRN',
   'Description' => 'Allows Vanilla to authenticate against a <a href="http://en.wikipedia.org/wiki/Central_Authentication_Service">CAS</a> authentication server.',
   'Version' => '1.0b',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'MobileFriendly' => TRUE,
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'SettingsUrl' => '/dashboard/settings/cas',
   'SettingsPermission' => 'Garden.Settings.Manage',
);

class CASAuthenticationPlugin extends Gdn_Plugin {
   /// PROPERTIES ///
   
   /// METHODS ///
   
   public function InitializeCAS() {
      require_once dirname(__FILE__).'/CAS.php';
      
      $Host = C('Plugins.CASAuthentication.Host');
      $Port = (int)C('Plugins.CASAuthentication.Port', 443);
      $Context = C('Plugins.CASAuthentication.Context', '/cas');

      // Initialize phpCAS.
      phpCAS::client(CAS_VERSION_1_0, $Host, $Port, $Context);
      phpCAS::setNoCasServerValidation();
      phpCAS::setNoClearTicketsFromUrl(FALSE);
      
      $Url = Url('/entry/cas', TRUE);
      if (Gdn::Request()->Get('Target'))
         $Url .= '?Target='.urlencode(Gdn::Request()->Get('Target'));
      phpCAS::setFixedServiceURL($Url);
   }
   
   public function Setup() {
      SaveToConfig('Garden.SignIn.Popup', FALSE);
   }
   
   /// EVENT HANDLERS ///
   
   /**
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function Base_ConnectData_Handler($Sender, $Args) {
      if (GetValue(0, $Args) != 'cas')
         return;
      
      $User = Gdn::Session()->Stash('CASUser');
      if (!$User) {
         $Url = Url('/entry/cas');
         $Message = "There was an error retrieving your user data. Click <a href='$Url'>here</a> to try again.";
         throw new Gdn_UserException($Message);
      }
      
      // Make sure there is a user.

      $Form = $Sender->Form;
      $Form->SetFormValue('UniqueID', $User['UniqueID']);
      $Form->SetFormValue('Provider', 'cas');
      $Form->SetFormValue('ProviderName', 'CRN');
      $Form->SetFormValue('Name', $User['Name']);
      $Form->SetFormValue('FullName', $User['FirstName'].' '.$User['LastName']);
      $Form->SetFormValue('Email', $User['Email']);
      
      SaveToConfig(array(
          'Garden.User.ValidationRegex' => UserModel::USERNAME_REGEX_MIN,
          'Garden.User.ValidationLength' => '{3,50}',
          'Garden.Registration.NameUnique' => FALSE,
          'Garden.Registration.AutoConnect' => TRUE
      ), '', FALSE);
      
      // Save some original data in the attributes of the connection for later API calls.
      $Attributes = array(
          'FirstName' => $User['FirstName'],
          'LastName' => $User['LastName']
      );
      $Form->SetFormValue('Attributes', $Attributes);
      
      $Sender->SetData('Verified', TRUE);
   }
   
   /**
    * @param EntryController $Sender 
    */
   public function EntryController_CAS_Create($Sender) {
      $this->InitializeCAS();
      
      // force CAS authentication
      try {
         unset($_GET['rm']);
         phpCAS::forceAuthentication();
      } catch (Exception $Ex) {
         decho($Ex);
         die();
      }

      $Email = phpCAS::getUser();
      if (!$Email) {
         die('Failed');
      } else {
         // We now have a user so we need to get some info.
         $Url = sprintf(C('Plugins.CASAuthentication.ProfileUrl', 'http://www.crn.com/jive/util/get_user_profile.htm?email=%s'), urlencode($Email));
         $Data = file_get_contents($Url);

         $Xml = (array)simplexml_load_string($Data);

         $User = ArrayTranslate($Xml, array('email' => 'Email', 'nickname' => 'Name', 'firstName' => 'FirstName', 'lastName' => 'LastName'));
         $User['UniqueID'] = $User['Email'];
         Gdn::Session()->Stash('CASUser', $User);

         // Now that we have the user we can redirect.
         $Get = $Sender->Request->Get();
         unset($Get['ticket']);
         $Url = '/entry/connect/cas?'.http_build_query($Get);
         Redirect($Url);
      }
   }
   
   public function EntryController_Register_Handler($Sender) {
      $Url = "http://www.crn.com/register.htm";
      Redirect($Url);
   }
   
   /**
    * @param EntryController $Sender 
    */
   public function EntryController_SignIn_Handler($Sender) {
      if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
         $Get = $Sender->Request->Get();
//         $Get['rm'] = 'true';
         $Url = '/entry/cas?'.http_build_query($Get);
         Redirect($Url);
      }
   }
   
   public function EntryController_SignOut_Handler($Sender) {
      $this->InitializeCAS();
      phpCAS::logout();
   }
   
   /**
    *
    * @param Gdn_Controller $Sender 
    */
   public function SettingsController_CAS_Create($Sender) {
      $Cf = new ConfigurationModule($Sender);
      $Cf->Initialize(array(
          'Plugins.CASAuthentication.Host',
          'Plugins.CASAuthentication.Context' => array('Control' => 'TextBox', 'Default' => '/cas'),
          'Plugins.CASAuthentication.Port' => array('Control' => 'TextBox', 'Default' => 443),
          'Plugins.CASAuthentication.ProfileUrl' => array('Control' => 'TextBox', 'Default' => 'http://www.crn.com/jive/util/get_user_profile.htm?email=%s')
      ));
      
      $Sender->AddSideMenu();
      $Sender->Title('CAS Settings');
      $Cf->RenderAll();
   }
   
}