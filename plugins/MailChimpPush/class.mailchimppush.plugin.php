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
   'Version' => '2.0',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Tim Gunter',
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://about.me/timgunter',
   'SettingsUrl' => '/plugin/mailchimppush'
);

class MailChimpPushPlugin extends Gdn_Plugin {
   
   protected $MCAPI = null;
   protected $Provider = null;
   
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
      $SuppliedEmail = GetValue('Email', $Sender->EventArguments['Fields'], null);
      if (empty($SuppliedEmail)) return;
      
      $OriginalEmail = GetValue('Email', $Sender->EventArguments['User'], null);
      
      $ListID = GetValue('ListID', $this->Provider(), null);
      if (empty($OriginalEmail)) {
         // Post update to Chimp List
         $this->Add($ListID, $SuppliedEmail, (array)$Sender->EventArguments['User']);
      } else if ($OriginalEmail != $SuppliedEmail) {
         // Post update to Chimp List
         $this->Update($ListID, $OriginalEmail, $SuppliedEmail, (array)$Sender->EventArguments['User']);
      }
   }
   
   /**
    * Add an address to Mail Chimp
    * 
    * @param string $ListID
    * @param string $Email
    * @param array $User
    */
   public function Add($ListID, $Email, $Options = null, $User = null) {
      if (!$ListID)
         return;
      
      // Configure subscription
      $Defaults = array(
         'ConfirmJoin'     => GetValue('ConfirmJoin', $this->Provider(), false),
         'Format'          => 'html'
      );
      $Options = (array)$Options;
      $Options = array_merge($Defaults, $Options);
      
      // Subscribe user to list
      if (!is_array($Email))
         $Email = array($Email);
      
      $Emails = array();
      foreach ($Email as $EmailAddress)
         $Emails[] = array('EMAIL' => $EmailAddress, 'EMAIL_TYPE' => $Options['Format']);
      
      // Send request
      return $this->MCAPI()->listBatchSubscribe($ListID, $Emails, $ConfirmJoin, true);
   }
   
   /**
    * Try to update an existing address in Mail Chimp
    * 
    * @param string $Email Old/current email address
    * @param string $NewEmail New email address
    * @param array $User
    */
   public function Update($ListID, $Email, $NewEmail, $Options = null, $User = null) {
      if (!$ListID)
         return;
      
      // Lookup member
      $MemberInfo = $this->MCAPI()->listMemberInfo($ListID, array($Email));
      
      // Add member if they don't exist
      if (!$MemberInfo)
         return $this->Add($ListID, $NewEmail, $Options, $User);
      
      // Configure subscription
      $Defaults = array(
         'ConfirmJoin'     => false,
         'Format'          => 'html'
      );
      $Options = (array)$Options;
      $Options = array_merge($Defaults, $Options);
      
      // Update existing user
      $ConfirmJoin = GetValue('ConfirmJoin', $this->Provider(), false);
      return $this->MCAPI()->listSubscribe($ListID, $Email, array(
         'EMAIL'  => $NewEmail
      ), $Options['Format'], $Options['ConfirmJoin'], true);
   }
   
   /**
    * Config
    * 
    * @param PluginController $Sender
    */
   public function PluginController_MailChimp_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $this->Dispatch($Sender);
   }
   
   public function Controller_Index($Sender) {
      $Sender->Title('MailChimp Settings');
      $Sender->AddSideMenu();
      $Sender->Form = new Gdn_Form();
      
      $Sender->AddJsFile('mailchimp.js', 'plugins/MailChimpPush');
      
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
      
      // Get data
      
      $Sender->SetData('ConfirmEmail', C('Garden.Registration.ConfirmEmail', false));
      $Sender->Form->SetData(array(
         'SyncBanned'      => false,
         'SyncDeleted'     => false,
         'SyncUnconfirmed' => false
      ));
      
      // Validate form
      if ($Sender->Form->IsPostBack()) {
         $Modified = false;
         
         // Update API Key?
         
         $SuppliedApiKey = $Sender->Form->GetValue('ApiKey');
         if ($SuppliedApiKey && $SuppliedApiKey != $ApiKey) {
            $Modified = true;
            $ProviderModel = new Gdn_AuthenticationProviderModel();
            
            if (!$Provider) {
               $ProviderModel->Insert(array(
                  'AuthenticationKey'           => self::PROVIDER_KEY,
                  'AuthenticationSchemeAlias'   => self::PROVIDER_ALIAS,
                  'AssociationSecret'           => $SuppliedApiKey
               ));
               
               $this->Provider = null;
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
               $Modified = true;
               $this->SetUserMeta(0, $Setting, $SuppliedSettingValue);
               $Provider[$Setting] = $SuppliedSettingValue;
            }
         }
         
         if ($Modified)
            $Sender->InformMessage(T("Changes saved"));
      }
      
      $ApiKey = GetValue('AssociationSecret', $Provider);
      if (!empty($ApiKey)) {
         $Ping = $this->MCAPI()->ping();
         if ($Ping == self::MAILCHIMP_OK) {
            $Sender->SetData('Configured', true);
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
   
   public function Controller_Sync($Sender) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);
      
      $ChunkSize = 25;
      try {
         
         $Opts = array(
            'Offset'          => 0, 
            'SyncListID'      => false,
            'SyncConfirmJoin' => false, 
            'SyncBanned'      => false,
            'SyncDeleted'     => false,
            'SyncUnconfirmed' => null
         );
         $RequiredOpts = array('SyncListID', 'SyncBanned', 'SyncDeleted');
         
         $Options = array();
         foreach ($Opts as $Opt => $Default) {
            $Val = Gdn::Request()->GetValue($Opt, null);
            if ((!isset($Val) || $Val == '') && in_array($Opt, $RequiredOpts))
               throw new Exception(sprintf(T("%s is required."), $Opt),400);
            $Options[$Opt] = is_null($Val) ? $Default : $Val;
         }
         
         extract($Options);
         
         $Criteria = array();
         
         // Only if true do we care
         if (!$SyncBanned)
            $Criteria['Banned'] = 0;
         
         if (!$SyncDeleted)
            $Criteria['Deleted'] = 0;
         
         // Only if supplied and false do we care
         if ($SyncUnconfirmed == false)
            $Criteria['Confirmed'] = 1;
         
         $TotalUsers = Gdn::UserModel()->GetCount($Criteria);
         if ($TotalUsers) {
            
            // Fetch users
            $ProcessUsers = Gdn::UserModel()->GetWhere($Criteria, 'UserID', 'desc', $ChunkSize, $Offset);
            $NewOffset = $Offset+$ProcessUsers->NumRows();
            
            // Extract email addresses
            $Emails = array();
            while ($ProcessUser = $ProcessUsers->NextRow(DATASET_TYPE_ARRAY)) {
               if (!empty($ProcessUser['Email']))
                  $Emails[] = $ProcessUser['Email'];
            }
            
            // Subscribe users
            $Start = microtime(true);
            $Response = $this->Add($SyncListID, $Emails, array(
               'ConfirmJoin'  => $SyncConfirmJoin
            ));
            $Elapsed = microtime(true) - $Start;
            
            $SPU = $Elapsed / sizeof($Emails);
            $ETA = ceil(($TotalUsers - $NewOffset) * $SPU);
            $ETAMin = ceil($ETA / 60);
            $Sender->SetData('ETA', $ETA);
            $Sender->SetData('ETAMinutes', $ETAMin);
            
            $Progress = round(($NewOffset / $TotalUsers) * 100, 2);
            $Sender->SetData('Progress', $Progress);
            $Sender->SetData('Offset', $NewOffset);
            $Sender->SetData('Count', sizeof($Emails));
         } else {
            throw new Exception('No users match criteria', 400);
         }
         
      } catch (Exception $Ex) {
         $Sender->SetData('Error', $Ex->getMessage());
         
         if ($Ex->getCode() == 400)
            $Sender->SetData('Fatal', true);
      }
      
      $Sender->Render();
   }
   
}