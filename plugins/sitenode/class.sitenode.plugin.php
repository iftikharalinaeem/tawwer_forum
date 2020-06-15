<?php
use Vanilla\Addon;

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
        $this->hubUrl = c('Hub.Url', Gdn::request()->domain().'/hub');
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
        Gdn::sql()->replace('UserAuthenticationProvider',
            [
                'AuthenticationSchemeAlias' => self::PROVIDER_KEY,
                'URL' => '/hub',
                'AssociationSecret' => '...', 'AssociationHashMethod' => '...',
                'SignInUrl' => '/hub/entry/signin',
                'SignOutUrl' => '/hub/entry/signout'
            ],
            ['AuthenticationKey' => self::PROVIDER_KEY], TRUE);

        // Add foreign ID columns specifically for the hub sync. These must not be unique.
        Gdn::structure()->table('Category');
        $hasCatType = Gdn::structure()
            ->table('Category')
            ->columnExists('Type');

        Gdn::structure()->column('HubID', 'varchar(20)', true, 'unique.hubid')
            ->column('OverrideHub', 'tinyint', '0')
            ->set();

        Gdn::structure()
            ->table('Role')
            ->column('HubID', 'varchar(20)', true, 'unique.hubid')
            ->column('OverrideHub', 'tinyint', '0')
            ->set();

        Gdn::structure()
            ->table('UserAuthenticationProvider')
            ->column('SyncWithHub', 'tinyint(1)', '1')
            ->set();

        if ($hasCatType) {
            // Reporting categories should be managed locally. Set them to override hub, if they aren't already.
            $hubReporting = Gdn::sql()->getWhere(
                'Category',
                ['Type' => 'Reporting', 'OverrideHub' => 0]
            )->count();

            if ($hubReporting > 0) {
                Gdn::sql()->update(
                    'Category',
                    ['OverrideHub' => 1],
                    ['Type' => 'Reporting', 'OverrideHub' => 0]
                )->put();
            }
        }
    }

    /**
     * Check to see if a category can be modified by the node.
     *
     * @param SettingsController $sender
     * @param array $args
     * @throws Gdn_UserException Throws an exception when trying to save a hub managed category.
     */
    protected function checkCategoryOverride($sender, $args) {
        $categoryID = val('categoryid', array_change_key_case($sender->ReflectArgs));
        if (!$categoryID) {
            return;
        }

        $category = CategoryModel::categories($categoryID);
        if (!val('HubID', $category)) {
            return;
        }

        if ($sender->Request->isAuthenticatedPostBack()) {
            // Roles from the hub cannot be edited/deleted under any circumstance.
            throw new Gdn_UserException('This category is administered in the hub.', 400);
        } elseif ($sender->deliveryType() === DELIVERY_TYPE_DATA) {
            return;
        } else {
            $sender->addSideMenu('');
            $sender->render('hubcategory', 'settings', 'plugins/sitenode');
            die();
        }
    }

    /**
     * Check to see if a role can be modified by the node.
     *
     * @param RoleController $sender
     * @param array $args
     * @throws Gdn_UserException Throws an exception when trying to save to a non-overridden hub.
     */
    protected function checkRoleOverride($sender, $args) {
        $roleID = val('roleid', array_change_key_case($sender->ReflectArgs));
        if (!$roleID) {
            return;
        }

        $role = $sender->RoleModel->getByRoleID($roleID);
        if (!val('HubID', $role)) {
            return;
        }

        $sender->Form->addHidden('HubID', val('HubID', $role));
        if (!$role || val('OverrideHub', $role)) {
            return;
        }

        if ($sender->Request->isAuthenticatedPostBack()) {
            // Roles from the hub cannot be edited/deleted under any circumstance.
            throw new Gdn_UserException('This role is administered in the hub.', 400);
        } elseif ($sender->deliveryType() === DELIVERY_TYPE_DATA) {
            return;
        } else {
            $sender->addSideMenu('');
            $sender->setData('RoleID', $roleID);
            $sender->render('hubrole', 'role', 'plugins/sitenode');
            die();
        }
    }

    /**
     * Check to see if a valid sso token was passed through the header.
     */
    public function checkSSO() {
        if (Gdn::session()->isValid()) {
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
            if (getValue('Authorization', $allHeaders)) {
                if (preg_match('`^token\s+([^\s]+)`i', $allHeaders['Authorization'], $m)) {
                    $token = $m[1];
                }
            }
        }

        if ($token && $token === Infrastructure::clusterConfig('cluster.loader.apikey', '')) {
            $userID = Gdn::userModel()->getSystemUserID();
            if ($userID) {
                Gdn::session()->start($userID, false, false);
                Gdn::session()->validateTransientKey(true);
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

        if ($urlParts['host'] === 'localhost' || stringEndsWith($urlParts['host'], '.lc') || stringEndsWith($urlParts['host'], '.dev')) {
            $headers['Host'] = $urlParts['host'];
            $urlParts['host'] = '127.0.0.1';
        }

        $url = rtrim(http_build_url($this->hubUrl, $urlParts), '/').'/api/v1/'.ltrim($path, '/');
        $localhost = $params['localhost'] ?? false;
        if (!$localhost && $system && $access_token = Infrastructure::clusterConfig('cluster.loader.apikey', '')) {
            $headers['Authorization'] = "token $access_token";
        }

        if ($localhost) {
            //This is similar as making an API V1 call as System.
            $headers['Authorization'] = 'Bearer '.$params['spoofToken'] ?? '';
        }

        $request = new ProxyRequest();
        $response = $request->request([
            'URL' => $url,
            'Cookies' => !$system,
            'Method' => $method,
            'Timeout' => 100,
        ], $params, null, $headers);

        if (strpos($request->ContentType, 'application/json') !== false) {
            $response = json_decode($response, true);
        }

        if ($request->ResponseStatus != 200) {
            trace($response, "Error {$request->ResponseStatus}");
            throw new Gdn_UserException(val('Exception', $response, 'There was an error performing your request.'), $request->ResponseStatus);
        }

        return $response;
    }

    public function slug() {
        return val('NODE_SLUG', $_SERVER);
    }

    /**
     *  Call the hub from the node to bring over data and update the local db and config.
     *
     * @param array $params Optionally pass parameters for debugging purposes.
     * @throws Exception Catch problems and log them.
     *
     * @return mixed;
     */
    public function syncNode($params = []) {
        // Get the config from the hub.
        $config = $this->hubApi('/multisites/nodeconfig.json', 'GET', $params + ['from' => $this->slug()], true);
        if (!val('Sync', $config)) {
            Logger::event('syncnode_skip', Logger::INFO, "The hub told us not to sync.");
            return;
        }

        $siteID = valr('Multisite.MultisiteID', $config);

        // Enable plugins.
        foreach (val('Addons', $config, []) as $addonKey => $enabled) {
            if (strcasecmp($addonKey, 'sitehub') === 0 && $enabled) {
                Logger::event('snycnode_skipaddon', Logger::WARNING, "The sitehub addon should not be enabled in nodes.");
                $this->toggleAddon($addonKey, false);
                continue;
            }

            try {
                $this->toggleAddon($addonKey, $enabled);
            } catch (Exception $ex) {
                Logger::event('nodesync_error', Logger::ERROR, $ex->getMessage());
            }
        }

        if ($theme = val('Theme', $config)) {
            try {
                Gdn::themeManager()->enableTheme($theme);
            } catch (Exception $ex) {
                Logger::event('nodesync_error', Logger::ERROR, $ex->getMessage());
            }
        }

        if ($mobileTheme = val('MobileTheme', $config)) {
            try {
                Gdn::themeManager()->enableTheme($mobileTheme, true);
            } catch (Exception $ex) {
                Logger::event('nodesync_error', Logger::ERROR, $ex->getMessage());
            }
        }

        // Synchronize some config.
        $saveConfig = (array)val('Config', $config, []);
        saveToConfig($saveConfig);

        // Synchronize the roles.
        trace('Synchronizing roles.');
        $roleMap = $this->syncRoles(val('Roles', $config, []));

        // Synchronize the categories.
        trace('Synchronizing categories.');
        $this->syncCategories(val('Categories', $config, []), val('OtherCategories', $config, []), $roleMap);

        // Synchronize the authenticators.
        trace('Synchronizing authenticators.');
        $this->syncAuthenticators(val('Authenticators', $config, []));

        // Everything after this communicates with the hub, if you are debugging you
        // don't want to update the production hub, so return here.
        $mode = $params['mode'] ?? null;
        if ($mode && $params['mode'] === 'debug') {
            $this->syncNodeSuccess();
            return;
        }

        // Push the categories.
        try {
            trace('Pushing categories.');
            $r = $this->pushCategories();
            Gdn::controller()->setData('NodeCategories', $r);
        } catch (Exception $ex) {
            // Do nothing. The exception is logged.
        }

        // Push the subcommunities.
        try {
            trace('Pushing subcommunities.');
            $r = $this->pushSubcommunities();
            Gdn::controller()->setData('NodeSubcommunities', $r);
        } catch (Exception $ex) {
            // Do nothing. The exception is logged.
            Gdn::controller()->setData('NodeSubcommunities', 'error');
        }

        $this->fireEvent('AfterSync');

        // Tell the hub that we've synchronized.
        $now = Gdn_Format::toDateTime();
        Gdn::userMetaModel()->setUserMeta(0, 'siteNode.dateLastSync', $now);
        $result = $this->hubApi(
            "/multisites/$siteID.json",
            'POST',
            ['Name' => c('Garden.HomepageTitle', c('Garden.Title', $this->slug())), 'Locale' => Gdn::locale()->current(), 'DateLastSync' => $now, 'Status' => 'active'],
            true
        );

        $this->syncNodeSuccess();
        return;
    }

    public function syncAuthenticators(array $authenticators) {
        $model = new Gdn_AuthenticationProviderModel();

        foreach ($authenticators as $row) {
            // Look for an existing authenticator.
            $current = $model->getWhere(['AuthenticationKey' => $row['AuthenticationKey']])->firstRow();
            if (!$current || val('SyncWithHub', $current)) {
                unset($row['SyncToNodes']);
                $row['SyncWithHub'] = true;
                $model->save($row);
            }
        }
    }

    /**
     * The /utility/pushcategories endpoint.
     *
     * @param UtilityController $sender
     */
    public function utilityController_pushCategories_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        if (!$sender->Request->isAuthenticatedPostBack(true)) {
            throw forbiddenException('GET');
        }

        $this->Data = $this->pushCategories();

        $sender->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Push the categories from this node to the hub.
     *
     * @return mixed
     */
    public function pushCategories() {
        $categories = Gdn::sql()
            ->select('CategoryID, Name, UrlCode, HubID')
            ->getWhere('Category', ['CategoryID >' => 0], '', '', 250)->resultArray();

        $post = [
            'Slug' => $this->slug(),
            'Categories' => $categories,
            'Delete' => true
        ];

        $json = json_encode($post, JSON_PRETTY_PRINT);

        $r = $this->hubApi('/multisites/syncnodecategories.json', 'POST', $post, true);
        return $r;
    }

    /**
     * Push subcommunities from this node to the hub.
     *
     * @return mixed Returns the result of the API call or **false** if subcommunities is not enabled.
     */
    public function pushSubcommunities() {
        if (!class_exists('SubcommunityModel')) {
            return false;
        }

        $subcommunities = SubcommunityModel::instance()->getWhere()->resultArray();
        array_walk($subcommunities, function (&$row) {
            $row['IsDefault'] = (bool)$row['IsDefault'];
        });
        $post = [
            'Slug' => $this->slug(),
            'Subcommunities' => $subcommunities,
            'Delete' => true
        ];

        $json = json_encode($post);
        $r = $this->hubApi('/multisites/syncnodesubcommunities.json', 'POST', $post, true);
        return $r;
    }

    /**
     * Synchronize the categories with an array from the hub.
     *
     * @param array $categories The categories to sync.
     * @param array $otherCategories Other categories that should not by synchronized.
     * @param array $roleMap A mapping of hub roles to roles on this site.
     */
    public function syncCategories(array $categories, array $otherCategories, array $roleMap) {
        $categoryMap = [];
        $sort = 0;
        $roles = RoleModel::roles();
        $permissionModel = new PermissionModel();

        foreach ($categories as $category) {
            $categoryModel = new CategoryModel(); // have to recreate each time

            $sort++;
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

            if ($hubID != '-1') {
                // See if there is an existing category.
                $existingCategory = $categoryModel->getWhereCache(['HubID' => $hubID]);
                if (!$existingCategory) {
                    // Try linking by url code.
                    $existingCategory = $categoryModel->getWhereCache(['UrlCode' => $category['UrlCode']]);
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
                $category['Sort'] = $sort;
            }

            if (!empty($permissions)) {
                $category['CustomPermissions'] = true;

                // Get all of the currently selected role/permission combinations for this junction.
                $currentCategoryPermissions = $permissionModel->getJunctionPermissions(['JunctionID' => val('CategoryID', $category, 0)], 'Category');
                $currentCategoryPermissions = Gdn_DataSet::index($currentCategoryPermissions, 'RoleID');

                foreach ($permissions as $i => $permissionRow) {
                    $hubRoleID = $permissionRow['RoleID'];

                    // Grab the local role ID to perform current permission lookup.
                    $roleID = val($hubRoleID, $roleMap);
                    if (empty($roleID)) {
                        continue; // role disconnected
                    }

                    // Translate the hub's role ID to the local role ID.
                    $permissionRow['RoleID'] = $roleID;

                    // Do not molest roles that respectfully requested some goddamn peace and quiet.
                    $role = val($roleID, $roles);
                    if (val('OverrideHub', $role)) {
                        continue;
                    }

                    // Verify we have an existing permission row before attempting to merge in the new.
                    if (array_key_exists($roleID, $currentCategoryPermissions) && is_array($currentCategoryPermissions[$roleID])) {
                        $roleCategoryPermissions = array_merge(
                            $currentCategoryPermissions[$roleID],
                            $permissionRow
                        );
                    } else {
                        $roleCategoryPermissions = $permissionRow;
                    }
                    $currentCategoryPermissions[$roleID] = $roleCategoryPermissions;
                }
                // New set of permission! CategoryModel save want them all!
                $category['Permissions'] = $currentCategoryPermissions;
            }


            if ($hubID != '-1') {
                $categoryID = $categoryModel->save($category);
                if ($categoryID) {
                    $categoryMap[$hubID] = $categoryID;
                }
                $categoryModel->Validation->results(true);
            } elseif (!empty($category['Permissions'])) {
                $permissions = $category['Permissions'];
                foreach ($permissions as &$perm) {
                    $perm['JunctionTable'] = 'Category';
                    $perm['JunctionColumn'] = 'PermissionCategoryID';
                    $perm['JunctionID'] = -1;
                    $permissionModel->save($perm);
                }
            }
        }

        trace($categoryMap, 'categoryMap');
        $categoryModel = new CategoryModel();

        // Remove the synchronization from other categories.
        foreach ($otherCategories as $hubID) {
            $categories = $categoryModel->getWhereCache(['HubID' => $hubID]);

            foreach ($categories as $categoryID => $category) {
                $categoryModel->setField($categoryID, 'HubID', null);
                trace("Removing hub ID for category $categoryID.");
            }
        }

        // Find categories that have been removed from the hub.
        $toDelete = Gdn::sql()
            ->select('CategoryID')
            ->from('Category')
            ->whereNotIn('CategoryID', $categoryMap)
            ->where('HubID is not null')
            ->where('OverrideHub', 0)
            ->get()->resultArray();
        $deleteIDs = array_column($toDelete, 'CategoryID');

        foreach ($deleteIDs as $categoryID) {
            if ($categoryID <= 0) {
                continue;
            }
            $category = CategoryModel::categories($categoryID);
            $categoryModel->deleteID($category['CategoryID']);
        }

        // Update the sort order of categories that aren't from the hub.
        Gdn::sql()->update('Category')
            ->set('Sort', "Sort + $sort", false, false)
            ->where('CategoryID >', 0)
            ->whereNotIn('CategoryID', $categoryMap)
            ->put();

        // Rebuild the tree with from the new sort.
        $categoryModel->rebuildTree(true);
    }

    public function syncRoles(array $roles) {
        $roleModel = new RoleModel();
        $roles = Gdn_DataSet::index($roles, 'HubID');
        $roleMap = [];

        foreach ($roles as $hubID => &$role) {
            $permissions = $role['Permissions'];
            unset($role['RoleID'], $role['Permissions'], $role['Deletable']);

            if (!$hubID) {
                Logger::event('nodesync_error', Logger::ERROR, "Node tried to sync a role with no hubID.");
                continue;
            }

            // Grab the current role.
            $currentRole = $roleModel->getWhere(['HubID' => $hubID])->firstRow(DATASET_TYPE_ARRAY);
            if (!$currentRole) {
                // Try and grab the role by name.
                $currentRole = $roleModel->getWhere(['Name' => $role['Name']])->firstRow(DATASET_TYPE_ARRAY);
                if ($currentRole && $currentRole['HubID'] && isset($roles['HubID'])) {
                    $currentRole = false;
                }
            }
            if (val('OverrideHub', $currentRole)) {
                continue;
            }

            if ($currentRole) {
                $roleID = $currentRole['RoleID'];
                $fields = array_diff_assoc($role, $currentRole);
                if (!empty($fields)) {
                    $roleModel->update($fields, ['RoleID' => $roleID]);
                }
            } else {
                // Insert the role.
                $roleID = $roleModel->insert($role);
            }

            if ($roleID) {
                $permissions['RoleID'] = $roleID;
                $globalPermissions = Gdn::permissionModel()->save($permissions, true);
                trace(Gdn::permissionModel()->Validation->results(true));
                $roleMap[$hubID] = $roleID;
            }
        }

        // Get a list of roles that need to be deleted.
        $missingHubIDs = Gdn::sql()
            ->select('RoleID')
            ->from('Role')
            ->where('HubID is not null')
            ->whereNotIn('HubID', array_keys($roles))
            ->get()->resultArray();

        foreach ($missingHubIDs as $missingRow) {
            $missingRoleID = $missingRow['RoleID'];
            $roleModel->deleteID($missingRoleID);
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
        $info = Gdn::pluginManager()->getPluginInfo($key, Gdn_PluginManager::ACCESS_PLUGINNAME);
        if ($info) {
            $currentEnabled = Gdn::addonManager()->isEnabled($key, Addon::TYPE_ADDON);
            if ($enabled && !$currentEnabled) {
                Gdn::pluginManager()->enablePlugin($key, $valid);
                trace("Plugin $key enabled.");
            } elseif (!$enabled && $currentEnabled) {
                Gdn::pluginManager()->disablePlugin($key);
                trace("Plugin $key disabled.");
            }
            return;
        }

        // Try the key as an application.
        $info = Gdn::applicationManager()->getApplicationInfo($key);
        if ($info) {
            $currentEnabled = array_key_exists($key, Gdn::applicationManager()->enabledApplications());
            if ($enabled && !$currentEnabled) {
                Gdn::applicationManager()->enableApplication($key, $valid);
                trace("Application $key enabled.");
            } elseif (!$enabled && $currentEnabled) {
                Gdn::applicationManager()->disableApplication($key);
                trace("Application $key disabled.");
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
        if (Gdn::addonManager()->isEnabled('sitehub', Addon::TYPE_ADDON)) {
            Logger::event('hubsso_skip', Logger::DEBUG, "Site hub is enabled. Skipping Hub SSO.");
            return;
        }

        $this->checkSSO();

        if (!Gdn::session()->isValid() && $hubCookie = val(self::HUB_COOKIE, $_COOKIE)) {
            // Check the cookie expiry here.
            $expiry = 0;
            $hubJWT = explode('.', $hubCookie);
            if (count($hubJWT) === 3 && isset($hubJWT[1])) {
                $hubJWTPayload = base64_decode($hubJWT[1]);
                if (is_string($hubJWTPayload)) {
                    $hubJWTPayload = json_decode($hubJWTPayload, true);
                    if (is_array($hubJWTPayload) && array_key_exists('exp', $hubJWTPayload)) {
                        $expiry = $hubJWTPayload['exp'];
                    }
                }
            }

            if ($expiry < time()) {
                Logger::event(
                    'hubsso_expired',
                    Logger::INFO,
                    "Skipping hub SSO because the hub's cookie has expired.",
                    ['cookieExpiry' => $expiry]
                );
                return;
            }

            try {
                $user = val('User', $this->hubApi('/profile/hubsso.json', 'GET', ['from' => $this->slug()]));

                // Hub SSO always synchronizes roles.
                saveToConfig('Garden.SSO.SyncRoles', true, false);
                saveToConfig('Garden.SSO.SynchRoles', true, false); // backwards compat

                // Translate the roles.
                $roles = val('Roles', $user);
                if (is_array($roles)) {
                    $roleModel = new RoleModel();
                    $roleHubIDs = array_column($roles, 'HubID');
                    $roles = $roleModel->getWhere(['HubID' => $roleHubIDs])->resultArray();
                    $user['Roles'] = array_column($roles, 'RoleID');
                    $user['RoleID'] = $user['Roles'];
                }

                // Fire an event so that plugins can determine access etc.
                $this->EventArguments['User'] =& $user;
                $this->fireEvent('hubSSO');

                trace($user, 'hubSSO');

                $config = Gdn::config();
                $autoConnect = $config->get("Garden.Registration.AutoConnect");
                $config->set("Garden.Registration.AutoConnect", true, true, false);
                $user_id = Gdn::userModel()->connect($user['UniqueID'], self::PROVIDER_KEY, $user);
                $config->set("Garden.Registration.AutoConnect", $autoConnect, true, false);

                trace($user_id, 'user ID');
                if ($user_id) {
                    // Add additional authentication.
                    $authentication = val('Authentication', $user);
                    if (is_array($authentication)) {
                        foreach ($authentication as $provider => $authKey) {
                            Gdn::userModel()->saveAuthentication([
                                'UserID' => $user_id,
                                'Provider' => $provider,
                                'UniqueID' => $authKey
                            ]);
                        }
                    }

                    Gdn::session()->start($user_id, true);
                }
            } catch (Exception $ex) {
                if ($ex->getCode() == 401) {
                    Gdn::dispatcher()
                        ->passData('Code', $ex->getCode())
                        ->passData('Exception', $ex->getMessage())
                        ->passData('Message', $ex->getMessage())
                        ->passData('Trace', $ex->getTraceAsString())
                        ->passData('Url', url())
                        ->dispatch('/home/error');
                    exit;
                } else {
                    trace($ex, TRACE_ERROR);
                }
            }
        }

        // Override the from email address.
//        if ($alias = c('Plugins.VanillaPop.Alias')) {
//            $supportEmail = $this->slug().".$alias@vanillaforums.email";
//            saveToConfig('Garden.Email.SupportAddress', $supportEmail, false);
//        }
    }

    /**
     * Add a checkbox that tells this connection whether or not to synchronize to nodes.
     *
     * @param JsConnectPlugin $sender
     */
    public function jsconnectPlugin_addEdit_render($sender) {
        $sender->addControl(
            'SyncWithHub',
            ['LabelCode' => 'Get the client information from the hub.', 'Control' => 'Checkbox']
        );
    }

    /**
     *
     * @param Gdn_PluginManager $sender
     */
    public function gdn_pluginManager_afterStart_handler($sender) {
        if (Gdn::addonManager()->isEnabled('sitehub', Addon::TYPE_ADDON)) {
            return;
        }

        $path = '/'.trim(Gdn::request()->webRoot(), '/');

        saveToConfig([
            'Garden.Cookie.Name' => self::NODE_COOKIE,
            'Garden.Cookie.Path' => $path
        ], '', false);
    }

    public function gdn_session_end_handler($sender) {
        // Delete the hub cookie when signing out too.
        if (val(self::HUB_COOKIE, $_COOKIE)) {
            Logger::event('session_end_node', Logger::INFO, 'Hub session ending from node.');

            Gdn_CookieIdentity::deleteCookie(SiteNodePlugin::HUB_COOKIE, '/');
        }
    }

    /**
     * Check to see if the role can be edited or not.
     *
     * @param $sender
     * @param $args
     */
    public function roleController_delete_before($sender, $args) {
        $this->checkRoleOverride($sender, $args);
    }

    /**
     * Check to see if the role can be edited or not.
     *
     * @param $sender
     * @param $args
     */
    public function roleController_edit_before($sender, $args) {
        $this->checkRoleOverride($sender, $args);
    }

    /**
     * Allow the override flag to be changed on a role.
     *
     * @param RoleController $sender
     * @param string $roleID
     */
    public function roleController_overrideHub_create($sender, $roleID) {
        $sender->permission('Garden.Settings.Manage');

        $role = $sender->RoleModel->getByRoleID($roleID);
        if (!$role) {
            throw notFoundException('Role');
        }

        /* @var Gdn_Form $form */
        $form = $sender->Form;

        if ($sender->Request->isAuthenticatedPostBack()) {
            $overrideHub = (bool)$form->getFormValue('OverrideHub');
            $sender->RoleModel->setField($roleID, 'OverrideHub', (int)$overrideHub);

            $sender->setData([
                'RoleID' => $roleID,
                'OverrideHub' => $overrideHub
            ]);
            $sender->setRedirectTo('/role');
        } else {
            $form->setData($role);
        }

        $sender->render('hubrole', 'Role', 'plugins/sitenode');
    }

    /**
     * Add hub specific control options to the roles.
     *
     * @param RoleController $sender
     * @param array $args
     */
    public function roleController_beforeRolePermissions_handler($sender, $args) {
        if ($sender->Form->getValue('HubID')) {
            $sender->Data['_ExtendedFields']['OverrideHub'] = [
                'Control' => 'Checkbox',
                'LabelCode' => 'This role can can override settings synchronized with the hub.'
            ];
        }
    }

    /**
     * Make sure that hub managed categories can't be changed.
     *
     * @param SettingsController $sender
     * @param array $args
     * @throws Gdn_UserException
     */
    public function settingsController_editCategory_before($sender, $args) {
        $this->checkCategoryOverride($sender, $args);
    }

    /**
     * Make sure that hub managed categories can't be changed.
     *
     * @param SettingsController $sender
     * @param array $args
     * @throws Gdn_UserException
     */
    public function settingsController_deleteCategory_before($sender, $args) {
        $this->checkCategoryOverride($sender, $args);
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
        $sender->permission('Garden.Settings.Manage');

        if (Gdn::request()->requestMethod() !== 'POST') {
            throw new Gdn_UserException("This resource only accepts POST.", 405);
        }

        $this->syncNode();
        $sender->render('blank');
    }

    /**
     * @param UtilityController $sender
     */
    public function utilityController_syncNodeInfo_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        $info = Gdn::userMetaModel()->getUserMeta(0, 'siteNode.dateLastSync', []);
        $date = array_pop($info);

        $sender->setData('Ready', (bool)$date);
        $sender->setData('DateLastSync', $date);

        $sender->render('blank');
    }


    /**
     * Make a call to a Site Hub spoofing as one of its nodes.
     *
     * @param UtilityController $sender
     * @throws Gdn_UserException For missing config settings.
     */
    public function utilityController_spoofSyncNode_create(UtilityController $sender) {
        $sender->permission('Garden.Settings.Manage');
        if (!c('Hub.Spoof.Enabled')) {
            throw new Gdn_UserException('This feature is not configured. `Hub.Spoof.Enabled` needs to be `true`.');
        }
        $sender->setData('title', 'Synchronize From a Hub');
        $sender->setData('messageclass', 'danger');
        $sender->setData('instructions', 'Be careful you are about to overwrite a lot of data in your local database.');
        $sender->setData('configuredHubURL', c('Hub.Spoof.Values.hubURL', 'You have not configured a forum from which to sync. (Hub.Spoof.Values.hubURL)'));
        $sender->setData('configuredSpoofSlug', c('Hub.Spoof.Values.spoofSlug'), 'You have not configured the node slug you wish to imitate. (Hub.Spoof.Values.spoofSlug)');
        $sender->setData('configuredSpoofToken', c('Hub.Spoof.Values.spoofToken'), 'You have not configured API V1 Token of the hube site. (Hub.Spoof.Values.spoofToken)');
        $spoofValues = c('Hub.Spoof.Values', []);
        $params = $this->generateParams($spoofValues);
        if ($sender->Form->AuthenticatedPostBack()) {
            if (!$spoofValues['hubURL']) {
                throw new Gdn_UserException('You need to provide a URL of a hub site that you want to sync from in Hub.Spoof.Values.hubURL');
            }

            if (!$spoofValues['spoofSlug']) {
                throw new Gdn_UserException('You need to provide the slug of the node site that you want to sync in Hub.Spoof.Values.spoofSlug');
            }

            if (!$spoofValues['spoofToken']) {
                throw new Gdn_UserException('You need to provide API V1 token of the hub site that you want to sync from in Hub.Spoof.Values.spoofToken');
            }

            $this->syncNode($params);
            $sender->setData('instructions', 'Sychronized successfully!');
            $sender->setData('messageclass', 'success');
        }

        /**
         * TODO loop through the config and display what is configured to happen during syncing.
         */
        if ($params) {
            $config = $this->hubApi('/multisites/nodeconfig.json', 'GET', $params + ['from' => $this->slug()], true);
            $sender->setData('hubConfig', $config);
        }
        $sender->render('spoofsyncnode', '', 'plugins/sitenode');
    }

    /**
     * Get the parameters from the confg and arrange them into a nice array.
     *
     * @param array $spoofValues Configured values from that imitate a node's values.
     * @return array
     */
    private function generateParams($spoofValues): array {
        $this->hubUrl = $spoofValues['hubURL'];
        $localhost = $spoofValues['localhost'] ?? false;
        $params = [];
        if ($spoofValues['hubURL'] && $spoofValues['spoofToken'] && $spoofValues['spoofSlug']) {
            $params = ['from' => $spoofValues['spoofSlug'], 'spoofToken' => $spoofValues['spoofToken'], 'localhost' => $localhost, 'mode' => 'debug'];
        }
        return  $params;
    }


    public function cleanspeak_init_handler($sender) {
        $siteID = Infrastructure::site('siteid');
        if (!$siteID) {
            throw new Gdn_UserException('Error getting Site ID for cleanspeak plugin.');
        }
        $sender->uuidSeed = [$siteID, 0, 0, 0];
    }

    /**
     * Routine for ending the node sync process.
     */
    private function syncNodeSuccess(): void {
        Gdn::config()->shutdown();
        Logger::event('syncnode_complete', Logger::INFO, "The node has completed it's sync.");
    }

}
