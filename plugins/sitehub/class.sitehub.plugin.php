<?php if (!defined('APPLICATION')) exit;

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

    const EMAIL_NODE_REGEX = '(?:(?<category>[a-z0-9-]+)\.)?(?<node>[a-z0-9-]+)';
    const EMAIL_CATEGORY_REGEX = '(?<category>[a-z0-9-]+)(?:\.(?<node>[a-z0-9-]+))?';

    /**
     * @var bool Whether to match categories before nodes in email routing.
     */
    protected $emailMatchCategories;

    /// Methods ///

    /**
     * Initialize a new instance of the {@link SiteHubPlugin} .
     */
    public function __construct() {
        $this->emailMatchCategories = c('SiteHub.EmailMatchCategories', false);
    }

    public function setup() {
        $this->structure();
    }

    public function structure() {
        gdn::structure()
            ->table('Multisite')
            ->primaryKey('MultisiteID')
            ->column('Name', 'varchar(255)', false)
            ->column('Slug', 'varchar(50)', false, 'unique.slug')
            ->column('Url', 'varchar(191)', false, 'unique.url')
            ->column('Locale', 'varchar(20)', true, 'index')
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

        gdn::structure()
            ->table('Role')
            ->column('HubSync', ['settings', 'membership'], true)
            ->set();

        gdn::structure()
            ->table('Category')
            ->column('HubSync', ['', 'settings'], 'settings')
            ->set();

        gdn::structure()
            ->table('UserAuthenticationProvider')
            ->column('SyncToNodes', 'tinyint(1)', '0')
            ->set();

        touchConfig('Badges.Disabled', true);

        // This table contains a mirror of all of the categories on all of the nodes.
        gdn::structure()
            ->table('NodeCategory')
            ->primaryKey('NodeCategoryID')
            ->column('MultisiteID', 'int', false, 'key')
            ->column('CategoryID', 'int', false)
            ->column('Name', 'varchar(255)')
            ->column('UrlCode', 'varchar(191)', true)
            ->column('HubID', 'int', true)
            ->column('DateLastSync', 'datetime', false, 'index')
            ->set();

        // This table contains a mirror of all of the subcommunities on all of the nodes (if any).
        gdn::structure()
            ->table('NodeSubcommunity')
            ->primaryKey('NodeSubcommunityID')
            ->column('MultisiteID', 'int', false, 'key')
            ->column('SubcommunityID', 'int', false)
            ->column('Name', 'varchar(255)')
            ->column('Folder', 'varchar(255)')
            ->column('CategoryID', 'int', false)
            ->column('Locale', 'varchar(20)')
            ->column('IsDefault', 'tinyint(1)', '0')
            ->column('DateLastSync', 'datetime', false, 'index')
            ->set();
    }

    /**
     * Check to see if a valid sso token was passed through the header.
     */
    public function checkSSO() {
//        if (Gdn::session()->isValid()) {
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
            $userID = Gdn::userModel()->getSystemUserID();
            if ($userID) {
                Gdn::session()->start($userID, false, false);
                Gdn::session()->validateTransientKey(true);
            }
        }
    }

    /**
     * Get the regex used to match emails.
     *
     * @return string Returns a part of a regex as a string.
     */
    public function getEmailRegex() {
        if ($this->emailMatchCategories) {
            return self::EMAIL_CATEGORY_REGEX;
        } else {
            return self::EMAIL_NODE_REGEX;
        }
    }

    /**
     * Get the regular expression used to extract nodes/categories from email addresses.
     *
     * @return string Returns a regular expression as a string.
     */
    public function getEmailAddressRegex() {
        $regex = $this->getEmailRegex();

        return "`^\s*$regex\.[a-z0-9-]+(?:\+(?<args>[^@]+))?@`i";
    }

    /**
     * Get the regular expression used to extract nodes/categories from an email subject.
     *
     * @return string string Returns a regular expression as a string.
     */
    public function getEmailSubjectRegex() {
        $regex = $this->getEmailRegex();

        return "`^\s*{$regex}`i";
    }

    /// Event Handlers ///


    public function base_getAppSettingsMenuItems_handler($sender) {
        /* @var SideMenuModule */
        $menu = $sender->EventArguments['SideMenu'];

        $menu->addItem('sitehub', t('Site Hub'), FALSE, ['After' => 'Forum']);
        $menu->addLink('sitehub', t('Sites'), '/multisites', 'Garden.Settings.Manage');
    }

    /**
     * An endpoint that lists the communities on the nodes, by language.
     *
     * @param CategoriesController $sender
     * @param string $locale The locale being looked at.
     */
    public function categoriesController_sites_create($sender, $locale = '') {
        if (empty($locale)) {
            $locale = Gdn::locale()->current();
        } elseif ($locale !== Gdn::locale()->current()) {
            Gdn::locale()->set($locale);
        }

        $sites = MultisiteModel::instance()->getNodeSites($locale);
        $sender->setData('Sites', $sites);

        $sender->title(t(c('Garden.SitesTitle', c('Garden.Title', 'Communities'))), '');
        $sender->render('Sites', 'Categories', 'plugins/sitehub');
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
     * Tell the email router which node to route an email to.
     *
     * @param Gdn_Controller $sender The controller dispatching this endpoint.
     */
    public function utilityController_emailRoute_create($sender) {
        if (!$sender->Request->isPostBack()) {
            throw forbiddenException('GET');
        }

        $email = $sender->Request->post('Email');
        $subject = trim($sender->Request->post('Subject'));

        // Pass the data to the dispatcher for errors.
        Gdn::dispatcher()
            ->passData('Email', $email)
            ->passData('Subject', $subject);

        // Look for a match against the email address first.
        $valid = preg_match($this->getEmailAddressRegex(), $email, $matches);
        if (!$valid || (empty($matches['node']) && empty($matches['category']))) {
            // The email address is not valid so look at the subject.
            $valid = preg_match($this->getEmailSubjectRegex(), $subject, $matches);

            if ($valid) {
                $sender->setData('Matched', $subject);
            }
        } else {
            $sender->setData('Matched', $email);
        }

        if ($valid) {
            $nodeSlug = val('node', $matches, null);
            $categorySlug = val('category', $matches, null);

            if ($nodeSlug) {
                $node = MultisiteModel::instance()->getWhere(['Slug' => $nodeSlug])->firstRow(DATASET_TYPE_ARRAY);
            }
            if ($nodeSlug !== null && empty($node)) {
                throw notFoundException('Site');
            }

            if ($categorySlug) {
                $where = ['UrlCode' => $categorySlug];

                if ($node) {
                    $where['MultisiteID'] = $node['MultisiteID'];
                }
                $category = Gdn::sql()->getWhere('NodeCategory', $where)->firstRow(DATASET_TYPE_ARRAY);

                if ($category && empty($node)) {
                    $node = MultisiteModel::instance()->getID($category['MultisiteID']);
                }
            }

            if (!empty($node)) {
                $sender->setData('Url', $node['FullUrl']);
            }
            if (!empty($category)) {
                $sender->setData('Data', ['CategoryID' => $category['CategoryID']]);
            }
        } else {
            throw notFoundException('@'.sprintf('The email %s did not match.', $this->emailMatch));
        }

        if (!$sender->data('Url')) {
            throw notFoundException('Site');
        }

        $sender->render('Blank');
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
        saveToConfig([
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
        if (!Gdn::session()->isValid()) {
            throw notFoundException('User');
        }

        // Get the site that the user is trying to sign in to.
        $site = false;
        if ($from) {
            $site = MultisiteModel::instance()->getWhere(['slug' => $from])->firstRow(DATASET_TYPE_ARRAY);
            $sender->EventArguments['Site'] =& $site;
        }
        // Make sure the user is signing in to a valid site within the hub.
        if (!$site) {
            throw notFoundException('Site');
        }

        // Get the currently signed in user.
        if (!Gdn::session()->User) {
            throw notFoundException('User');
        }
        $ssoUser = arrayTranslate((array)Gdn::session()->User, ['UserID' => 'UniqueID', 'Name', 'Email', 'Banned', 'Photo', 'PhotoUrl']);
        $ssoUser['Photo'] = $ssoUser['PhotoUrl'];

        // Get the user's role.
        $roles = Gdn::userModel()->getRoles(Gdn::session()->UserID)->resultArray();
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
        $sender->EventArguments['Session'] =& Gdn::session();
        $sender->fireEvent('hubSSO');

        $sender->Data = ['User' => $ssoUser];
        saveToConfig('Api.Clean', false, false);
        $sender->render('Blank', 'Utility', 'Dashboard');
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
