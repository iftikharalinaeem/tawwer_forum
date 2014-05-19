<?php if (!defined('APPLICATION')) exit;

$PluginInfo['sitenode'] = array(
    'Name'        => "Multisite Node",
    'Version'     => '1.0.0-alpha',
    'Author'      => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl'   => 'http://vanillaforums.com',
    'License'     => 'Proprietary'
);

/**
 * Multisite Node Plugin
 *
 * @author    Todd Burry <todd@vanillaforums.com>
 * @copyright 2014 (c) Todd Burry
 * @license   Proprietary
 * @since     1.0.0
 */
class SiteNodePlugin extends Gdn_Plugin {
    /// CONSTANTS ///

    const HUB_COOKIE = 'vf_hub_ENDTX';
    const NODE_COOKIE = 'vf_node_ENDTX';
    const PROVIDER_KEY = 'hub';

    /// PROPERTIES ///

    /// METHODS ///

    /**
     * This will run when you "Enable" the plugin
     *
     * @since  1.0.0
     * @return bool
     */
    public function setup() {
        $this->structure();
    }

    public function structure() {
        // Save the hub sso provider type.
        Gdn::SQL()->Replace('UserAuthenticationProvider',
            array('AuthenticationSchemeAlias' => self::PROVIDER_KEY, 'URL' => '...', 'AssociationSecret' => '...', 'AssociationHashMethod' => '...'),
            array('AuthenticationKey' => self::PROVIDER_KEY), TRUE);
    }

    public function hubApi($path, $method = 'GET', $params = []) {
        Trace("hub api: $method $path");

        $url = rtrim(Gdn::Request()->Domain(), '/');
        $headers = [];

        // Cludge for osx that doesn't allow host files.
        if (Gdn::Request()->Host() === 'localhost' || StringEndsWith($url, '.lc')) {
            $url = 'http://127.0.0.1';
            $headers['Host'] = Gdn::Request()->Host();
        }

        $url .= '/hub/'.ltrim($path, '/');

        $request = new ProxyRequest();
        $response = $request->Request([
            'URL' => $url,
            'Cookies' => true,
            'Timeout' => 100
        ], $params, null, $headers);

        if ($request->ContentType === 'application/json') {
            $response = json_decode($response, true);
        }

        if ($request->ResponseStatus != 200) {
            Trace($response, "Error {$request->ResponseStatus}");
            throw new Gdn_UserException(val('Exception', $response, 'There was an error performing your request.'), $request->ResponseStatus);
        }

        Trace($response, "hub api response");
        return $response;
    }

    /// EVENT HANDLERS ///

    /**
     * SSO someone into the hub if they aren't already signed in.
     * @param Gdn_Dispatcher $sender
     */
    public function gdn_dispatcher_appStartup_handler($sender) {
        if (!Gdn::Session()->IsValid() && val(self::HUB_COOKIE, $_COOKIE)) {
            Trace('Trying hub sso');
            try {
                $user = val('User', $this->hubApi('/profile/hubsso.json'));
                $user_id = Gdn::UserModel()->Connect($user['UserID'], self::PROVIDER_KEY, $user);
                Trace($user_id, 'user ID');
                if ($user_id) {
                    Gdn::Session()->Start($user_id, true);
                }
            } catch(Exception $ex) {
                Trace($ex, TRACE_ERROR);
            }
        }
    }

    /**
     *
     * @param Gdn_PluginManager $sender
     */
    public function gdn_pluginManager_afterStart_handler($sender) {
        $path = '/'.trim(Gdn::Request()->WebRoot(), '/');

        SaveToConfig([
            'Garden.Cookie.Name' => self::NODE_COOKIE,
            'Garden.Cookie.Path' => $path,
            'Garden.Registration.AutoConnect' => true,
        ], '', false);
    }
}
