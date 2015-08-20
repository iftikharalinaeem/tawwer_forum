<?php if (!defined('APPLICATION')) exit;

$PluginInfo['sitehub'] = array(
    'Name'        => "Multisite Hub",
    'Description' => 'The Multi-site Hub plugin provides functionality to manage a multi-site cluster.',
    'Version'     => '1.1.0',
    'Author'      => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl'   => 'http://vanillaforums.com',
    'License'     => 'Proprietary',
    'RequiredPlugins' => array(
        'SimpleAPI' => '1.0',
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
            ->column('SiteID', 'int', true)
            ->column('Status', ['pending', 'building', 'active', 'error', 'deleting'], 'pending')
            ->column('DateStatus', 'datetime')
            ->column('DateInserted', 'datetime')
            ->column('InsertUserID', 'int')
            ->column('DateUpdated', 'datetime', true)
            ->column('UpdateUserID', 'int', true)
            ->column('Sync', 'tinyint', '1')
            ->column('DateLastSync', 'datetime', true)
            ->column('Attributes', 'text', true)
            ->set();

        Gdn::Structure()
            ->table('Role')
            ->column('HubSync', ['settings', 'membership'], true)
            ->set();

        Gdn::Structure()
            ->table('Category')
            ->column('HubSync', ['', 'settings'], 'settings')
            ->set();

        Gdn::Structure()
            ->Table('UserAuthenticationProvider')
            ->Column('SyncToNodes', 'tinyint(1)', '0')
            ->Set();

        TouchConfig('Badges.Disabled', true);
    }

    /**
     * Check to see if a valid sso token was passed through the header.
     */
    public function checkSSO() {
//        if (Gdn::Session()->IsValid()) {
//            return;
//        }

        // First look for a header.
        $token = '';
        if ($auth = val('HTTP_AUTHORIZATION', $_SERVER, '')) {
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

    /// Event Handlers ///


    public function base_getAppSettingsMenuItems_handler($sender) {
        /* @var SideMenuModule */
        $menu = $sender->EventArguments['SideMenu'];

        $menu->AddItem('sitehub', T('Site Hub'), FALSE, ['After' => 'Forum']);
        $menu->AddLink('sitehub', T('Sites'), '/multisites', 'Garden.Settings.Manage');
    }

    /*
    *
    * @param Gdn_Controller $Sender
    */
    public function settingsController_addEditCategory_handler($sender) {
        $sender->Data['_ExtendedFields']['HubSync'] = [
            'Control' => 'RadioList',
            'Description' => 'Specify how this category synchronizes to the node sites.',
            'Items' => ['' => 'None', 'settings' => 'Settings']
        ];
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
     * @param string $from The slug of the site that is trying to sso.
     * @throws Gdn_UserException Throws an exception when the user isn't signed in.
     */
    public function profileController_hubSSO_create($sender, $from) {
        if (!Gdn::Session()->IsValid()) {
            throw NotFoundException('User');
        }

        // Get the site that the user is trying to sign in to.
        $site = false;
        if ($from) {
            $site = MultisiteModel::instance()->getWhere(['slug' => $from])->FirstRow(DATASET_TYPE_ARRAY);
            $sender->EventArguments['Site'] =& $site;
        }
        // Make sure the user is signing in to a valid site within the hub.
        if (!$site) {
            throw NotFoundException('Site');
        }

        // Get the currently signed in user.
        if (!Gdn::Session()->User) {
            throw NotFoundException('User');
        }
        $ssoUser = arrayTranslate((array)Gdn::Session()->User, array('UserID' => 'UniqueID', 'Name', 'Email', 'Banned', 'Photo', 'PhotoUrl'));
        $ssoUser['Photo'] = $ssoUser['PhotoUrl'];

        // Get the user's role.
        $roles = Gdn::UserModel()->GetRoles(Gdn::Session()->UserID)->ResultArray();
        $allRoles = [];
        $ssoUser['Roles'] = [];
        foreach ($roles as $role) {
            $allRoles[] = $role['Name'];
            if (val('HubSync', $role)) {
                $ssoUser['Roles'][] = [
                    'HubID' => $role['RoleID'],
                    'Name' => $role['Name']
                ];
            }
        }
        $sender->EventArguments['AllRoles'] = $allRoles;

        $sender->EventArguments['User'] =& $ssoUser;
        $sender->EventArguments['Session'] =& Gdn::Session();
        $sender->FireEvent('hubSSO');

        $sender->Data = array('User' => $ssoUser);
        SaveToConfig('Api.Clean', false, false);
        $sender->Render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Add a checkbox that tells this connection whether or not to synchronize to nodes.
     *
     * @param JsConnectPlugin $sender
     */
    public function jsconnectPlugin_addEdit_render($sender) {
        $sender->addControl(
            'SyncToNodes',
            ['LabelCode' => 'Synchronize the client information to the nodes.', 'Control' => 'Checkbox']
        );
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
