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
            ->column('HubID', 'varchar(20)', true, 'unique.hubid')
            ->column('OverrideHub', 'tinyint', '0')
            ->set();

        Gdn::Structure()
            ->table('Role')
            ->column('HubID', 'varchar(20)', true, 'unique.hubid')
            ->column('OverrideHub', 'tinyint', '0')
            ->set();

        Gdn::Structure()
            ->table('Discussion')
            ->column('HubID', 'varchar(20)', true, 'unique.hubid')
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
        $token = '';
        if ($auth = val('HTTP_AUTHORIZATION', $_SERVER, '')) {
            if (preg_match('`^token\s+([^\s]+)`i', $auth, $m)) {
                $token = $m[1];
            }
        }

        if (empty($token)) {
            $allHeaders = getallheaders();
            if (GetValue('Authorization', $allHeaders)) {
                if (preg_match('`^token\s+([^\s]+)`i', $allHeaders['Authorization'], $m)) {
                    $token = $m[1];
                }
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
        $headers = [];

        // Kludge for osx that doesn't allow host files.
        $urlParts = parse_url($this->hubUrl);

        if ($urlParts['host'] === 'localhost' || StringEndsWith($urlParts['host'], '.lc')) {
            $headers['Host'] = $urlParts['host'];
            $urlParts['host'] = '127.0.0.1';
        }

        $url = rtrim(http_build_url($this->hubUrl, $urlParts), '/').'/api/v1/'.ltrim($path, '/');

        if ($system && $access_token = Infrastructure::clusterConfig('cluster.loader.apikey', '')) {
            $headers['Authorization'] = "token $access_token";
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
            throw new Gdn_UserException(val('Exception', $response, 'There was an error performing your request.'), $request->ResponseStatus);
        }

        return $response;
    }

    public function slug() {
        return val('NODE_SLUG', $_SERVER);
    }

    public function syncNode() {
        // Get the config from the hub.
        $config = $this->hubApi('/multisites/nodeconfig.json', 'GET', ['from' => $this->slug()], true);
        if (!val('Sync', $config)) {
            return;
        }

        // Enable plugins.
        foreach (val('Addons', $config, []) as $addonKey => $enabled) {
            try {
                $this->toggleAddon($addonKey, $enabled);
            } catch (Exception $ex) {
                Logger::event('nodesync_error', Logger::ERROR, $ex->getMessage());
            }
        }

        if ($theme = val('Theme', $config)) {
            try {
                Gdn::ThemeManager()->EnableTheme($theme);
            } catch (Exception $ex) {
                Logger::event('nodesync_error', Logger::ERROR, $ex->getMessage());
            }
        }

        if ($mobileTheme = val('MobileTheme', $config)) {
            try {
                Gdn::ThemeManager()->EnableTheme($mobileTheme, true);
            } catch (Exception $ex) {
                Logger::event('nodesync_error', Logger::ERROR, $ex->getMessage());
            }
        }

        // Synchronize some config.
        $saveConfig = (array)val('Config', $config, []);
        TouchValue('Garden.Title', $saveConfig, valr('Multisite.Name', $config));
        SaveToConfig($saveConfig);

        // Synchronize the roles.
        $roleMap = $this->syncRoles(val('Roles', $config, []));

        // Synchronize the categories.
        $this->syncCategories(val('Categories', $config, []), $roleMap);

        // Tell the hub that we've synchronized.

        $this->FireEvent('AfterSync');

        Gdn::Config()->Shutdown();
    }

    public function syncCategories(array $categories, array $roleMap) {
        $categoryModel = new CategoryModel();
        $categoryMap = [];

        foreach ($categories as $category) {
            $hubID = $category['HubID'];
            $parentID = val('ParentHubID', $category);
            if (!$parentID || !isset($categoryMap[$parentID])) {
                $category['ParentCategoryID'] = -1;
            } else {
                $category['ParentCategoryID'] = $categoryMap[$parentID];
            }
            $category['AllowDiscussions'] = true;
            $permissions = val('Permissions', $category, []);
            unset($category['Permissions']);

            if (!empty($permissions)) {
                $category['CustomPermissions'] = true;
                foreach ($permissions as $i => $permissionRow) {
                    $permissions[$i]['JunctionTable'] = 'Category';
                    $permissions[$i]['JunctionColumn'] = 'PermissionCategoryID';
                    $permissions[$i]['RoleID'] = $roleMap[$permissionRow['RoleID']];
                    if ($hubID == '-1') {
                        $permissions[$i]['JunctionID'] = $hubID;
                    }
                }
                $category['Permissions'] = $permissions;
            }

            if ($hubID != '-1') {
                // See if there is an existing category.
                $existingCategory = $categoryModel->GetWhereCache(['HubID' => $hubID]);
                if (!$existingCategory) {
                    // Try linking by url code.
                    $existingCategory = $categoryModel->GetWhereCache(['UrlCode' => $category['UrlCode']]);
                    if ($existingCategory) {
                        $existingCategory = array_shift($existingCategory);
                    }
                } else {
                    $existingCategory = array_shift($existingCategory);
                }

                if ($existingCategory) {
                    $category['CategoryID'] = $existingCategory['CategoryID'];
                    $categoryMap[$hubID] = $existingCategory['CategoryID'];
                }

                $categoryID = $categoryModel->Save($category);
                if ($categoryID) {
                    $categoryMap[$hubID] = $categoryID;
                }
            } else {
                foreach ($permissions as $permissionRow) {
                    Gdn::PermissionModel()->Save($permissionRow);
                }
            }
        }
    }

    public function syncRoles(array $roles) {
        $roleModel = new RoleModel();
        $roles = Gdn_DataSet::Index($roles, 'HubID');
        $roleMap = [];

        foreach ($roles as $hubID => &$role) {
            $permissions = $role['Permissions'];
            unset($role['RoleID'], $role['Permissions']);

            if (!$hubID) {
                Logger::event('nodesync_error', Logger::ERROR, "Node tried to sync a role with no hubID.");
                continue;
            }

            // Grab the current role.
            $currentRole = $roleModel->GetWhere(['HubID' => $hubID])->FirstRow(DATASET_TYPE_ARRAY);
            if (!$currentRole) {
                // Try and grab the role by name.
                $currentRole = $roleModel->GetWhere(['Name' => $role['Name']])->FirstRow(DATASET_TYPE_ARRAY);
                if ($currentRole && $currentRole['HubID'] && isset($roles['HubID'])) {
                    $currentRole = false;
                }
            }
            if ($currentRole) {
                $roleID = $currentRole['RoleID'];
                $fields = array_diff_assoc($role, $currentRole);
                if (!empty($fields)) {
                    $roleModel->Update($fields, ['RoleID' => $roleID]);
                }
            } else {
                // Insert the role.
                $roleID = $roleModel->Insert($role);
            }

            if ($roleID) {
                $permissions['RoleID'] = $roleID;
                $globalPermissions = Gdn::PermissionModel()->Save($permissions, true);
                $roleMap[$hubID] = $roleID;
            }
        }

        // Get a list of roles that need to be deleted.
        $missingHubIDs = Gdn::SQL()
            ->Select('RoleID')
            ->From('Role')
            ->WhereNotIn('HubID', array_keys($roles))
            ->Get()->ResultArray();

        foreach ($missingHubIDs as $missingRow) {
            $missingRoleID = $missingRow['RoleID'];
            $roleModel->Delete($missingRoleID, 0);
        }

        return $roleMap;
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
            $currentEnabled = array_key_exists($key, Gdn::ApplicationManager()->EnabledApplications());
            if ($enabled && !$currentEnabled) {
                Gdn::ApplicationManager()->EnableApplication($key, $valid);
                Trace("Application $key enabled.");
            } elseif (!$enabled && $currentEnabled) {
                Gdn::ApplicationManager()->DisableApplication($key);
                Trace("Application $key disabled.");
            }
            return;
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
                $user = val('User', $this->hubApi('/profile/hubsso.json', 'GET', ['from' => $this->slug()]));

                // Hub SSO always synchronizes roles.
                SaveToConfig('Garden.SSO.SynchRoles', true, false);

                // Translate the roles.
                $roles = val('Roles', $user);
                if (is_array($roles)) {
                    $roleModel = new RoleModel();
                    $roleHubIDs = ConsolidateArrayValuesByKey($roles, 'HubID');
                    $roles = $roleModel->GetWhere(['HubID' => $roleHubIDs])->ResultArray();
                    $user['Roles'] = ConsolidateArrayValuesByKey($roles, 'RoleID');
                    $user['RoleID'] = $user['Roles'];
                }

                // Fire an event so that plugins can determine access etc.
                $this->EventArguments['User'] =& $user;
                $this->FireEvent('hubSSO');

                $user_id = Gdn::UserModel()->Connect($user['UniqueID'], self::PROVIDER_KEY, $user);
                Trace($user_id, 'user ID');
                if ($user_id) {
                    // Add additional authentication.
                    $authentication = val('Authentication', $user);
                    if (is_array($authentication)) {
                        foreach ($authentication as $provider => $authKey) {
                            Gdn::UserModel()->SaveAuthentication([
                                'UserID' => $user_id,
                                'Provider' => $provider,
                                'UniqueID' => $authKey
                            ]);
                        }
                    }

                    Gdn::Session()->Start($user_id, true);
                }
            } catch(Exception $ex) {
                if ($ex->getCode() == 401) {
                    Gdn::Dispatcher()
                        ->PassData('Code', $ex->getCode())
                        ->PassData('Exception', $ex->getMessage())
                        ->PassData('Message', $ex->getMessage())
                        ->PassData('Trace', $ex->getTraceAsString())
                        ->PassData('Url', Url())
                        ->Dispatch('/home/error');
                    exit;
                } else {
                    Trace($ex, TRACE_ERROR);
                }
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
        $this->structure();
        $this->syncNode();
    }

    /**
     * Synchronize the settings of this node with the hub.
     *
     * @param UtilityController $sender
     * @throws Gdn_UserException Throws an exception when any method but post is shown.
     */
    public function utilityController_syncNode_create($sender) {
        $sender->Permission('Garden.Settings.Manage');

        if (Gdn::Request()->RequestMethod() !== 'POST') {
            throw new Gdn_UserException("This resource only accepts POST.", 405);
        }

        $this->syncNode();
        $sender->Render('blank');
    }

    public function cleanspeak_init_handler($sender) {
        $siteID = Infrastructure::site('siteid');
        if (!$siteID) {
            throw new Gdn_UserException('Error getting Site ID for cleanspeak plugin.');
        }
        $sender->uuidSeed = array($siteID, 0, 0, 0);
    }

}
