<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$PluginInfo['Whitelist'] = array(
    'Name' => 'Whitelist',
    'Description' => 'Block any requests that does not come from a whitelisted source.',
    'Version' => '1.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'HasLocale' => false,
    'License' => 'GNU GPL2',
    'SettingsUrl' => '/settings/whitelist',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Alexandre (DaazKu) Chouinard',
    'AuthorEmail' => 'alexandre.c@vanillaforums.com'
);

/**
 * Class WhitelistPlugin
 *
 * By default this plugin allows "BlockExceptions" to be requested by non whitelisted sources.
 * It works like this to prevent an administrator to lock himself out of his own forum.
 *
 * It is possible to disregard "BlockExceptions"
 * by manually setting the Whitelist.BlockMode to "HARDCORE" in the config
 *
 * IPs from Whitelist.MasterIPList will never be blocked by anything even the "HARDCORE" block mode.
 * This config has to be set in the config directly. Use the same format than Whitelist.IPList but separated by ;
 */
class WhitelistPlugin extends Gdn_Plugin {

    protected $informMessage = null;

    /**
     * Create a method called "whitelist" on the SettingController.
     *
     * @param SettingsController $sender Sending controller instance
     */
    public function settingsController_whitelist_create($sender) {
        $sender->title(sprintf(t('%s settings'), t('Whitelist')));
        $sender->addSideMenu('settings/whitelist');

        $sender->Form = new Gdn_Form();
        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('PluginDescription', $this->getPluginKey('Description'));

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'Whitelist.Active' => c('Whitelist.Active', false),
            'Whitelist.IPList' => c('Whitelist.IPList', null),
        ]);

        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } else {
            $sender->Form->setFormValue('Whitelist.Active', (bool)$sender->Form->getFormValue('Whitelist.Active'));

            // Make sure only valid characters are part of the whitelist
            $IPList = $sender->Form->getFormValue('Whitelist.IPList');
            $IPList = $this->cleanIPWhiteList($IPList);
            $sender->Form->setFormValue('Whitelist.IPList', $IPList);

            if ($sender->Form->save() !== false) {
                $sender->informMessage(t('Your changes have been saved.'));
            }
        }

        $sender->render($this->getView('configuration.php'));
    }

    /**
     * Add a link to the dashboard menu.
     *
     * @param object $sender Sending controller instance.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Add-ons', t('Whitelist'), 'settings/whitelist', 'Garden.Settings.Manage');
    }

    /**
     * Add a link to the dashboard menu.
     *
     * @param object $sender Sending controller instance.
     */
    public function base_render_before($sender) {
        if ($this->informMessage !== null) {
            $sender->informMessage($this->informMessage);
            $this->informMessage = null;
        }
    }

    public function gdn_dispatcher_afterAnalyzeRequest_handler($sender, $args) {
        // The plugin is not active
        if (!c('Whitelist.Active', false)) {
            return;
        }

        $isBlockModeHardcore = (c('Whitelist.BlockMode', false) === 'HARDCORE');
        $request = $args['Request'];

        if (!$isBlockModeHardcore) {
            // If you are an admin we should not block you even if you are not whitelisted
            // unless... HARDCORE BLOCK MODE! (don't make a whitelisting error lolz)
            if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
                return;
            }

            $blockExceptions = $args['BlockExceptions'];
            $pathRequested = $request->path();

            // Lets use block exceptions as a whitelist of URLs that must not be blocked (ex. entry/*)
            foreach ($blockExceptions as $blockException => $blockLevel) {
                if (preg_match($blockException, $pathRequested)) {
                    return;
                }
            }
        }

        $ip = $request->ipAddress();

        // Lets check if you are whitelisted
        if ($this->isIPWhitelisted($ip, $this->loadWhitelistedIPs())) {
            return;
        }

        // Lets check the master IP list just in case!
        if ($this->isIPWhitelisted($ip, $this->loadMasterIPList())) {
            // Register an inform message for when controllers will be initialized.
            $this->informMessage = __CLASS__.': '.t('Request allowed by MasterIPList');
            return;
        }

        // Check your privileges son :P
        if ($isBlockModeHardcore || Gdn::session()->isValid()) {
            safeHeader('HTTP/1.0 403 Unauthorized', true, 403);
            if (Gdn::request()->get('DeliveryType') === DELIVERY_TYPE_DATA) {
                safeHeader('Content-Type: application/json; charset=utf-8', true);
            }
        // Lets redirect you to the signin page in case you are an admin.
        } else {
            if (Gdn::request()->get('DeliveryType') === DELIVERY_TYPE_DATA) {
                safeHeader('HTTP/1.0 401 Unauthorized', true, 401);
                safeHeader('Content-Type: application/json; charset=utf-8', true);
                echo json_encode([
                    'Code' => '401',
                    'Exception' => t('You must sign in.'),
                ]);
            } else {
                redirect('/entry/signin?Target='.urlencode($request->path(false)));
            }
        }
        exit();
    }

    /**
     * Load the tokenized list of whitelisted IPs.
     *
     * @return array tokenized IPs
     */
    protected function loadWhitelistedIPs() {
        $whitelistedIPs = [];

        if (($rawList = c('Whitelist.IPList', false)) !== false) {
            $rawList = $this->cleanIPWhiteList($rawList);
            if ($rawList) {
                $whitelistedIPs = $this->parseIPsListDefinition($rawList, "\n");
            }
        }

        return $whitelistedIPs;
    }

    /**
     * Clean the IPWhitelist from invalid characters.
     *
     * @param $ipWhiteList
     * @return string Clean IPWhitelist
     */
    protected function cleanIPWhiteList($ipWhitelist) {
        return preg_replace('/[^\d\n\-*.]/', null, $ipWhitelist);
    }

    /**
     * Load the tokenized list of master IPs.
     *
     * @return array tokenized IPs
     */
    protected function loadMasterIPList() {
        $masterIPList = [];

        if (($rawList = c('Whitelist.MasterIPList', false)) !== false) {
            $masterIPList = $this->parseIPsListDefinition($rawList, ';');
        }

        return $masterIPList;
    }

    /**
     * Parse a list, potentially malformed, of IPs
     *
     * @param string $ipList List of IPs
     * @param string $separator List's separator
     *
     * @return array tokenized IPs
     */
    protected function parseIPsListDefinition($ipList, $separator) {
        $that = $this;

        // Convert the list to array, trim each IPs and tokenize them, filter any falsy value.
        $whitelistedIPs = array_filter(
            array_map(
                function($value) use ($that) {
                    $value = trim($value);
                    return $that->tokenizeIP($value);
                },
                explode($separator, $ipList)
            )
        );

        return $whitelistedIPs;
    }

    /**
     * Check whether an IP is whitelisted or not.
     *
     * @param string $ip IP address
     * @param string $whitelist list of whitelisted IPs
     *
     * @return bool true if the IP whitelisted false otherwise
     */
    protected function isIPWhitelisted($ip, $whitelist) {
        $tokenizedIP = $this->tokenizeIP($ip);
        if (!$tokenizedIP) {
            return false;
        }

        foreach($whitelist as $whitelistedIP) {
            $expandedIP = $this->expandWhitelistedIP($whitelistedIP);

            for ($i = 0; $i < count($tokenizedIP); $i++) {
                $token = $tokenizedIP[$i];
                $toMatch = $expandedIP[$i];

                switch ($toMatch['TYPE']) {
                    case 'ANY':
                        continue 2;
                    case 'RANGE':
                        if ($token >= $toMatch['MIN_VALUE'] && $token <= $toMatch['MAX_VALUE']) {
                            continue 2;
                        }
                        break;
                    case 'MATCH':
                    default:
                        if ($token == $toMatch['VALUE']) {
                            continue 2;
                        }
                        break;
                }

                // No match.. next IP!
                continue 2;
            }

            // Every tokens matched we can stop here!
            return true;
        }

        return false;
    }

    /**
     * Tokenize an IPv4 into 4 token.
     *
     * @param string $ip IP address
     * @return array|bool tokenized IP or false on failure.
     */
    protected function tokenizeIP($ip) {
        $tokens = explode('.', $ip);

        if (count($tokens) !== 4) {
            $tokens = false;
        }

        return $tokens;
    }

    /**
     * Expand a whitelisted IP definition into a format that allows ip tokens comparison.
     *
     * Possible formats:
     *     - Allows all possible values -> ['TYPE' => 'ANY']
     *     - Allows compared value to be between MIN_VALUE and MAX_VALUE -> [
     *         'TYPE' => 'RANGE',
     *         'MIN_VALUE' => ?,
     *         'MAX_VALUE' => ?,
     *     ]
     *     - Compared value must match VALUE -> ['TYPE' => 'MATCH', 'VALUE' => ?]
     *
     * @param array $tokenizedIPDefinition Whitelisted IP that has been tokenized.
     * @return array expanded IP definition.
     */
    protected function expandWhitelistedIP($tokenizedIPDefinition) {
        $expandedIP = [];

        for ($i = 0; $i < count($tokenizedIPDefinition); $i++) {
            $token = $tokenizedIPDefinition[$i];

            if ($token === '*') {
                $expandedIP[$i] = [
                    'TYPE' => 'ANY',
                ];
            } else if (strpos($token, '-') !== false) {
                list($minValue, $maxValue) = explode('-', $token);
                $expandedIP[$i] = [
                    'TYPE' => 'RANGE',
                    'MIN_VALUE' => $minValue,
                    'MAX_VALUE' => $maxValue,
                ];
            } else {
                $expandedIP[$i] = [
                    'TYPE' => 'MATCH',
                    'VALUE' => $token,
                ];
            }
        }

        return $expandedIP;
    }
}
