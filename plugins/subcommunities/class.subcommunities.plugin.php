<?php if (!defined('APPLICATION')) exit;

$PluginInfo['subcommunities'] = array(
    'Name'        => "Subcommunities",
    'Description' => "Allows you to use categories as virtual mini forums for multilingual or multi-product communities.",
    'Version'     => '1.0.1',
    'Author'      => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl'   => 'https://vanillaforums.com',
    'License'     => 'Proprietary'
);


class SubcommunitiesPlugin extends Gdn_Plugin {
    /// Properties ///

    protected $savedDefaultRoute = '';
    protected $savedDoHeadings = '';
    protected $categoryIDs = null;

    /// Methods ///

    /**
     */
    public function setup() {
        $this->structure();
    }

    public function structure() {
        Gdn::Structure()
            ->Table('Subcommunity')
            ->PrimaryKey('SubcommunityID')
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
     * Get the category IDs for the current subcommunity.
     *
     * @return array Returns an array of category IDs
     */
    public function getCategoryIDs() {
        if (!isset($this->categoryIDs)) {
            $site = SubcommunityModel::getCurrent();
            $categoryID = val('CategoryID', $site);

            // Get all of the category IDs associated with the subcommunity.
            $categories = CategoryModel::GetSubtree($categoryID, true);
            $this->categoryIDs = array_keys($categories);
        }
        return $this->categoryIDs;
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

//        // Set the default routes.
//        if ($site['CategoryID']) {
//            $category = CategoryModel::Categories($site['CategoryID']);
//            Gdn::Router()->SetRoute('categories$', ltrim(CategoryUrl($category, '', '/'), '/'), 'Internal', false);
//
//            $defaultRoute = Gdn::Router()->GetRoute('DefaultController');
//            if ($defaultRoute['Destination'] === 'categories') {
//                Gdn::Router()->SetRoute('DefaultController', ltrim(CategoryUrl($category, '', '/'), '/'), 'Temporary', false);
//            }
//        }

        SubcommunityModel::setCurrent($site);
    }

    /// Event Handlers ///

    public function base_render_before($sender) {
        // Add the alternate urls to the current crop of sites.
        SubcommunityModel::addAlternativeUrls();

        // Set alternative urls.
        $domain = Gdn::Request()->UrlDomain();
        foreach (SubcommunityModel::all() as $site) {
            if (!$site['AlternatePath']) {
                continue;
            }
            $url = "$domain/{$site['Folder']}{$site['AlternatePath']}";
            $sender->Head->AddTag(
                'link',
                [
                    'rel' => 'alternate',
                    'href' => $url,
                    'hreflang' => str_replace('_', '-', $site['Locale']),
                    HeadModule::SORT_KEY => 1000
                ]);
        }
    }

    public function base_getAppSettingsMenuItems_handler($sender) {
        /* @var SideMenuModule */
        $menu = $sender->EventArguments['SideMenu'];
        $menu->AddLink('Forum', T('Subcommunities'), '/subcommunities', 'Garden.Settings.Manage', ['After' => 'vanilla/settings/managecategories']);
    }

    /**
     * Override the categories that are displayed in the categories module to match the current subcommunity.
     *
     * @param CategoriesModule $sender
     */
    public function categoriesModule_getData_handler($sender) {
        $site = SubcommunityModel::getCurrent();
        $categoryID = val('CategoryID', $site);

        // Get the child categories
        $categories = CategoryModel::GetSubtree($categoryID, false, true);

        // Remove categories I can't view.
        $categories = array_filter($categories, function($category) {
           return (bool)val('PermsDiscussionsView', $category);
        });

        $data = new Gdn_DataSet($categories);
        $data->DatasetType(DATASET_TYPE_ARRAY);
        $data->DatasetType(DATASET_TYPE_OBJECT);
        $sender->Data = $data;
    }

    /**
     * Adjust the depth of the categories so that they start at 1.
     *
     * @param CategoriesController $sender
     */
    public function categoriesController_render_before($sender) {
        if (empty($sender->Data['Category']) || empty($sender->Data['Categories'])) {
            return;
        }

        $adjust = -$sender->Data('Category.Depth');
        foreach ($sender->Data['Categories'] as &$category) {
            SetValue('Depth', $category, val('Depth', $category) + $adjust);
        }
    }

    /**
     * Make sure the discussions controller is filtering by subcommunity.
     *
     * @param DiscussionsController $sender
     * @param array $args
     */
    public function discussionsController_index_before($sender, $args) {
        // Get all of the category IDs associated with the subcommunity.
        $categoryIDs = $this->getCategoryIDs();
        $sender->setCategoryIDs($categoryIDs);
    }

    /**
     * Make sure the discussions module is filtering by subcommunity.
     *
     * @param DiscussionsModule $sender
     * @param array $args
     */
    public function discussionsModule_init_handler($sender, $args) {
        $sender->setCategoryIDs($this->getCategoryIDs());
    }

    public function Gdn_Dispatcher_AppStartup_Handler() {
        saveToConfig(
            [
                'Vanilla.Categories.NavDepth' => 1
            ],
            '',
            false
        );

        $parts = explode('/', trim(Gdn::request()->path(), '/'), 2);
        $root = $parts[0];
        $path = val(1, $parts, '');

        // Look the root up in the mini sites.
        $site = SubcommunityModel::getSite($root);
        if ($site) {
            Gdn::Request()->path($path);
            $webroot = Gdn::request()->webRoot();
            Gdn::Request()->assetRoot($webroot);
            Gdn::Request()->webRoot(trim("$webroot/$root", '/'));


            $this->initializeSite($site);
        } elseif (!in_array($root, ['utility'])) {
            $defaultSite = SubcommunityModel::getDefaultSite();
            if ($defaultSite) {

                $url = Gdn::Request()->assetRoot().'/'.$defaultSite['Folder'].rtrim('/'.Gdn::Request()->Path(), '/');
                redirectUrl($url, debug() ? 302 : 301);
            }
        }

        $this->savedDoHeadings = C('Vanilla.Categories.DoHeadings');
        $navDepth = C('Vanilla.Categories.NavDepth', 0);
        if ($navDepth == 0) {
            saveToConfig('Vanilla.Categories.NavDepth', 1);
        }
    }

    /**
     * Default Routing
     *
     * This forces the default controller to be /account, since the vanilla
     * application is not loaded and we don't have any discussions.
     *
     * @param Gdn_Router $sender
     */
    public function Gdn_Router_beforeLoadRoutes_Handler($sender, $args) {
        $routes =& $args['Routes'];
        $site = SubcommunityModel::getCurrent();

        // Set the default routes.
        if (val('CategoryID', $site)) {
            $category = CategoryModel::Categories($site['CategoryID']);

            // Set the default category root.
            $routes[base64_encode('categories(.json)?$')] = ltrim(CategoryUrl($category, '', '/'), '/').'$1';

            $defaultRoute = val('DefaultController', $routes);
            if (is_array($defaultRoute)) {
                $defaultRoute = array_shift($defaultRoute);
            }
            $this->savedDefaultRoute = $defaultRoute;
            switch ($defaultRoute) {
                case 'categories':
                    $defaultRoute = ltrim(CategoryUrl($category, '', '/'), '/');
                    break;
            }
            if ($defaultRoute) {
                $routes['DefaultController'] = $defaultRoute;
            }

//            Gdn::Router()->SetRoute('categories$', ltrim(CategoryUrl($category, '', '/'), '/'), 'Internal', false);
//
//            $defaultRoute = Gdn::Router()->GetRoute('DefaultController');
//            if ($defaultRoute['Destination'] === 'categories') {
//                Gdn::Router()->SetRoute('DefaultController', ltrim(CategoryUrl($category, '', '/'), '/'), 'Temporary', false);
//            }
//
//            $sender->EventArguments['Routes']['DefaultForumRoot'] = 'account';
//            $sender->EventArguments['Routes']['DefaultController'] = 'account';
        }
    }

    public function settingsController_homepage_render($sender) {
        if ($this->savedDefaultRoute) {
            $sender->setData('CurrentTarget', $this->savedDefaultRoute);
        }
    }

    /**
     * @param Smarty $sender
     */
    public function Gdn_Smarty_Init_Handler($sender) {
        $sender->assign('Subcommunity', SubcommunityModel::getCurrent());
    }

    /**
     * @return SubcommunitiesPlugin
     */
    public static function instance() {
        return Gdn::PluginManager()->GetPluginInstance(__CLASS__, Gdn_PluginManager::ACCESS_CLASSNAME);
    }
}
