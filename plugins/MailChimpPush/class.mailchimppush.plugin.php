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
   'Version' => '2.0.3',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Tim Gunter',
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://about.me/timgunter',
   'SettingsUrl' => '/plugin/mailchimp'
);

class MailChimpPushPlugin extends Gdn_Plugin {

   protected $MCAPI = null;
   protected $provider = null;

   protected static $settings = array('ListID', 'ConfirmJoin');

   const PROVIDER_KEY = 'MailChimpAPI';
   const PROVIDER_ALIAS = 'mcapi';

   const MAILCHIMP_OK = "Everything's Chimpy!";

   /**
    * Get our Provider record
    *
    * @return array
    */
   protected function provider() {
      if (!$this->provider) {
         $ProviderModel = new Gdn_AuthenticationProviderModel();
         $this->provider = $ProviderModel->getProviderByScheme(self::PROVIDER_ALIAS);

         if (is_array($this->provider)) {
            foreach (self::$settings as $setting)
               $this->provider[$setting] = array_pop($this->getUserMeta(0, $setting));
         }
      }
      return $this->provider;
   }

   /**
    * Get an instance of MCAPI
    *
    * @return MCAPI
    */
   protected function MCAPI() {
      if (!$this->MCAPI) {
         $provider = $this->provider();
         $key = val("AssociationSecret", $provider);
         $this->MCAPI = new MCAPI($key);
      }

      return $this->MCAPI;
   }

   /**
    *
    * @param type $sender
    * @return type
    */
   public function userModel_AfterSave_Handler($sender) {
      $suppliedEmail = val('Email', $sender->EventArguments['Fields'], null);
      if (empty($suppliedEmail)) return;

      $originalEmail = val('Email', $sender->EventArguments['User'], null);

      $listID = val('ListID', $this->provider(), null);
      if (empty($originalEmail)) {
         // Post update to Chimp List
         $this->add($listID, $suppliedEmail, null, (array)$sender->EventArguments['User']);
      } else if ($originalEmail != $suppliedEmail) {
         // Post update to Chimp List
         $this->update($listID, $originalEmail, $suppliedEmail, null, (array)$sender->EventArguments['User']);
      }
   }

    /**
     * Add user after successfully filling a registration form.
     *
     * Note: The UserModel_AfterSave_Handler will NOT save users who register.
     * It will only save users if they are created from the dashboard, or their
     * information is changed. The former use case may have previously
     * functioned, but this looks like an update to core invalidated that. This
     * handler is put in place to catch users registering, then.
     *
     * @param UserModel $Sender The User Model.
     * @param Event $Args The arguments.
     */
    public function userModel_AfterRegister_Handler($Sender, $Args) {
        $isValidRegistration = $Args['Valid'];

        if ($isValidRegistration) {
            $user = $Args['RegisteringUser'];
            $listID = val('ListID', $this->provider(), null);
            $email = val('Email', $user, null);

            // Add the email to the given MailChimp list.
            $this->add($listID, $email, null, (array)$user);
        }
    }

   /**
    * Add an address to Mail Chimp
    *
    * @param string $listID
    * @param string $email
    * @param array $user
    */
   public function add($listID, $email, $options = null, $user = null) {
      if (!$listID)
         return;

      // Configure subscription
      $defaults = array(
         'ConfirmJoin'     => val('ConfirmJoin', $this->provider(), false),
         'Format'          => 'html'
      );
      $options = (array)$options;
      $options = array_merge($defaults, $options);

      // Subscribe user to list
      if (!is_array($email))
         $email = array($email);

      $emails = array();
      foreach ($email as $emailAddress)
         $emails[] = array('EMAIL' => $emailAddress, 'EMAIL_TYPE' => $options['Format']);

      // Send request
      return $this->MCAPI()->listBatchSubscribe($listID, $emails, $options['ConfirmJoin'], true);
   }

   /**
    * Try to update an existing address in Mail Chimp
    *
    * @param string $email Old/current email address
    * @param string $newEmail New email address
    * @param array $user
    */
   public function update($listID, $email, $newEmail, $options = null, $user = null) {
      if (!$listID)
         return;

      // Lookup member
      $memberInfo = $this->MCAPI()->listMemberInfo($listID, array($email));

      // Add member if they don't exist
      if (!$memberInfo)
         return $this->add($listID, $newEmail, $options, $user);

      // Configure subscription
      $defaults = array(
         'ConfirmJoin'     => false,
         'Format'          => 'html'
      );
      $options = (array)$options;
      $options = array_merge($defaults, $options);

      // Update existing user
      $confirmJoin = val('ConfirmJoin', $this->provider(), false);
      return $this->MCAPI()->listSubscribe($listID, $email, array(
         'EMAIL'  => $newEmail
      ), $options['Format'], $options['ConfirmJoin'], true);
   }

   /**
    * Config
    *
    * @param PluginController $sender
    */
   public function pluginController_MailChimp_Create($sender) {
      $sender->permission('Garden.Settings.Manage');
      $this->dispatch($sender);
   }

