<?php if (!defined('APPLICATION')) exit();

/**
 * MailChimpPush Plugin
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Misc
 */

// Define the plugin:
$PluginInfo['MailChimpPush'] = array(
   'Name' => 'MailChimp Push',
   'Description' => "Updates MailChimp when users adjust their email address.",
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Tim Gunter',
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://about.me/timgunter',
   'SettingsUrl' => '/plugin/mailchimppush'
);

class MailChimpPushPlugin extends Gdn_Plugin {
   
   protected $MCAPI = NULL;
   protected $Provider = NULL;
   
   protected static $Settings = array('ListID', 'ConfirmJoin');
   
   const PROVIDER_KEY = 'MailChimpAPI';
   const PROVIDER_ALIAS = 'mcapi';
   
   const MAILCHIMP_OK = "Everything's Chimpy!";
   
   /**
    * Get our Provider record
    * 
    * @return array
    */
   protected function Provider() {
      if (!$this->Provider) {
         $ProviderModel = new Gdn_AuthenticationProviderModel();
         $this->Provider = $ProviderModel->GetProviderByScheme(self::PROVIDER_ALIAS);
         
         if (is_array($this->Provider)) {
            foreach (self::$Settings as $Setting)
               $this->Provider[$Setting] = array_pop($this->GetUserMeta(0, $Setting));
         }
      }
      return $this->Provider;
   }
   
   /**
    * Get an instance of MCAPI
    * 
    * @return MCAPI
    */
   protected function MCAPI() {
      if (!$this->MCAPI) {
         $Provider = $this->Provider();
         $Key = GetValue("AssociationSecret", $Provider);
         $this->MCAPI = new MCAPI($Key);
      }
      
      return $this->MCAPI;
   }
   
   /**
    * 
    * @param type $Sender
    * @return type
    */
   public function UserModel_AfterSave_Handler($Sender) {
      $SuppliedEmail = GetValue('Email', $Sender->EventArguments['Fields'], NULL);
      if (empty($SuppliedEmail)) return;
      
      $OriginalEmail = GetValue('Email', $Sender->EventArguments['User'], NULL);
      
      if (empty($OriginalEmail)) {
         // Post update to Chimp List
         $this->Add($SuppliedEmail, (array)$Sender->EventArguments['User']);
      } else if ($OriginalEmail != $SuppliedEmail) {
         // Post update to Chimp List
         $this->Update($OriginalEmail, $SuppliedEmail, (array)$Sender->EventArguments['User']);
      }
   }
   
   /**
    * Add an address to Mail Chimp
    * @param string $Email
    * @param array $User
    */
   public function Add($Email, $User) {
      $ListID = GetValue('ListID', $this->Provider(), NULL);
      
      if (!$ListID)
         return;
      
      // Subscribe user to list
      $ConfirmJoin = GetValue('ConfirmJoin', $this->Provider(), FALSE);
      $this->MCAPI()->listSubscribe($ListID, $Email, array(
         'EMAIL'  => $Email
      ), 'html', $ConfirmJoin, TRUE);
   }
   
   /**
    * Try to update an existing address in Mail Chimp
    * 
    * @param string $Email
    * @param string $NewEmail
    * @param array $User
    */
   public function Update($Email, $NewEmail, $User) {
      $ListID = GetValue('ListID', $this->Provider(), NULL);
      
      if (!$ListID)
         return;
      
      // Lookup member
      $MemberInfo = $this->MCAPI()->listMemberInfo($ListID, array($Email));
      
      // Add member if they don't exist
      if (!$MemberInfo)
         return $this->Add($NewEmail, $User);
      
      // Update existing user
      $ConfirmJoin = GetValue('ConfirmJoin', $this->Provider(), FALSE);
      $this->MCAPI()->listSubscribe($ListID, $Email, array(
         'EMAIL'  => $NewEmail
      ), 'html', FALSE, TRUE);
   }
   
   /**
    * Config
    * 
    * @param PluginController $Sender
    */
   public function PluginController_MailChimpPush_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('MailChimp Settings');
      $Sender->AddSideMenu();
      $Sender->Form = new Gdn_Form();
      
      $Provider = $this->Provider();
      
      $ApiKey = GetValue('AssociationSecret', $Provider);
      $Sender->Form->SetValue('ApiKey', $ApiKey);
      
      // Get additional settings
      
      $SettingValues = array();
      foreach (self::$Settings as $Setting) {
         $SettingValues[$Setting] = GetValue($Setting, $Provider);
         $Sender->Form->SetValue($Setting, $SettingValues[$Setting]);
      }
      extract($SettingValues);
      
      // Validate form
      if ($Sender->Form->IsPostBack()) {
         
         // Update API Key?
         
         $SuppliedApiKey = $Sender->Form->GetValue('ApiKey');
         if ($SuppliedApiKey && $SuppliedApiKey != $ApiKey) {
            $ProviderModel = new Gdn_AuthenticationProviderModel();
            
            if (!$Provider) {
               $ProviderModel->Insert(array(
                  'AuthenticationKey'           => self::PROVIDER_KEY,
                  'AuthenticationSchemeAlias'   => self::PROVIDER_ALIAS,
                  'AssociationSecret'           => $SuppliedApiKey
               ));
               
               unset($this->Provider);
               $Provider = $this->Provider();
            } else {
               $Provider['AssociationSecret'] = $SuppliedApiKey;
               $ProviderModel->Save($Provider);
               $this->Provider = $Provider;
            }
         }
         
         // Update settings?
         
         foreach (self::$Settings as $Setting) {
            $SuppliedSettingValue = $Sender->Form->GetValue($Setting);
            if ($SuppliedSettingValue != $SettingValues[$Setting]) {
               $this->SetUserMeta(0, $Setting, $SuppliedSettingValue);
               $Provider[$Setting] = $SuppliedSettingValue;
            }
         }
         
         $Sender->InformMessage(T("Changes saved"));
      }
      
      $ApiKey = GetValue('AssociationSecret', $Provider);
      if (!empty($ApiKey)) {
         $Ping = $this->MCAPI()->ping();
         if ($Ping == self::MAILCHIMP_OK) {
            $Sender->SetData('Configured', TRUE);
            $ListsResponse = $this->MCAPI()->lists();
            $Lists = GetValue('data', $ListsResponse);
            $Lists = Gdn_DataSet::Index($Lists, 'id');
            $Lists = ConsolidateArrayValuesByKey($Lists, 'id', 'name');
            $Sender->SetData('Lists', $Lists);
         } else {
            $Sender->Form->AddError("Bad API Key");
         }
      }
      
      $Sender->Render('settings','','plugins/MailChimpPush');
   }
   
   
}