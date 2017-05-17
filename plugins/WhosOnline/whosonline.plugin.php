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
        $ConfigItem = c('WhosOnline.Location.Show', 'every');
        $Controller = $sender->ControllerName;
        $Session = Gdn::session();

        // Check if it's visible to users
        if (c('WhosOnline.Hide', true) && !$Session->isValid()) {
            return;
        }

        // Is this a page for including the module?
        switch ($ConfigItem) {
            case 'custom':
                return;
            case 'every':
                $ShowOnController = [
                    'discussioncontroller',
                    'categoriescontroller',
                    'discussionscontroller',
                    'profilecontroller',
                    'activitycontroller'
                ];
                break;
            case 'discussionsonly':
                $ShowOnController = [
                    'discussionscontroller',
                    'categoriescontroller'
                ];
                break;
            case 'discussion':
            default:
                $ShowOnController = [
                    'discussioncontroller',
                    'discussionscontroller',
                    'categoriescontroller'
                ];
                break;
        }

        // Include the module
        if (inArrayI($Controller, $ShowOnController)) {
            $sender->addModule('WhosOnlineModule');
        }

        // Ping the server when still online
        $sender->addJsFile('whosonline.js', 'plugins/WhosOnline');
        $sender->addCssFile('whosonline.css', 'plugins/WhosOnline');
        $Frequency = c('WhosOnline.Frequency', 60);
        if (!is_numeric($Frequency)) {
            $Frequency = 60;
        }
        $sender->addDefinition('WhosOnlineFrequency', $Frequency);

    }

    /**
     *
     *
     * @param EntryController $sender Sending controller instance.
     */
    public function entryController_signOut_handler($sender) {
        $User = $sender->EventArguments['SignoutUser'];
        $UserID = getValue('UserID', $User, false);
        if ($UserID === false) {
            return;
        }

        Gdn::sql()->delete('Whosonline', [
            'UserID' => getValue('UserID', $User)
        ]);
    }

    /**
     * Add privacy settings to profile menu.
     *
     * @param ProfileController $sender Sending controller instance.
     */
    public function profileController_afterAddSideMenu_handler($sender) {
        $SideMenu = $sender->EventArguments['SideMenu'];
        $Session = Gdn::session();
        $ViewingUserID = $Session->UserID;

        if ($sender->User->UserID == $ViewingUserID) {
            $SideMenu->addLink('Options', t('Privacy Settings'), '/profile/whosonline', false, ['class' => 'Popup']);
        }
    }

    /**
     * Let users modify their privacy settings.
     *
     * @param ProfileController $sender Sending controller instance.
     */
    public function profileController_whosonline_create($sender) {
        $Session = Gdn::session();
        $UserID = $Session->isValid() ? $Session->UserID : 0;
        $sender->getUserInfo();

        // Get the data
        $UserMetaData = $this->getUserMeta($UserID, '%');
        $ConfigArray = [
            'Plugin.WhosOnline.Invisible' => null
        ];

        if ($sender->Form->authenticatedPostBack() === false) {
            // Convert to using arrays if more options are added.
            $ConfigArray = array_merge($ConfigArray, $UserMetaData);
            $sender->Form->setData($ConfigArray);
        } else {
            $Values = $sender->Form->formValues();
            $FrmValues = array_intersect_key($Values, $ConfigArray);

            foreach ($FrmValues as $MetaKey => $MetaValue) {
                $this->setUserMeta($UserID, $this->trimMetaKey($MetaKey), $MetaValue);
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
        $Structure = Gdn::structure();
        $Structure->table('Whosonline')
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
            $Names = ['__vnOz0', '__vnOz1'];

            $Time = time();
            list($Expire0, $Expire1, $Active) = self::expiries($Time);

            // Get bot keys from the cache.
            $Cache = Gdn::cache()->get($Names);

            $Debug = [
                'Cache' => $Cache,
                'Active' => $Active
            ];
            Gdn::controller()->setData('GuestCountCache', $Debug);

            if (isset($Cache[$Names[$Active]])) {
                return $Cache[$Names[$Active]];
            } elseif (is_array($Cache) && count($Cache) > 0) {
                // Maybe the key expired, but the other key is still there.
                return array_pop($Cache);
            }
        } catch (Exception $Ex) {
            echo $Ex->getMessage();
        }
    }

    /**
     *
     *
     * @param $Time
     * @return array
     */
    public static function expiries($Time) {
        $Timespan = 600; // 10 mins.

        $Expiry0 = $Time - $Time % $Timespan + $Timespan;

        $Expiry1 = $Expiry0 - $Timespan / 2;
        if ($Expiry1 <= $Time) {
            $Expiry1 = $Expiry0 + $Timespan / 2;
        }

        $Active = $Expiry0 < $Expiry1 ? 0 : 1;

        return [$Expiry0, $Expiry1, $Active];
    }

    /**
     *
     *
     * @param $Name
     * @param $Expiry
     * @return int
     */
    protected static function _incrementCache($Name, $Expiry) {
        $Value = Gdn::cache()->increment($Name, 1, [Gdn_Cache::FEATURE_EXPIRY => $Expiry]);

        if (!$Value) {
            $Value = 1;
            Gdn::cache()->store($Name, $Value, [Gdn_Cache::FEATURE_EXPIRY => $Expiry]);
        }

        return $Value;
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

        $Now = time();

        $TempName = c('Garden.Cookie.Name').'-Vv';
        $TempCookie = val($TempName, $_COOKIE);
        if (!$TempCookie) {
            setcookie($TempName, $Now, $Now + 1200, c('Garden.Cookie.Path', '/'));
            return;
        }
        // We are going to be checking one of two cookies and flipping them once every 10 minutes.
        // When we read from one cookie
        $Name0 = '__vnOz0';
        $Name1 = '__vnOz1';

        list($Expire0, $Expire1) = self::expiries($Now);

        if (!Gdn::session()->isValid()) {
            // Check to see if this guest has been counted.
            if (!isset($_COOKIE[$Name0]) && !isset($_COOKIE[$Name1])) {
                setcookie($Name0, $Now, $Expire0 + 30, '/'); // cookies expire a little after the cache so they'll definitely be counted in the next one
                $Counts[$Name0] = self::_IncrementCache($Name0, $Expire0);

                setcookie($Name1, $Now, $Expire1 + 30, '/'); // We want both cookies expiring at different times.
                $Counts[$Name1] = self::_IncrementCache($Name1, $Expire1);
            } elseif (!isset($_COOKIE[$Name0])) {
                setcookie($Name0, $Now, $Expire0 + 30, '/');
                $Counts[$Name0] = self::_IncrementCache($Name0, $Expire0);
            } elseif (!isset($_COOKIE[$Name1])) {
                setcookie($Name1, $Now, $Expire1 + 30, '/');
                $Counts[$Name1] = self::_IncrementCache($Name1, $Expire1);
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
        $Session = Gdn::session();
        if (!$Session->UserID) {
            return;
        }

        $Invisible = Gdn::userMetaModel()->getUserMeta($Session->UserID, 'Plugin.WhosOnline.Invisible', false);
        $Invisible = val('Plugin.WhosOnline.Invisible', $Invisible);
        $Invisible = ($Invisible ? 1 : 0);

        $Timestamp = Gdn_Format::toDateTime();
        $Px = $sender->SQL->Database->DatabasePrefix;
        $Sql = "insert {$Px}Whosonline (UserID, Timestamp, Invisible) values ({$Session->UserID}, :Timestamp, :Invisible) on duplicate key update Timestamp = :Timestamp1, Invisible = :Invisible1";
        $sender->SQL->Database->query($Sql, [
            ':Timestamp' => $Timestamp,
            ':Invisible' => $Invisible,
            ':Timestamp1' => $Timestamp,
            ':Invisible1' => $Invisible
        ]);


        // Do some cleanup of old entries.
        $Frequency = c('WhosOnline.Frequency', 60);
        $History = time() - 6 * $Frequency; // give bit of buffer

        $Sql = "delete from {$Px}Whosonline where Timestamp < :Timestamp limit 10";
        $sender->SQL->Database->query($Sql, [':Timestamp' => Gdn_Format::toDateTime($History)]);
    }
}