   public function controller_Index($sender) {
      $sender->title('MailChimp Settings');
      $sender->addSideMenu();
      $sender->Form = new Gdn_Form();
      $sender->Sync = new Gdn_Form();

      $sender->addJsFile('mailchimp.js', 'plugins/MailChimpPush');

      $provider = $this->provider();

      $apiKey = val('AssociationSecret', $provider);
      $sender->Form->setValue('ApiKey', $apiKey);

      // Get additional settings

      $settingValues = array();
      foreach (self::$settings as $setting) {
         $settingValues[$setting] = val($setting, $provider);
         $sender->Form->setValue($setting, $settingValues[$setting]);
      }
      extract($settingValues);

      // Prepare sync data

      $sender->setData('ConfirmEmail', c('Garden.Registration.ConfirmEmail', false));
      $sender->Sync->setData(array(
         'SyncBanned'      => false,
         'SyncDeleted'     => false,
         'SyncUnconfirmed' => false
      ));

      // Validate form
      if ($sender->Form->authenticatedPostBack()) {
         $modified = false;

         // Update API Key?

         $suppliedApiKey = $sender->Form->getvalue('ApiKey');
         if ($suppliedApiKey && $suppliedApiKey != $apiKey) {
            $modified = true;
            $ProviderModel = new Gdn_AuthenticationProviderModel();

            if (!$provider) {
               $ProviderModel->insert(array(
                  'AuthenticationKey'           => self::PROVIDER_KEY,
                  'AuthenticationSchemeAlias'   => self::PROVIDER_ALIAS,
                  'AssociationSecret'           => $suppliedApiKey
               ));

               $this->provider = null;
               $provider = $this->provider();
            } else {
               $provider['AssociationSecret'] = $suppliedApiKey;
               $ProviderModel->save($provider);
               $this->provider = $provider;
            }
         }

         // Update settings?

         foreach (self::$settings as $setting) {
            $suppliedSettingValue = $sender->Form->getValue($setting);
            if ($suppliedSettingValue != $settingValues[$setting]) {
               $modified = true;
               $this->setUserMeta(0, $setting, $suppliedSettingValue);
               $provider[$setting] = $suppliedSettingValue;
            }
         }

         if ($modified)
            $sender->informMessage(t("Changes saved"));
      }

      $apiKey = val('AssociationSecret', $provider);
      if (!empty($apiKey)) {
         $ping = $this->MCAPI()->ping();
         if ($ping == self::MAILCHIMP_OK) {
            $sender->setData('Configured', true);
            $listsResponse = $this->MCAPI()->lists();
            $lists = val('data', $listsResponse);
            $lists = Gdn_DataSet::Index($lists, 'id');
            $lists = array_column($lists, 'id', 'name');
            $sender->setData('Lists', $lists);
         } else {
            $sender->Form->addError("Bad API Key");
         }
      }

      $sender->render('settings','','plugins/MailChimpPush');
   }

   public function controller_Sync($sender) {
      $sender->deliveryMethod(DELIVERY_METHOD_JSON);
      $sender->deliveryType(DELIVERY_TYPE_DATA);

      try {

         $opts = array(
            'Offset'          => 0,
            'SyncListID'      => false,
            'SyncConfirmJoin' => 0,
            'SyncBanned'      => 0,
            'SyncDeleted'     => 0,
            'SyncUnconfirmed' => null
         );
         $requiredOpts = array('SyncListID', 'SyncBanned', 'SyncDeleted');

         $options = array();
         foreach ($opts as $opt => $default) {
            $val = Gdn::Request()->getValue($opt, null);
            if ((!isset($val) || $val == '') && in_array($opt, $requiredOpts))
               throw new Exception(sprintf(T("%s is required."), $opt),400);
            $options[$opt] = is_null($val) ? $default : $val;
         }
         extract($options);

         // Chunk size depends on whether we're sending confirmation emails
         $chunkSize = $syncConfirmJoin ? 25 : 200;

         $criteria = array();

         // Only if true do we care
         if (!$syncBanned)
            $criteria['Banned'] = 0;

         if (!$syncDeleted)
            $criteria['Deleted'] = 0;

         // Only if supplied and false do we care
         if ($syncUnconfirmed == false)
            $criteria['Confirmed'] = 1;

         $totalUsers = Gdn::UserModel()->getCount($criteria);
         if ($totalUsers) {

            // Fetch users
            $processUsers = Gdn::UserModel()->getWhere($criteria, 'UserID', 'desc', $chunkSize, $offset);
            $newOffset = $offset+$processUsers->NumRows();

            // Extract email addresses
            $emails = array();
            while ($processUser = $processUsers->NextRow(DATASET_TYPE_ARRAY)) {
               if (!empty($processUser['Email']))
                  $emails[] = $processUser['Email'];
            }

            // Subscribe users
            $start = microtime(true);
            $response = $this->add($syncListID, $emails, array(
               'ConfirmJoin'  => (bool)$syncConfirmJoin
            ));
            $elapsed = microtime(true) - $start;

            $SPU = $elapsed / sizeof($emails);
            $ETA = ceil(($totalUsers - $newOffset) * $SPU);
            $ETAMin = ceil($ETA / 60);
            $sender->setData('ETA', $ETA);
            $sender->setData('ETAMinutes', $ETAMin);

            $progress = round(($newOffset / $totalUsers) * 100, 2);
            $sender->setData('Progress', $progress);
            $sender->setData('Offset', $newOffset);
            $sender->setData('Count', sizeof($emails));
         } else {
            throw new Exception('No users match criteria', 400);
         }

      } catch (Exception $ex) {
         $sender->setData('Error', $ex->getMessage());

         if ($ex->getCode() == 400)
            $sender->setData('Fatal', true);
      }

      $sender->render();
   }

}
