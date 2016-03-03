<?php if (!defined('APPLICATION')) exit;

$PluginInfo['sitenode'] = array(
    'Name'        => "Multisite Node",
    'Version'     => '1.1.1',
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
            ->Table('UserAuthenticationProvider')
            ->Column('SyncWithHub', 'tinyint(1)', '1')
            ->Set();
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

        $category = CategoryModel::Categories($categoryID);
        if (!val('HubID', $category)) {
            return;
        }

        if ($sender->Request->IsAuthenticatedPostBack()) {
            // Roles from the hub cannot be edited/deleted under any circumstance.
            throw new Gdn_UserException('This category is administered in the hub.', 400);
        } elseif ($sender->DeliveryType() === DELIVERY_TYPE_DATA) {
            return;
        } else {
            $sender->AddSideMenu('');
            $sender->Render('hubcategory', 'settings', 'plugins/sitenode');
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

        $role = $sender->RoleModel->GetByRoleID($roleID);
        if (!val('HubID', $role)) {
            return;
        }

        $sender->Form->AddHidden('HubID', val('HubID', $role));
        if (!$role || val('OverrideHub', $role)) {
            return;
        }

        if ($sender->Request->IsAuthenticatedPostBack()) {
            // Roles from the hub cannot be edited/deleted under any circumstance.
            throw new Gdn_UserException('This role is administered in the hub.', 400);
        } elseif ($sender->DeliveryType() === DELIVERY_TYPE_DATA) {
            return;
        } else {
            $sender->AddSideMenu('');
            $sender->SetData('RoleID', $roleID);
            $sender->Render('hubrole', 'role', 'plugins/sitenode');
            die();
        }
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
            'Method' => $method,
            'Timeout' => 100,
        ], $params, null, $headers);

        if (strpos($request->ContentType, 'application/json') !== false) {
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
            Logger::event('syncnode_skip', Logger::INFO, "The hub told us not to sync.");
            return;
        }

        $siteID = valr('Multisite.MultisiteID', $config);

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
        SaveToConfig($saveConfig);

        // Synchronize the roles.
        Trace('Synchronizing roles.');
        $roleMap = $this->syncRoles(val('Roles', $config, []));

        // Synchronize the categories.
        Trace('Synchronizing categories.');
        $this->syncCategories(val('Categories', $config, []), val('OtherCategories', $config, []), $roleMap);

        // Synchronize the authenticators.
        Trace('Synchronizing authenticators.');
        $this->syncAuthenticators(val('Authenticators', $config, []));

        // Push the categories.
        $this->pushCategories();

        $this->FireEvent('AfterSync');

        // Tell the hub that we've synchronized.
        $now = Gdn_Format::ToDateTime();
        Gdn::UserMetaModel()->SetUserMeta(0, 'siteNode.dateLastSync', $now);
        $result = $this->hubApi("/multisites/$siteID.json", 'POST', ['DateLastSync' => $now, 'Status' => 'active'], true);

        Gdn::Config()->Shutdown();
        Logger::event('syncnode_complete', Logger::INFO, "The node has completed it's sync.");
    }

    public function syncAuthenticators(array $authenticators) {
        $model = new Gdn_AuthenticationProviderModel();

        foreach ($authenticators as $row) {
            // Look for an existing authenticator.
            $current = $model->GetWhere(['AuthenticationKey' => $row['AuthenticationKey']])->FirstRow();
            if (!$current || val('SyncWithHub', $current)) {
                unset($row['SyncToNodes']);
                $row['SyncWithHub'] = true;
                $model->Save($row);
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
        if (!$sender->Request->IsAuthenticatedPostBack(true)) {
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

        $r = $this->hubApi('/multisites/syncnodecategories.json', 'POST', $post);
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

            if (!empty($permissions)) {
                $category['CustomPermissions'] = true;
                foreach ($permissions as $i => $permissionRow) {
                    if (empty($roleMap[$permissionRow['RoleID']])) {
                        continue; // role disconnected
                    }

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
                $category['Sort'] = $sort;

                $categoryID = $categoryModel->Save($category);
                if ($categoryID) {
                    $categoryMap[$hubID] = $categoryID;
                }
                $categoryModel->Validation->Results(true);
            }

            foreach ($permissions as $permissionRow) {
                TouchValue('JunctionID', $permissionRow, $categoryID);
                Gdn::PermissionModel()->Validation->Results(true);
                $result = Gdn::PermissionModel()->Save($permissionRow);
                Trace(Gdn::PermissionModel()->Validation->ResultsText());
            }
        }

        trace($categoryMap, 'categoryMap');

        // Remove the synchronization from other categories.
        foreach ($otherCategories as $hubID) {
            $categories = $categoryModel->getWhereCache(['HubID' => $hubID]);

            foreach ($categories as $categoryID => $category) {
                $categoryModel->setField($categoryID, 'HubID', null);
                trace("Removing hub ID for category $categoryID.");
            }
        }

        // Find categories that have been removed from the hub.
        $toDelete = Gdn::SQL()
            ->Select('CategoryID')
            ->From('Category')
            ->WhereNotIn('CategoryID', $categoryMap)
            ->Where('HubID is not null')
            ->Where('OverrideHub', 0)
            ->Get()->ResultArray();
        $deleteIDs = array_column($toDelete, 'CategoryID');

        foreach ($deleteIDs as $categoryID) {
            if ($categoryID <= 0) {
                cotinue;
            }
            $category = (object)CategoryModel::Categories($categoryID);
            $categoryModel->Delete($category, -1);
        }

        // Update the sort order of categories that aren't from the hub.
        Gdn::SQL()->Update('Category')
            ->Set('Sort', "Sort + $sort", false, false)
            ->Where('CategoryID >', 0)
            ->WhereNotIn('CategoryID', $categoryMap)
            ->Put();

        // Rebuild the tree with from the new sort.
        $categoryModel->RebuildTree(true);
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
            if (val('OverrideHub', $currentRole)) {
                continue;
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
                Trace(Gdn::PermissionModel()->Validation->Results(true));
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
            // Check the cookie expiry here.
            $hubCookie = explode('|', val(self::HUB_COOKIE, $_COOKIE));
            $expiry = val(4, $hubCookie);
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
                SaveToConfig('Garden.SSO.SyncRoles', true, false);
                SaveToConfig('Garden.SSO.SynchRoles', true, false); // backwards compat

                // Translate the roles.
                $roles = val('Roles', $user);
                if (is_array($roles)) {
                    $roleModel = new RoleModel();
                    $roleHubIDs = array_column($roles, 'HubID');
                    $roles = $roleModel->GetWhere(['HubID' => $roleHubIDs])->ResultArray();
                    $user['Roles'] = array_column($roles, 'RoleID');
                    $user['RoleID'] = $user['Roles'];
                }

                // Fire an event so that plugins can determine access etc.
                $this->EventArguments['User'] =& $user;
                $this->FireEvent('hubSSO');

                Trace($user, 'hubSSO');

                $user_id = Gdn::userModel()->connect($user['UniqueID'], self::PROVIDER_KEY, $user);
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
            } catch (Exception $ex) {
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

        // Override the from email address.
        if ($alias = C('Plugins.VanillaPop.Alias')) {
            $supportEmail = $this->slug().".$alias@email.vanillaforums.com";
            SaveToConfig('Garden.Email.SupportAddress', $supportEmail, false);
        }
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
            ob_start();
            debug_print_backtrace();
            $trace = ob_end_clean();
            Logger::event('session_end_node', Logger::INFO, 'Hub session ending from node.', ['trace' => $trace]);

            Gdn_CookieIdentity::DeleteCookie(SiteNodePlugin::HUB_COOKIE, '/');
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
        $sender->Permission('Garden.Settings.Manage');

        $role = $sender->RoleModel->getByRoleID($roleID);
        if (!$role) {
            throw NotFoundException('Role');
        }

        /* @var Gdn_Form $form */
        $form = $sender->Form;

        if ($sender->Request->IsAuthenticatedPostBack()) {
            $overrideHub = (bool)$form->GetFormValue('OverrideHub');
            $sender->RoleModel->SetField($roleID, 'OverrideHub', (int)$overrideHub);

            $sender->SetData([
                'RoleID' => $roleID,
                'OverrideHub' => $overrideHub
            ]);
            $sender->RedirectUrl = Url('/role');
        } else {
            $form->SetData($role);
        }

        $sender->Render('hubrole', 'Role', 'plugins/sitenode');
    }

    /**
     * Add hub specific control options to the roles.
     *
     * @param RoleController $sender
     * @param array $args
     */
    public function roleController_beforeRolePermissions_handler($sender, $args) {
        if ($sender->Form->GetValue('HubID')) {
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
        $sender->Permission('Garden.Settings.Manage');

        if (Gdn::Request()->RequestMethod() !== 'POST') {
            throw new Gdn_UserException("This resource only accepts POST.", 405);
        }

        $this->syncNode();
        $sender->Render('blank');
    }

    public function utilityController_syncNodeInfo_create($sender) {
        $sender->Permission('Garden.Settings.Manage');

        $info = Gdn::UserMetaModel()->GetUserMeta(0, 'siteNode.dateLastSync', []);
        $date = array_pop($info);

        $sender->SetData('Ready', (bool)$date);
        $sender->SetData('DateLastSync', $date);

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
