<?php if (!defined('APPLICATION')) exit;

$PluginInfo['minisites'] = array(
    'Name'        => "Minisites",
    'Description' => "Allows you to use categories as virtual mini forums for multilingual or multi-product communities.",
    'Version'     => '1.0.0-alhpa',
    'Author'      => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl'   => 'https://vanillaforums.com',
    'License'     => 'Proprietary'
);


class MinisitesPlugin extends Gdn_Plugin {
    /// Properties ///

    /// Methods ///

    /**
     */
    public function setup() {
        $this->structure();
    }

    public function structure() {
        Gdn::Structure()
            ->Table('Minisite')
            ->PrimaryKey('MinisiteID')
            ->Column('Name', 'varchar(255)')
            ->Column('Folder', 'varchar(255)', false, 'unique.Folder')
            ->Column('CategoryID', 'int', true)
            ->Column('Locale', 'varchar(20)')
            ->Column('DateInserted', 'datetime')
            ->Column('InsertUserID', 'int')
            ->Column('DateUpdated', 'datetime', true)
            ->Column('UpdateUserID', 'int', true)
            ->Column('Attributes', 'text', true)
            ->Column('Sort', 'smallint', '1000')
            ->Column('IsDefault', 'tinyint(1)', true, 'unique.IsDefault')
            ->Set();
    }

    /**
     * Initialize the environment on a mini site.
     * @param array $site The site to set.
     */
    protected function initializeSite(array $site) {
        // Set the locale from the site.
        if ($site['Locale'] !== Gdn::Locale()->Current()) {
            Gdn::Locale()->Set($site['Locale'], Gdn::ApplicationManager()->EnabledApplicationFolders(), Gdn::PluginManager()->EnabledPluginFolders());
        }

        // Set the default routes.
        if ($site['CategoryID']) {
            $category = CategoryModel::Categories($site['CategoryID']);
            Gdn::Router()->SetRoute('categories$', ltrim(CategoryUrl($category, '', '/'), '/'), 'Internal', false);

            $defaultRoute = Gdn::Router()->GetRoute('DefaultController');
            if ($defaultRoute['Destination'] === 'categories') {
                Gdn::Router()->SetRoute('DefaultController', ltrim(CategoryUrl($category, '', '/'), '/'), 'Temporary', false);
            }
        }

        MinisiteModel::setCurrent($site);
    }

    /// Event Handlers ///

    public function base_render_before($sender) {
        // Add the alternate urls to the current crop of sites.
        MinisiteModel::addAlternativeUrls();
    }

    public function base_getAppSettingsMenuItems_handler($sender) {
        /* @var SideMenuModule */
        $menu = $sender->EventArguments['SideMenu'];
        $menu->AddLink('Forum', T('Minisites'), '/minisites', 'Garden.Settings.Manage', ['After' => 'vanilla/settings/managecategories']);
    }

    public function Gdn_Dispatcher_AppStartup_Handler() {
        SaveToConfig(
            [
                'Vanilla.Categories.NavDepth' => 1
            ],
            '',
            false
        );

        $parts = explode('/', trim(Gdn::Request()->Path(), '/'), 2);
        $root = $parts[0];
        $path = val(1, $parts, '');

        // Look the root up in the mini sites.
        $site = MinisiteModel::getSite($root);
        if ($site) {
            Gdn::Request()->Path($path);
            Gdn::Request()->WebRoot($root);
            Gdn::Request()->AssetRoot('/');

            $this->initializeSite($site);
        } else {
            $defaultSite = MinisiteModel::getDefaultSite();
            if ($defaultSite) {

                $url = '/'.$defaultSite['Folder'].rtrim('/'.Gdn::Request()->Path(), '/');
                redirectUrl($url, Debug() ? 302 : 301);
            }
        }
    }

    /**
     * @param Smarty $sender
     */
    public function Gdn_Smarty_Init_Handler($sender) {
        $sender->assign('Minisite', MinisiteModel::getCurrent());
    }

    /**
     * @return MinisitesPlugin
     */
    public static function instance() {
        return Gdn::PluginManager()->GetPluginInstance(__CLASS__, Gdn_PluginManager::ACCESS_CLASSNAME);
    }
}
