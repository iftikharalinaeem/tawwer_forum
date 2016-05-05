<?php if (!defined('APPLICATION')) { exit; }

$PluginInfo['subcommunities'] = array(
    'Name'        => "Subcommunities",
    'Description' => "Allows you to use top level categories as virtual mini forums for multilingual or multi-product communities.",
    'Version'     => '1.0.2',
    'Author'      => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl'   => 'https://vanillaforums.com',
    'License'     => 'Proprietary'
);


class SubcommunitiesPlugin extends Gdn_Plugin {
    /// Properties ///

    /**
     * @var bool Is this an API call?
     */
    protected $api = false;

    protected $savedDefaultRoute = '';
    protected $savedDoHeadings = '';

    protected $categories;

    /// Methods ///

    /**
     */
    public function setup() {
        $this->structure();
    }

    public function structure() {
        Gdn::structure()
            ->table('Subcommunity')
            ->primaryKey('SubcommunityID')
            ->column('Name', 'varchar(255)')
            ->column('Folder', 'varchar(255)', false, 'unique.Folder')
            ->column('CategoryID', 'int', true)
            ->column('Locale', 'varchar(20)')
            ->column('DateInserted', 'datetime')
            ->column('InsertUserID', 'int')
            ->column('DateUpdated', 'datetime', true)
            ->column('UpdateUserID', 'int', true)
            ->column('Attributes', 'text', true)
            ->column('Sort', 'smallint', '1000')
            ->column('IsDefault', 'tinyint(1)', true, 'unique.IsDefault')
            ->set();
    }

    /**
     * Get the category IDs for the current subcommunity.
     *
     * @return array Returns an array of category IDs
     */
    public function getCategoryIDs() {
        static $categoryIDs = null;

        if ($categoryIDs === null) {
            $categories = $this->getCategories();
            $categoryIDs = array_keys($categories);
        }
        return $categoryIDs;
    }

