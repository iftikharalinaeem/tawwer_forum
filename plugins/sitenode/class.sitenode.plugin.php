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
    /// Constants ///

    const HUB_COOKIE = 'vf_hub_ENDTX';
    const NODE_COOKIE = 'vf_node_ENDTX';
    const PROVIDER_KEY = 'hub';

    /// Properties ///

    protected $hubUrl;

    /// Methods ///

    /**
     * Initialize an instance of the {@link SiteNodePlugin}.
     */
    public function __construct() {
        parent::__construct();
        $this->hubUrl = C('Hub.Url', Gdn::Request()->Domain().'/hub');
    }

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
            array(
                'AuthenticationSchemeAlias' => self::PROVIDER_KEY,
                'URL' => '/hub',
                'AssociationSecret' => '...', 'AssociationHashMethod' => '...',
                'SignInUrl' => '/hub/entry/signin',
                'SignOutUrl' => '/hub/entry/signout'
            ),
            array('AuthenticationKey' => self::PROVIDER_KEY), TRUE);

        // Add foreign ID columns specifically for the hub sync. These must not be unique.
        Gdn::Structure()
            ->table('Category')
            ->column('HubID', 'int', true)
            ->set();

        Gdn::Structure()
            ->table('Role')
            ->column('HubID', 'int', true)
            ->set();
    }

    /**
     * Check to see if a valid sso token was passed through the header.
     */
    public function checkSSO() {
        if (Gdn::Session()->IsValid()) {
            return;
        }

        // First look for a header.
        if ($auth = val('HTTP_AUTHENTICATION', $_SERVER, '')) {
            if (preg_match('`^token\s+([^\s]+)`i', $auth, $m)) {
                $token = $m[1];
            }
        }
        if ($token && $token === Infrastructure::clusterConfig('cluster.loader.apikey', '')) {
            $userID = Gdn::userModel()->GetSystemUserID();
            if ($userID) {
                Gdn::Session()->Start($userID, false, false);
                Gdn::Session()->ValidateTransientKey(true);
            }
        }
    }

    /**
     * Make an api call out to the hub.
     *
     * @param string $path The path to the api endpoint.
     * @param string $method The http method to use.
     * @param array $params The parameters for the request, either get or post.
     * @param bool $system Whether or not the request should authorize as the system user.
     * @return mixed Returns the decoded response from the request.
     * @throws Gdn_UserException Throws an exception when the api endpoint returns an error response.
     */
    public function hubApi($path, $method = 'GET', $params = [], $system = false) {
        Trace("hub api: $method $path");

        $headers = [];

        // Kludge for osx that doesn't allow host files.
        $urlParts = parse_url($this->hubUrl);

        if ($urlParts['host'] === 'localhost' || StringEndsWith($urlParts['host'], '.lc')) {
            $headers['Host'] = $urlParts['host'];
            $urlParts['host'] = '127.0.0.1';
        }

        $url = rtrim(http_build_url($this->hubUrl, $urlParts), '/').'/api/v1/'.ltrim($path, '/');

        if ($system && $access_token = Infrastructure::clusterConfig('cluster.loader.apikey', '')) {
//            $params['access_token'] = C('Plugins.SimpleAPI.AccessToken');
            $headers['Authentication'] = "token $access_token";
        }

        $request = new ProxyRequest();
        $response = $request->Request([
            'URL' => $url,
            'Cookies' => !$system,
            'Timeout' => 100,
        ], $params, null, $headers);

        if ($request->ContentType === 'application/json') {
            $response = json_decode($response, true);
        }

        if ($request->ResponseStatus != 200) {
            Trace($response, "Error {$request->ResponseStatus}");
            throw new Gdn_UserException('api: '.val('Exception', $response, 'There was an error performing your request.'), $request->ResponseStatus);
        }

        Trace($response, "hub api response");
        return $response;
    }

    public function syncNode() {
        // Get the config from the hub.
        $config = $this->hubApi('/multisites/nodeconfig.json', 'GET', ['nodeUrl' => Url('/', '//')], true);

        // Enable plugins.
        foreach (val('Addons', $config, []) as $addonKey => $enabled) {
            try {
                $this->toggleAddon($addonKey, $enabled);
            } catch (Exception $ex) {
                Logger::event('nodesync_error', LogLevel::ALERT, $ex->getMessage());
            }
        }

        if ($theme = val('Theme', $config)) {
            try {
                Gdn::ThemeManager()->EnableTheme($theme);
            } catch (Exception $ex) {
                Logger::event('nodesync_error', LogLevel::ALERT, $ex->getMessage());
            }
        }

        // Synchronize the roles.
    }

    /**
     * Enable or disable an addon (plugin or application).
     *
     * @param string $key The addon key.
     * @param bool $enabled Whether to enable or disable the addon.
     */
    public function toggleAddon($key, $enabled) {
        $valid = new Gdn_Validation();

        // Try the key as a plugin first.
        $info = Gdn::PluginManager()->GetPluginInfo($key, Gdn_PluginManager::ACCESS_PLUGINNAME);
        if ($info) {
            $currentEnabled = Gdn::PluginManager()->IsEnabled($key);
            if ($enabled && !$currentEnabled) {
                Gdn::PluginManager()->EnablePlugin($key, $valid);
                Trace("Plugin $key enabled.");
            } elseif (!$enabled && $currentEnabled) {
                Gdn::PluginManager()->DisablePlugin($key);
                Trace("Plugin $key disabled.");
            }
            return;
        }

        // Try the key as an application.
        $info = Gdn::ApplicationManager()->GetApplicationInfo($key);
        if ($info) {
            $currentEnabled = array_key_exists($info, Gdn::ApplicationManager()->EnabledApplications());
            if ($enabled && !$currentEnabled) {
                Gdn::ApplicationManager()->EnableApplication($key, $valid);
                Trace("Application $key enabled.");
            } elseif (!$enabled && $currentEnabled) {
                Gdn::ApplicationManager()->DisableApplication($key);
                Trace("Application $key disabled.");
            }
        }
    }

    /// Event Handlers ///

    /**
     * SSO someone into through hub if they aren't already signed in.
     * @param Gdn_Dispatcher $sender
     */
    public function gdn_dispatcher_appStartup_handler($sender) {
        if (Gdn::PluginManager()->IsEnabled('sitehub')) {
            return;
        }

        $this->checkSSO();

        if (!Gdn::Session()->IsValid() && val(self::HUB_COOKIE, $_COOKIE)) {
            Trace('Trying hub sso');
            try {
                $user = val('User', $this->hubApi('/profile/hubsso.json'));

                // Hub SSO always synchronizes roles.
                SaveToConfig('Garden.SSO.SynchRoles', true, false);

                // Fire an event so that plugins can determine access etc.
                $this->EventArguments['User'] =& $user;
                $this->FireEvent('hubSSO');

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
        if (Gdn::PluginManager()->IsEnabled('sitehub')) {
            return;
        }

        $path = '/'.trim(Gdn::Request()->WebRoot(), '/');

        SaveToConfig([
            'Garden.Cookie.Name' => self::NODE_COOKIE,
            'Garden.Cookie.Path' => $path,
            'Garden.Registration.AutoConnect' => true,
        ], '', false);
    }

    public function gdn_session_end_handler($sender) {
        // Delete the hub cookie when signing out too.
        if (val(self::HUB_COOKIE, $_COOKIE)) {
            Gdn_CookieIdentity::DeleteCookie(SiteNodePlugin::HUB_COOKIE, '/');
        }
    }

    public function setupController_installed_handler($sender) {
        $this->syncNode();
    }

    /**
     * Synchronize the settings of this node with the hub.
     *
     * @param UtilityController $sender
     */
    public function utilityController_syncNode_create($sender) {
        $this->syncNode();
        $sender->Render('blank');
    }
}
