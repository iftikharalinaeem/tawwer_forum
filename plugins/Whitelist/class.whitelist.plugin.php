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
 */
class WhitelistPlugin extends Gdn_Plugin {

    /**
     * Create a method called "whitelist" on the SettingController.
     *
     * @param $sender Sending controller instance
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
        $configurationModel->setField(array(
            'Whitelist.Active' => c('Whitelist.Active', false),
            'Whitelist.IPList' => c('Whitelist.IPList', null),
        ));

        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } else {
            if ($sender->Form->save()) {
                $sender->StatusMessage = t('Your changes have been saved.');
            }
        }

        $sender->render($this->getView('configuration.php'));
    }

    /**
     * Add a link to the dashboard menu.
     *
     * @param $sender Sending controller instance.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Add-ons', t('Whitelist'), 'settings/whitelist', 'Garden.Settings.Manage');
    }

    public function gdn_dispatcher_afterAnalyzeRequest_handler($sender, $args) {
        // The plugin is not active
        if (!c('Whitelist.Active', false)) {
            return;
        }

        // If you are an admin we should not block you even if you are not whitelisted
        if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            return;
        }

        $request = $args['Request'];
        $blockExceptions = $args['BlockExceptions'];
        $pathRequested = $request->path();

        // Lets use block exceptions as a whitelist of URLs that must not be blocked (ex. entry/*)
        foreach ($blockExceptions as $blockException => $blockLevel) {
            if (preg_match($blockException, $pathRequested)) {
                return;
            }
        }

        $ip = $request->ipAddress();

        // Lets check if you are whitelisted
        if ($this->isIpWhitelisted($ip)) {
            return;
        }

        // Check your privileges son :P
        if (Gdn::session()->isValid()) {
            safeHeader('HTTP/1.0 403 Unauthorized', true, 403);
            if (Gdn::request()->get('DeliveryType') === DELIVERY_TYPE_DATA) {
                safeHeader('Content-Type: application/json; charset=utf-8', true);
            }
        // Lets redirect you to the signin page in case you are an admin.
        } else {
            if (Gdn::request()->get('DeliveryType') === DELIVERY_TYPE_DATA) {
                safeHeader('HTTP/1.0 401 Unauthorized', true, 401);
                safeHeader('Content-Type: application/json; charset=utf-8', true);
                echo json_encode(array('Code' => '401', 'Exception' => t('You must sign in.')));
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
            $self = $this;

            // Convert the list to array, trim each IPs and tokenize them, filter any falsy value.
            $whitelistedIPs = array_filter(
                array_map(
                    function($value) use ($self) {
                        $value = trim($value);
                        return $self->tokenizeIP($value);
                    },
                    explode("\n", $rawList)
                )
            );
        }

        return $whitelistedIPs;
    }

    /**
     * Check whether an IP is whitelisted or not.
     *
     * @param string $ip IP address
     * @return bool true if the IP whitelisted false otherwise
     */
    protected function isIPWhitelisted($ip) {
        $tokenizedIP = $this->tokenizeIP($ip);
        if (!$tokenizedIP) {
            return false;
        }

        $whitelistedIPs = $this->loadWhitelistedIPs();

        foreach($whitelistedIPs as $whitelistedIP) {
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