    /**
     * Get the categories for the current subcommunity.
     *
     * @return array Returns an array of categories
     */
    public function getCategories() {
        if ($this->categories === null) {
            if ($this->api && SubCommunityModel::getCurrent() === null) {
                $this->categories = CategoryModel::getSubtree(-1, false);
            } else {
                $site = SubcommunityModel::getCurrent();
                $categoryID = val('CategoryID', $site);

                // Get all of the category IDs associated with the subcommunity.
                $this->categories = CategoryModel::getSubtree($categoryID, false);
            }
        }

        return $this->categories;
    }
    /**
     * Initialize the environment on a mini site.
     * @param array $site The site to set.
     */
    protected function initializeSite(array $site) {
        // Set the locale from the site.
        if ($site['Locale'] !== Gdn::locale()->current()) {
            Gdn::locale()->set($site['Locale'], Gdn::applicationManager()->enabledApplicationFolders(), Gdn::pluginManager()->enabledPluginFolders());
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

    /**
     * Determine if the current request is an API request. SimpleAPI adds an "API" property to Gdn_Dispatcher with a
     * value of true if this is an API request.  However, relying on this assumes its Gdn_Dispatcher AppStartup
     * handler has run before now.  We check the "API" property presence and, failing that, analyze the URL format.
     *
     * @param Gdn_Dispatcher $dispatcher Instance of Gdn_Dispatcher to analyze.
     * @return bool True if determined to be an API request.  Otherwise, false.
     */
    protected function isAPI(Gdn_Dispatcher $dispatcher) {
        if (val('API', $dispatcher, false)) {
            return true;
        } elseif (preg_match('`^/?api/(v[\d\.]+)/(.+)`i', Gdn::request()->requestURI())) {
            return true;
        }

        return false;
    }

    /// Event Handlers ///

    /**
     * Add subcommunity to default analytics data for events tracked with VanillaAnalytics.
     *
     * @param AnalyticsTracker $sender
     * @param array $args
     */
    public function analyticsTracker_GetDefaultData_handler($sender, &$args) {
        $subcommunity = SubcommunityModel::getCurrent();

        if (!is_array($subcommunity)) {
            return;
        }

        $args['Defaults']['Subcommunity'] = [
            'Locale'         => $subcommunity['Locale'],
            'Folder'         => $subcommunity['Folder'],
            'Name'           => $subcommunity['Name'],
            'SubcommunityID' => $subcommunity['SubcommunityID']
        ];
    }

    public function base_render_before($sender) {
        if (!SubCommunityModel::getCurrent()) {
            return;
        }

        // Add the alternate urls to the current crop of sites.
        SubcommunityModel::addAlternativeUrls();

        // Set alternative urls.
        $domain = Gdn::request()->urlDomain();
        foreach (SubcommunityModel::all() as $site) {
            if (!$site['AlternatePath']) {
                continue;
            }
            $url = "$domain/{$site['Folder']}{$site['AlternatePath']}";
            $sender->Head->addTag(
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
        $menu->addLink('Forum', t('Subcommunities'), '/subcommunities', 'Garden.Settings.Manage', ['After' => 'vanilla/settings/managecategories']);
    }

    /**
     * Override the categories that are displayed in the categories module to match the current subcommunity.
     *
     * @param CategoriesModule $sender
     */
    public function categoriesModule_getData_handler($sender) {
        $site = SubcommunityModel::getCurrent();

        if (!$site) {
            return;
        }

        $categoryID = val('CategoryID', $site);

        // Get the child categories
        $categories = CategoryModel::getSubtree($categoryID, false, true);

        // Remove categories I can't view.
        $categories = array_filter($categories, function($category) {
           return (bool)val('PermsDiscussionsView', $category);
        });

        $data = new Gdn_DataSet($categories);
        $data->datasetType(DATASET_TYPE_ARRAY);
        $data->datasetType(DATASET_TYPE_OBJECT);
        $sender->Data = $data;
    }

    /**
     * Adjust the depth of the categories so that they start at 1.
     *
     * @param CategoriesController $sender
     */
    public function categoriesController_render_before($sender) {
        if (!SubCommunityModel::getCurrent()) {
            return;
        }

        if (empty($sender->Data['Category']) || empty($sender->Data['Categories'])) {
            return;
        }

        $adjust = -$sender->data('Category.Depth');
        foreach ($sender->Data['Categories'] as &$category) {
            setValue('Depth', $category, val('Depth', $category) + $adjust);
        }

        // We add the Depth of the root Category to the MaxDisplayDepth before rendering the categories page.
        // This resets it so the rendering respects the MaxDisplayDepth.
        setValue('Depth', $sender->data('Category'), 0);
    }

    /**
     * Make sure the discussions controller is filtering by subcommunity.
     *
     * @param DiscussionsController $sender
     * @param array $args
     */
    public function discussionsController_index_before($sender, $args) {
        if (!SubCommunityModel::getCurrent()) {
            return;
        }

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
        if (!SubCommunityModel::getCurrent()) {
            return;
        }

        $sender->setCategoryIDs($this->getCategoryIDs());
    }

    /**
     * @param Gdn_Dispatcher $sender
     */
    public function gdn_dispatcher_appStartup_handler($sender) {

        $this->api = $this->isAPI($sender);

        $parts = explode('/', trim(Gdn::request()->path(), '/'), 2);
        $root = $parts[0];
        $path = val(1, $parts, '');

        // Look the root up in the mini sites.
        $site = SubcommunityModel::getSite($root);
        $defaultSite = SubcommunityModel::getDefaultSite();

        if (!$site && !$defaultSite) {
            return;
        }

        saveToConfig(
            [
                'Vanilla.Categories.NavDepth' => 1
            ],
            '',
            false
        );

        if ($site) {
            Gdn::request()->path($path);
            $webroot = Gdn::request()->webRoot();
            Gdn::request()->assetRoot($webroot);
            Gdn::request()->webRoot(trim("$webroot/$root", '/'));

            $this->initializeSite($site);
        } elseif (!in_array($root, ['utility', 'sso', 'entry']) && !$this->api) {
            if ($defaultSite) {
                $url = Gdn::request()->assetRoot().'/'.$defaultSite['Folder'].rtrim('/'.Gdn::request()->path(), '/');
                $get = Gdn::request()->get();
                if (!empty($get)) {
                    $url .= '?'.http_build_query($get);
                }

                redirectUrl($url, debug() ? 302 : 301);
            }
        }

        $this->savedDoHeadings = c('Vanilla.Categories.DoHeadings');
        $navDepth = c('Vanilla.Categories.NavDepth', 0);
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
    public function gdn_router_beforeLoadRoutes_handler($sender, $args) {
        $routes =& $args['Routes'];
        $site = SubcommunityModel::getCurrent();

        // Set the default routes.
        if (val('CategoryID', $site)) {
            $category = CategoryModel::categories($site['CategoryID']);

            // Set the default category root.
            $routes[base64_encode('categories(.json)?$')] = ltrim(categoryUrl($category, '', '/'), '/').'$1';

            $defaultRoute = val('DefaultController', $routes);
            if (is_array($defaultRoute)) {
                $defaultRoute = array_shift($defaultRoute);
            }
            $this->savedDefaultRoute = $defaultRoute;
            switch ($defaultRoute) {
                case 'categories':
                    $defaultRoute = ltrim(categoryUrl($category, '', '/'), '/');
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
    public function gdn_smarty_init_handler($sender) {
        $sender->assign('Subcommunity', SubcommunityModel::getCurrent());
    }

    /**
     * @return SubcommunitiesPlugin
     */
    public static function instance() {
        return Gdn::pluginManager()->getPluginInstance(__CLASS__, Gdn_PluginManager::ACCESS_CLASSNAME);
    }

    /**
     * Hook on CategoryModel's CategoryWatch event.
     *
     * Used to filter down the categories used in the normal search.
     *
     * @param $sender Sending controller instance.
     * @param $args Event arguments.
     */
    public function gdn_pluginManager_categoryWatch_handler($sender, $args) {
        if (!SubCommunityModel::getCurrent()) {
            return;
        }

        $args['CategoryIDs'] = $this->getCategoryIDs();
    }

    /**
     * Hook on AdvancedSearchPlugin's BeforeSearchCompilation event.
     *
     * Used to filter down the categories used in the advanced search
     *
     * @param $sender Sending controller instance.
     * @param $args Event arguments.
     */
    public function advancedSearchPlugin_beforeSearchCompilation_handler($sender, $args) {
        if (!SubCommunityModel::getCurrent()) {
            return;
        }

        $allowsUncategorized = in_array(0, $args['Search']['cat']);
        $args['Search']['cat'] = $this->getCategoryIDs();

        if ($allowsUncategorized) {
            $args['Search']['cat'][] = 0;
        }
    }

    /**
     * Hook on Gdn_Form BeforeCategoryDropDown event.
     *
     * Used to filter down the category dropdown when you are in a subcommunity.
     *
     * @param $sender Sending controller instance.
     * @param $args Event arguments.
     */
    public function gdn_form_beforeCategoryDropDown_handler($sender, $args) {
        if (!SubCommunityModel::getCurrent()) {
            return;
        }

        $categories = $this->getCategories();

        // Allow moving a post to another subcommunity!
        $path = Gdn::request()->path();
        if (stringBeginsWith($path, 'moderation/')) {
            $options = $args['Options'];
            $defaultCategories = CategoryModel::getByPermission(
                'Discussions.View',
                null,
                val('Filter', $options, ['Archived' => 0]),
                val('PermFilter', $options, [])
            );

            // Prevent moving posts into a subcommunity root category
            $subcommunities = SubcommunityModel::all();
            foreach($subcommunities as $subcommunity) {
                $defaultCategories[$subcommunity['CategoryID']]['AllowDiscussions'] = 0;
            }

            $categories = $defaultCategories;
        }

        $args['Options']['CategoryData'] = $categories;
    }

    /**
     * Adds an endpoint to render the subcommunity toggle.
     *
     * @param CategoriesController $sender The sending object.
     * @param array $args Expects a variation of the SubcommunityToggleModule view.
     */
    public function categoriesController_subcommunitySelect_create($sender, $args) {
        $module = new SubcommunityToggleModule();
        if (!empty($args)) {
            $module->Style = $args[0];
        }
        $sender->title('Choose a Forum');
        $sender->setData('SubcommunitiesModule', $module);
        $sender->render('subcommunityselect', '', 'plugins/subcommunities');
    }

    /**
     * Adds a link to the site nav module to change subcommunities.
     *
     * @param SiteNavModule $sender
     */
    public function siteNavModule_default_handler($sender) {
        $subName = val('Name', SubcommunityModel::getCurrent());
        $sender->addLink('etc.subcommuntyselect', array('text' => $subName.'<span class="pull-right icon icon-arrow-right"></span>', 'url' => 'categories/subcommunityselect', false, 'sort' => 99, 'icon' => icon('globe')));
    }

    /**
     * Adds the subcommunity CategoryID to the new discussion button so it can check its permissions.
     *
     * @param DiscussionsController $sender
     * @param array $args
     */
    public function discussionsController_beforeNewDiscussionButton_handler($sender, $args) {
        if (val('NewDiscussionModule', $args) && !$args['NewDiscussionModule']->CategoryID) {
            $args['NewDiscussionModule']->CategoryID = val('CategoryID', SubcommunityModel::getCurrent());
        }
    }
}
