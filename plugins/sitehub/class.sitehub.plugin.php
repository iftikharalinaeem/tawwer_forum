<?php if (!defined('APPLICATION')) exit;

$PluginInfo['sitehub'] = array(
    'Name'        => "Multisite Hub",
    'Description' => 'The Multi-site Hub plugin provides functionality to manage a multi-site cluster.',
    'Version'     => '1.0.0',
    'Author'      => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl'   => 'http://vanillaforums.com',
    'License'     => 'Proprietary',
    'RequiredPlugins' => array(
        'SimpleApi' => '1.0',
    ),
);

/**
 * The Multisite Hub plugin provides functionality to manage a multi-site cluster.
 *
 * @author    Todd Burry <todd@vanillaforums.com>
 * @copyright 2014 (c) Todd Burry
 * @license   Proprietary
 * @package   MultisiteHub
 * @since     1.0.0
 */
class SiteHubPlugin extends Gdn_Plugin {
    /// Constants ///

    const HUB_COOKIE = 'vf_hub_ENDTX';
    const NODE_COOKIE = 'vf_node_ENDTX';

    /// Methods ///

    public function setup() {
        $this->structure();
    }

    public function structure() {
        gdn::structure()
            ->table('Multisite')
            ->primaryKey('MultisiteID')
            ->column('Name', 'varchar(255)', false)
            ->column('Slug', 'varchar(50)', false, 'unique.slug')
            ->column('Url', 'varchar(255)', false, 'unique.url')
            ->column('Status', ['pending', 'building', 'active', 'error'], 'pending')
            ->column('DateStatus', 'datetime')
            ->column('DateInserted', 'datetime')
            ->column('InsertUserID', 'int')
            ->column('DateUpdated', 'datetime', true)
            ->column('UpdateUserID', 'int', true)
            ->column('Attributes', 'text', true)
            ->set();

        Gdn::Structure()
            ->table('Role')
            ->column('HubSync', ['settings', 'membership'], true)
            ->set();
    }

    /**
     * Check to see if a valid sso token was passed through the header.
     */
    public function checkSSO() {
//        if (Gdn::Session()->IsValid()) {
//            return;
//        }

        // First look for a header.
        if ($auth = val('HTTP_AUTHENTICATION', $_SERVER, '')) {
            if (preg_match('`^token\s+([^\s]+)`i', $auth, $m)) {
                $token = $m[1];
            }
        } else {
            $token = Gdn::Request()->Get('access_token');
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
     * Make an api call out to a node..
     *
     * @param string $node The slug of the node to call out to.
     * @param string $path The path to the api endpoint.
     * @param string $method The http method to use.
     * @param array $params The parameters for the request, either get or post.
     * @return mixed Returns the decoded response from the request.
     * @throws Gdn_UserException Throws an exception when the api endpoint returns an error response.
     */
    public function nodeApi($node, $path, $method = 'GET', $params = []) {
        $node = trim($node, '/');

        Trace("api: $method /$node$path");

        $headers = [];

        // Kludge for osx that doesn't allow host files.
        $baseUrl = MultisiteModel::instance()->siteUrl($node, true);
        $urlParts = parse_url($baseUrl);

        if ($urlParts['host'] === 'localhost' || StringEndsWith($urlParts['host'], '.lc')) {
            $headers['Host'] = $urlParts['host'];
            $urlParts['host'] = '127.0.0.1';
        }

        $url = rtrim(http_build_url($baseUrl, $urlParts), '/').'/api/v1/'.ltrim($path, '/');

        if ($access_token = Infrastructure::clusterConfig('cluster.loader.apikey', '')) {
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

    /// Event Handlers ///


    public function base_getAppSettingsMenuItems_handler($sender) {
        /* @var SideMenuModule */
        $menu = $sender->EventArguments['SideMenu'];

        $menu->AddItem('sitehub', T('Site Hub'), FALSE, ['After' => 'Forum']);
        $menu->AddLink('sitehub', T('Sites'), '/multisites', 'Garden.Settings.Manage');
    }

    /**
     * @param Gdn_Dispatcher $sender
     */
    public function gdn_dispatcher_appStartup_handler($sender) {
        $this->checkSSO();
    }

    /**
     * @param Gdn_PluginManager $sender
     */
    public function gdn_pluginManager_afterStart_handler($sender) {
        SaveToConfig([
            'Garden.Cookie.Name' => self::HUB_COOKIE,
            'Garden.Cookie.Path' => '/',
        ], '', false);
    }

    /**
     * Gets information about the currently signed in user suitable for hub SSO calls.
     *
     * @param ProfileController $sender
     * @throws Gdn_UserException Throws an exception when the user isn't signed in.
     */
    public function profileController_hubSSO_create($sender) {
        if (!Gdn::Session()->IsValid()) {
            throw NotFoundException('User');
        }

        // Get the currently signed in user.
        $user = Gdn::UserModel()->GetID(Gdn::Session()->UserID, DATASET_TYPE_ARRAY);
        if (!$user) {
            throw NotFoundException('User');
        }
        $ssoUser = arrayTranslate($user, array('UserID', 'Name', 'Email', 'Banned', 'Photo', 'PhotoUrl'));
        $ssoUser['Photo'] = $ssoUser['PhotoUrl'];

        $roles = Gdn::UserModel()->GetRoles($user['UserID'])->ResultArray();
        foreach ($roles as $role) {
            if (val('HubSync', $role)) {
                $ssoUser['Roles'][] = $role['Name'];
            }
        }

        $sender->EventArguments['User'] =& $ssoUser;
        $sender->EventArguments['Session'] =& $user;
        $sender->FireEvent('hubSSO');

        $sender->Data = array('User' => $ssoUser);
        SaveToConfig('Api.Clean', false, false);
        $sender->Render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Add hub specific control options to the roles.
     *
     * @param RoleController $sender
     * @param array $args
     */
    public function roleController_beforeRolePermissions_handler($sender, $args) {
        $sender->Data['_ExtendedFields']['HubSync'] = [
            'Control' => 'RadioList',
            'Description' => 'Specify how this role synchronizes to the node sites.',
            'Items' => ['' => 'None', 'settings' => 'Settings', 'membership' => 'Settings & Membership']
        ];
    }
}
