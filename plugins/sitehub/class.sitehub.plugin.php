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

    /// Event Handlers ///


    public function base_getAppSettingsMenuItems_handler($sender) {
        /* @var SideMenuModule */
        $menu = $sender->EventArguments['SideMenu'];

        $menu->AddItem('sitehub', T('Site Hub'), FALSE, ['After' => 'Forum']);
        $menu->AddLink('sitehub', T('Sites'), '/multisites', 'Garden.Settings.Manage');
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
