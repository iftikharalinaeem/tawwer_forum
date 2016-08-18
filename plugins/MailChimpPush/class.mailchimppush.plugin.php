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
    'Description' => 'Updates MailChimp when users adjust their email address.',
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

    /**
     * Get our Provider record
     *
     * @return array
     */
    protected function provider() {
        if (!$this->provider) {
            $providerModel = new Gdn_AuthenticationProviderModel();
            $this->provider = $providerModel->getProviderByScheme(self::PROVIDER_ALIAS);

            if (is_array($this->provider)) {
                foreach (self::$settings as $setting)
                    $this->provider[$setting] = array_pop($this->getUserMeta(0, $setting));
            }
        }
        return $this->provider;
    }

    /**
     * Get an instance of MCAPI, the MailChimp wrapper class.
     *
     * @return MCAPI
     */
    protected function MCAPI() {
        if (!$this->MCAPI) {

            // This will ensure that the class is loaded until the addon autoloader is fixed properly.
            if (!class_exists('MCAPI')) {
                require_once(__DIR__.'/library/mailchimp/class.mailchimpwrapper.php');
            }

            $provider = $this->provider();
            $key = val('AssociationSecret', $provider);
            $this->MCAPI = new MailChimpWrapper($key);
        }

        return $this->MCAPI;
    }

    /**
     * After a user signs up for the forum, send his email to MailChimp.
     *
     * @param type $sender.
     * @return type.
     */
    public function userModel_afterSave_handler($sender) {
        $suppliedEmail = val('Email', $sender->EventArguments['Fields'], null);
        if (empty($suppliedEmail)) {
            return;
        }

        $originalEmail = val('Email', $sender->EventArguments['User'], null);

        $listID = val('ListID', $this->provider(), null);
        if (empty($originalEmail)) {
            // Post update to Chimp List
            $this->add($listID, $suppliedEmail, null, (array)$sender->EventArguments['User']);
        } elseif ($originalEmail != $suppliedEmail) {
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
    public function userModel_afterRegister_handler($Sender, $Args) {
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
     * Add an address to MailChimp.
     *
     * @param string $listID.
     * @param string $email.
     */
    public function add($listID, $email, $options = null) {
        if (!$listID) {
            return;
        }

        if (!$email) {
            return ['error' => 'no emails'];
        }

        // Configure subscription
        $defaults = array(
            'ConfirmJoin'     => val('ConfirmJoin', $this->provider(), false),
            'Format'          => 'html'
        );
        $options = (array)$options;
        $options = array_merge($defaults, $options);

        // Subscribe user to list
        if (!is_array($email)) {
            $email = array($email);
        }

        $emails = array();
        foreach ($email as $emailAddress) {
            $emails[] = array('EMAIL' => $emailAddress, 'EMAIL_TYPE' => $options['Format']);
        }

        // Send request
        return $this->MCAPI()->listBatchSubscribe($listID, $emails, $options['ConfirmJoin'], true);
    }

    /**
     * Try to update an existing address in MailChimp.
     *
     * @param string $defaultListID if the user doesn't exist in MailChimp db, add the user to this list.
     * @param string $email Old/current email address.
     * @param string $newEmail New email address.
     * @param array $options.
     *
     * @return null|string
     */
    public function update($defaultListID, $email, $newEmail, $options = null) {
        $lists = $this->MCAPI()->lists();
        $allLists = array_keys($lists);
        $updated = false;
        foreach ($allLists as $listID) {
            // Lookup member
            $memberInfo = $this->MCAPI()->listMemberInfo($listID, array($email));
            $memberInfo = $this->MCAPI()->toArray($memberInfo);

            if ($memberInfo['status'] === 'subscribed') {
                // Configure subscription
                $defaults = array(
                    'ConfirmJoin'     => false,
                    'Format'          => 'html'
                );
                $options = (array)$options;
                $options = array_merge($defaults, $options);

                // Update existing user
                $this->MCAPI()->listUpdateAddress(
                    $listID,
                    array(
                        'EMAIL'  => $email,
                        'NEW_EMAIL' => $newEmail,
                        'EMAIL_TYPE' => $options['Format']
                    )
                );

                $updated = true;
            }
        }

        // if the user was not on a list, add the user to the default list.
        if (!$updated) {
            $this->add($defaultListID, $newEmail, $options);
        }
    }

    /**
     * Config page in the dashboard.
     *
     * @param PluginController $sender
     */
    public function pluginController_mailChimp_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $this->dispatch($sender);
    }


    /**
     * Add mailchimp js to dashboard
     *
     * @param $sender
     * @param $args
     */
    public function base_render_before($sender, $args) {
        if ($sender->MasterView == 'admin') {
            $sender->addJsFile('mailchimp.js', 'plugins/MailChimpPush');
        }
    }

    /**
     * Settings controller for storing API key, creating settings page in dashboard.
     *
     * @param PluginController $sender.
     * @throws Gdn_UserException.
     */
    public function controller_index($sender) {
        $sender->title('MailChimp Settings');
        $sender->addSideMenu();
        $sender->Form = new Gdn_Form();
        $sender->Sync = new Gdn_Form();

        $sender->addDefinition('MailChimpUploadSuccessMessage', t('MailChimp will now process the list you have uploaded. Check your MailChimp Dashboard later.'));

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

            if ($modified) {
                $sender->informMessage(t('Changes saved'));
            }
        }

        $apiKey = val('AssociationSecret', $provider);
        if (!empty($apiKey)) {
            $ping = $this->MCAPI()->ping();
            if ($ping === true) {
                $sender->setData('Configured', true);
                $allLists = $this->MCAPI()->lists();
                $sender->setData('Lists', $allLists);
            } else {
                $sender->Form->addError('Bad API Key');
            }
        }

        $syncURL = gdn::request()->url('plugin/mailchimp/sync', true);
        $sender->Sync->addHidden('SyncURL', $syncURL);

        $trackBatchesURL = gdn::request()->url('plugin/mailchimp/trackbatches', true);
        $sender->Sync->addHidden('TrackBatchesURL', $trackBatchesURL);

        $sender->render('settings','','plugins/MailChimpPush');
    }


    /**
     * Send massive list to Mailchimp to synchronize emails in GDN_User.
     *
     * @param PluginController $sender.
     */
    public function controller_sync($sender) {
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
                $val = Gdn::request()->getValue($opt, null);
                if ((!isset($val) || $val == '') && in_array($opt, $requiredOpts)) {
                    throw new Exception(sprintf(t('%s is required.'), $opt), 400);
                }
                $options[$opt] = is_null($val) ? $default : $val;
            }
            extract($options);

            /* @var  $SyncConfirmJoin passed in $options array*/
            // Chunk size depends on whether we're sending confirmation emails
            $chunkSize = $SyncConfirmJoin ?  300 : 2000;

            $criteria = array();

            /* @var $SyncBanned passed in $options array */
            // Only if true do we care
            if (!$SyncBanned) {
                $criteria['Banned'] = 0;
            }

            /* @var $SyncDeleted passed in $options array */
            if (!$SyncDeleted) {
                $criteria['Deleted'] = 0;
            }

            /* @var $SyncUnconfirmed passed in $options array */
            // Only if supplied and false do we care
            if ($SyncUnconfirmed == false) {
                $criteria['Confirmed'] = 1;
            }

            $totalUsers = Gdn::userModel()->getCount($criteria);
            if ($totalUsers) {

                // Fetch users
                /* @var $Offset passed in $options array */
                $processUsers = Gdn::userModel()->getWhere($criteria, 'UserID', 'desc', $chunkSize, $Offset);

                // Extract email addresses
                $emails = array();
                while ($processUser = $processUsers->NextRow(DATASET_TYPE_ARRAY)) {
                    if (!empty($processUser['Email'])) {
                        $emails[] = $processUser['Email'];
                    }
                }

                // Subscribe users
                /* @var $SyncListID passed in $options array */
                $response = [];
                if (count($emails)) {
                    $response = $this->add($SyncListID, $emails, array('ConfirmJoin'  => (bool)$SyncConfirmJoin));
                }

                $response = $this->MCAPI()->toArray($response);

                $sender->setData('Status', val('status', $response, 'unknown'));
                $sender->setData('BatchID', val('id', $response));
                $sender->setData('NumberOfUsers', $totalUsers);
                $progress = floor(($Offset / $totalUsers) * 100);
                $sender->setData('Offset', ($Offset + $chunkSize));
                $sender->setData('ChunkSize', $chunkSize);
                $sender->setData('Progress', $progress);
            } else {
                throw new Exception('No users match criteria', 400);
            }

        } catch (Exception $ex) {
            $sender->setData('Error', $ex->getMessage());

            if ($ex->getCode() == 400) {
                $sender->setData('Fatal', true);
            }
        }

        $sender->render();
    }

    /**
     * Send massive list to Mailchimp to synchronize emails in GDN_User.
     *
     * @param PluginController $sender.
     */
    public function controller_trackbatches($sender) {
        $sender->deliveryMethod(DELIVERY_METHOD_JSON);
        $sender->deliveryType(DELIVERY_TYPE_DATA);
        $batchID = Gdn::request()->getValue('batchID');
        $response = $this->MCAPI()->getBatchStatus($batchID);
        $response = $response->getBody();
        $sender->setData('response', $response);
        $sender->render();
    }

}
