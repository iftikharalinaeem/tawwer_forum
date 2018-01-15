<?php
/**
 * TODO:
 * Admin option to allow users it hide the module
 * User Meta table to store if they are hidden or not
 */

// Changelog
// 1.3.1 ??
// 1.3.2 ??
// 1.3.3 ??
// 1.3.4 ??
// 1.3.5 ??
// 1.4   Added ability to target only lists, made pinger work on all pages, replace dash menu item w/settings button, adds docs -Lincoln
// 1.5   Remove users from the list when they log out explicitly
// 1.5.1 Add 'Invisible' class to invisibles that are shown for admins

class WhosOnlinePlugin extends Gdn_Plugin {
    /**
     * Settings page.
     *
     * @param PluginController $sender Sending controller instance.
     */
    public function pluginController_whosOnline_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->addSideMenu('plugin/whosonline');
        $sender->setData('Title', t('Who&rsquo;s Online Settings'));

        $config = new ConfigurationModule($sender);
        $config->initialize([
            'WhosOnline.Location.Show' => [
                'Control' => 'RadioList',
                'Description' => 'This setting determins where the list of online users is displayed.',
                'Items' => [
                    'every' => 'Every page',
                    'discussion' => 'All discussion pages',
                    'discussionsonly' => 'Only discussions and categories list',
                    'custom' => 'Use your custom theme'
                ],
                'Default' => 'every'
            ],
            'WhosOnline.Hide' => [
                'Control' => 'CheckBox',
                'LabelCode' => 'Hide the who\'s online module for guests.'
            ],
            'WhosOnline.DisplayStyle' => [
                'Control' => 'RadioList',
                'Items' => [
                    'list' => 'List',
                    'pictures' => 'Pictures'
                ],
                'Default' => 'list'
            ]
        ]);

