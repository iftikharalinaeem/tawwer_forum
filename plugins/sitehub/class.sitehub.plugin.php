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

    const EMAIL_MATCH_SUBJECT = 'subject';
    const EMAIL_MATCH_TO = 'to';

    /**
     * @var string The regular expression stub to be used to match a node/category from an incoming email.
     */
    protected $emailRegex;

    /**
     * @var string What part of an email to match to find the node/category.
     */
    protected $emailMatch;

    /// Methods ///

    /**
     * Initialize a new instance of the {@link SiteHubPlugin} .
     */
    public function __construct() {
        $this->emailRegex = c('SiteHub.EmailRegex', '(?:(?<category>[a-z0-9_-]+)\.)?(?<node>[a-z0-9_-]+)');
        $this->emailMatch = c('SiteHub.EmailMatch', self::EMAIL_MATCH_TO);
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
            ->column('UrlCode', 'varchar(255)', true)
            ->column('HubID', 'int', true)
            ->column('DateLastSync', 'datetime', false, 'index')
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

    /**
     * Get the regular expression used to extract nodes/categories from email addresses.
     *
     * @return string Returns a regular expression as a string.
     */
    public function getEmailAddressRegex() {
        return "`{$this->emailRegex}\.[a-z0-9_-](?:\+(?<args>[^@]+))?@`i";
    }

    /**
     * Get the regular expression used to extract nodes/categories from an email subject.
     *
     * @return string string Returns a regular expression as a string.
     */
    public function getEmailSubjectRegex() {
        return "`^{$this->emailRegex}`i";
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

        $valid = false;
        switch ($this->emailMatch) {
            case self::EMAIL_MATCH_SUBJECT:
                // Check for a subject that uses square brackets first.
                if (stringBeginsWith($subject, '[')) {
                    $subject = substr($subject, 1);
                    if ($pos = strpos($subject, ']')) {
                        $subject = trim(substr($subject, 0, $pos));
                    }
                }

                $sender->setData('Tested', $subject);
                $valid = preg_match($this->getEmailSubjectRegex(), $subject, $matches);
                break;
            case self::EMAIL_MATCH_TO:
                $sender->setData('Tested', $email);
                $valid = preg_match($this->getEmailAddressRegex(), $email, $matches);
                break;
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