        $config->renderAll();
    }

    /**
     * Page for Javascript to ping to signal user is still online.
     *
     * @param PluginController $sender Sending controller instance.
     */
    public function pluginController_imOnline_create($sender) {
        $session = Gdn::session();
        $userMetaData = $this->getUserMeta($session->UserID, '%');

        // Render new block and replace whole thing opposed to just the data
        include_once(PATH_PLUGINS.DS.'WhosOnline'.DS.'class.whosonlinemodule.php');

        $whosOnlineModule = new whosOnlineModule($sender);
        $whosOnlineModule->getData(valr('Plugin.WhosOnline.Invisible', $userMetaData));
        echo $whosOnlineModule->toString();
    }

    /**
     * Add module to specified pages and include Javascript pinger.
     *
     * @param Gdn_Controller $sender Sending controller instance.
     */
    public function base_render_before($sender) {
        $configItem = c('WhosOnline.Location.Show', 'every');
        $controller = $sender->ControllerName;
        $session = Gdn::session();

        // Check if it's visible to users
        if (c('WhosOnline.Hide', true) && !$session->isValid()) {
            return;
        }

        // Is this a page for including the module?
        switch ($configItem) {
            case 'custom':
                return;
            case 'every':
                $showOnController = [
                    'discussioncontroller',
                    'categoriescontroller',
                    'discussionscontroller',
                    'profilecontroller',
                    'activitycontroller'
                ];
                break;
            case 'discussionsonly':
                $showOnController = [
                    'discussionscontroller',
                    'categoriescontroller'
                ];
                break;
            case 'discussion':
            default:
                $showOnController = [
                    'discussioncontroller',
                    'discussionscontroller',
                    'categoriescontroller'
                ];
                break;
        }

        // Include the module
        if (inArrayI($controller, $showOnController)) {
            $sender->addModule('WhosOnlineModule');
        }

        // Ping the server when still online
        $sender->addJsFile('whosonline.js', 'plugins/WhosOnline');
        $sender->addCssFile('whosonline.css', 'plugins/WhosOnline');
        $frequency = c('WhosOnline.Frequency', 60);
        if (!is_numeric($frequency)) {
            $frequency = 60;
        }
        $sender->addDefinition('WhosOnlineFrequency', $frequency);

    }

    /**
     *
     *
     * @param EntryController $sender Sending controller instance.
     */
    public function entryController_signOut_handler($sender) {
        $user = $sender->EventArguments['SignoutUser'];
        $userID = getValue('UserID', $user, false);
        if ($userID === false) {
            return;
        }

        Gdn::sql()->delete('Whosonline', [
            'UserID' => getValue('UserID', $user)
        ]);
    }

    /**
     * Add privacy settings to profile menu.
     *
     * @param ProfileController $sender Sending controller instance.
     */
    public function profileController_afterAddSideMenu_handler($sender) {
        $sideMenu = $sender->EventArguments['SideMenu'];
        $session = Gdn::session();
        $viewingUserID = $session->UserID;

        if ($sender->User->UserID == $viewingUserID) {
            $sideMenu->addLink('Options', t('Privacy Settings'), '/profile/whosonline', false, ['class' => 'Popup']);
        }
    }

    /**
     * Let users modify their privacy settings.
     *
     * @param ProfileController $sender Sending controller instance.
     */
    public function profileController_whosonline_create($sender) {
        $sender->permission('Garden.SignIn.Allow');

        $session = Gdn::session();
        $userID = $session->isValid() ? $session->UserID : 0;
        $sender->getUserInfo();

        // Get the data
        $userMetaData = $this->getUserMeta($userID, '%');
        $configArray = [
            'Plugin.WhosOnline.Invisible' => null
        ];

        if ($sender->Form->authenticatedPostBack() === false) {
            // Convert to using arrays if more options are added.
            $configArray = array_merge($configArray, $userMetaData);
            $sender->Form->setData($configArray);
        } else {
            $values = $sender->Form->formValues();
            $frmValues = array_intersect_key($values, $configArray);

            foreach ($frmValues as $metaKey => $metaValue) {
                $this->setUserMeta($userID, $this->trimMetaKey($metaKey), $metaValue);
            }

            $sender->StatusMessage = t("Your changes have been saved.");
        }

        $sender->render($this->getView('settings.php'));
    }

    /**
     *
     *
     * @param Gdn_Statistics $sender Sending pluggable instance.
     */
    public function gdn_statistics_tick_handler($sender) {
        if (!Gdn::session()->isValid()) {
            $this->incrementGuest();
        }
    }

    /**
     * Called on plugin activation
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Called on utility/update
     */
    public function structure() {
        $structure = Gdn::structure();
        $structure->table('Whosonline')
            ->column('UserID', 'int(11)', false, 'primary')
            ->column('Timestamp', 'datetime')
            ->column('Invisible', 'int(1)', 0)
            ->set(false, false);

    }

    /**
     *
     *
     * @return int|mixed
     */
    public static function guestCount() {
        if (!Gdn::cache()->activeEnabled()) {
            return 0;
        }

        try {
            $names = ['__vnOz0', '__vnOz1'];

            $time = time();
            list($expire0, $expire1, $active) = self::expiries($time);

            // Get bot keys from the cache.
            $cache = Gdn::cache()->get($names);

            $debug = [
                'Cache' => $cache,
                'Active' => $active
            ];
            Gdn::controller()->setData('GuestCountCache', $debug);

            if (isset($cache[$names[$active]])) {
                return $cache[$names[$active]];
            } elseif (is_array($cache) && count($cache) > 0) {
                // Maybe the key expired, but the other key is still there.
                return array_pop($cache);
            }
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

    /**
     *
     *
     * @param $time
     * @return array
     */
    public static function expiries($time) {
        $timespan = 600; // 10 mins.

        $expiry0 = $time - $time % $timespan + $timespan;

        $expiry1 = $expiry0 - $timespan / 2;
        if ($expiry1 <= $time) {
            $expiry1 = $expiry0 + $timespan / 2;
        }

        $active = $expiry0 < $expiry1 ? 0 : 1;

        return [$expiry0, $expiry1, $active];
    }

    /**
     *
     *
     * @param $name
     * @param $expiry
     * @return int
     */
    protected static function _incrementCache($name, $expiry) {
        $value = Gdn::cache()->increment($name, 1, [Gdn_Cache::FEATURE_EXPIRY => $expiry]);

        if (!$value) {
            $value = 1;
            Gdn::cache()->store($name, $value, [Gdn_Cache::FEATURE_EXPIRY => $expiry]);
        }

        return $value;
    }

    /**
     *
     *
     * @return bool|void
     */
    public function incrementGuest() {
        if (!Gdn::cache()->activeEnabled()) {
            return false;
        }

        $now = time();

        $tempName = c('Garden.Cookie.Name').'-Vv';
        $tempCookie = val($tempName, $_COOKIE);
        if (!$tempCookie) {
            setcookie($tempName, $now, $now + 1200, c('Garden.Cookie.Path', '/'));
            return;
        }
        // We are going to be checking one of two cookies and flipping them once every 10 minutes.
        // When we read from one cookie
        $name0 = '__vnOz0';
        $name1 = '__vnOz1';

        list($expire0, $expire1) = self::expiries($now);

        if (!Gdn::session()->isValid()) {
            // Check to see if this guest has been counted.
            if (!isset($_COOKIE[$name0]) && !isset($_COOKIE[$name1])) {
                setcookie($name0, $now, $expire0 + 30, '/'); // cookies expire a little after the cache so they'll definitely be counted in the next one
                $counts[$name0] = self::_IncrementCache($name0, $expire0);

                setcookie($name1, $now, $expire1 + 30, '/'); // We want both cookies expiring at different times.
                $counts[$name1] = self::_IncrementCache($name1, $expire1);
            } elseif (!isset($_COOKIE[$name0])) {
                setcookie($name0, $now, $expire0 + 30, '/');
                $counts[$name0] = self::_IncrementCache($name0, $expire0);
            } elseif (!isset($_COOKIE[$name1])) {
                setcookie($name1, $now, $expire1 + 30, '/');
                $counts[$name1] = self::_IncrementCache($name1, $expire1);
            }
        }
    }

    /**
     *
     *
     * @param UserModel $sender Sending model instance.
     * @return type
     */
    public function userModel_updateVisit_handler($sender) {
        $session = Gdn::session();
        if (!$session->UserID) {
            return;
        }

        $invisible = Gdn::userMetaModel()->getUserMeta($session->UserID, 'Plugin.WhosOnline.Invisible', false);
        $invisible = val('Plugin.WhosOnline.Invisible', $invisible);
        $invisible = ($invisible ? 1 : 0);

        $timestamp = Gdn_Format::toDateTime();
        $px = $sender->SQL->Database->DatabasePrefix;
        $sql = "insert {$px}Whosonline (UserID, Timestamp, Invisible) values ({$session->UserID}, :Timestamp, :Invisible) on duplicate key update Timestamp = :Timestamp1, Invisible = :Invisible1";
        $sender->SQL->Database->query($sql, [
            ':Timestamp' => $timestamp,
            ':Invisible' => $invisible,
            ':Timestamp1' => $timestamp,
            ':Invisible1' => $invisible
        ]);


        // Do some cleanup of old entries.
        $frequency = c('WhosOnline.Frequency', 60);
        $history = time() - 6 * $frequency; // give bit of buffer

        $sql = "delete from {$px}Whosonline where Timestamp < :Timestamp limit 10";
        $sender->SQL->Database->query($sql, [':Timestamp' => Gdn_Format::toDateTime($history)]);
    }
}
