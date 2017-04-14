<?php if (!defined('APPLICATION')) { exit; }

$PluginInfo['subcommunities'] = array(
    'Name'        => "Subcommunities",
    'Description' => "Allows you to use top level categories as virtual mini forums for multilingual or multi-product communities.",
    'Version'     => '1.0.3',
    'Author'      => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl'   => 'https://vanillaforums.com',
    'License'     => 'Proprietary',
    'Icon'        => 'subcommunities.png'
);


class SubcommunitiesPlugin extends Gdn_Plugin {
    /// Properties ///

    /**
     * @var bool Is this an API call?
     */
    protected $api = false;

    /** @var string The unmodified web root for the current request. */
    protected static $originalWebRoot = '';

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
            ->column('Folder', 'varchar(191)', false, 'unique.Folder')
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
     * Recursively adjust the depth of a category tree.
     *
     * @param array $tree The current category tree.
     * @param int $offset An offset, positive or negative, to add to each category's depth attribute.
     */
    protected static function adjustTreeDepth(&$tree, $offset = 0) {
        if (!is_array($tree)) {
            return;
        }

        foreach ($tree as &$category) {
            setValue('Depth', $category, val('Depth', $category) + $offset);

            if (!empty($category['Children'])) {
                static::adjustTreeDepth($category['Children'], $offset);
            }
        }
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
            Gdn::locale()->set($site['Locale']);
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
    public function analyticsTracker_GetDefaultData_handler($sender, $args) {
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

    /**
     * Change the heading of the top-level categories for 'Category' to 'Subcommunity/Category'
     *
     * @param AnalyticsController $sender
     * @param array $args
     */
    public function analyticsController_analyticsCategoryFilter_handler($sender, $args) {
        $args['Heading'] = t('Subcommunity/Category');
        $args['Attributes']['IncludeNull'] = t('All');
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
        $categoryID = val('CategoryID', $sender->data('Category'));
        $subcommunity = self::getSubcommunityFromCategoryID($categoryID);
        $sender->canonicalUrl(self::getCanonicalUrl(Gdn::request()->path(), $subcommunity));

        if (!SubcommunityModel::getCurrent()) {
            return;
        }

        if (empty($sender->Data['Category']) || empty($sender->Data['CategoryTree'])) {
            return;
        }

        // We add the Depth of the root Category to the MaxDisplayDepth before rendering the categories page.
        // This resets it so the rendering respects the MaxDisplayDepth.
        $sender->setData('Category.Depth', 0);
    }

    /**
     * @param DiscussionController $sender
     * @param array $args
     */
    public function discussionController_render_before($sender, $args) {
        $categoryID = val('CategoryID', $sender->data('Category'));
        $subcommunity = self::getSubcommunityFromCategoryID($categoryID);
        $sender->canonicalUrl(self::getCanonicalUrl(Gdn::request()->path(), $subcommunity));
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
        $parts = explode('/', trim(Gdn::request()->path(), '/'), 2);
        $root = $parts[0];

        if (SubcommunityModel::isReservedSlug($root)) {
            return;
        }

        $this->api = $this->isAPI($sender);
        $path = val(1, $parts, '');

        // Look the root up in the mini sites.
        $site = SubcommunityModel::getSite($root);
        $defaultSite = null;

        if (!$site) {
            $defaultSite = SubcommunityModel::getDefaultSite();
            if (!$defaultSite) {
                return;
            }
        }

        saveToConfig(
            [
                'Vanilla.Categories.NavDepth' => 1
            ],
            '',
            false
        );

        self::$originalWebRoot = Gdn::request()->webRoot();
        if ($site) {
            Gdn::request()->path($path);
            $webroot = self::$originalWebRoot;

            Gdn::request()->assetRoot($webroot);
            Gdn::request()->webRoot(trim("$webroot/$root", '/'));

            $this->initializeSite($site);
        } elseif (!$this->api) {
            if ($defaultSite) {
                // Redirect to the canonicalURL
                redirectURL(self::getCanonicalUrl(Gdn::request()->pathAndQuery(), $defaultSite));
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
     * Also filter down discussions controller categories.
     *
     * @param CategoryModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function categoryModel_categoryWatch_handler($sender, $args) {
        if (!SubCommunityModel::getCurrent()) {
            return;
        }

        $watchedCategoryIDs = $args['CategoryIDs'];
        $subcommunityCategoryIDs = $this->getCategoryIDs();

        $args['CategoryIDs'] = array_intersect($subcommunityCategoryIDs, $watchedCategoryIDs);
    }

    /**
     *
     *
     * Also filter down unanswered questions.
     * categoryWatch does not work here because the QnA controller set some categories himself.
     *
     * @param DiscussionsController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function QnAPlugin_unansweredBeforeSetCategories_handler($sender, $args) {
        if (!SubCommunityModel::getCurrent()) {
            return;
        }

        $subcommunityCategoryIDs = $this->getCategoryIDs();

        $args['Categories'] = array_intersect_key($args['Categories'], array_flip($subcommunityCategoryIDs));
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
     * Force ShowCategorySelector to true when we are creating a discussion
     *
     * @param PostController $sender Sending controller instance.
     */
    public function postController_beforeDiscussionRender_handler($sender) {
        if (!SubCommunityModel::getCurrent()) {
            return;
        }

        $subCommunityCategoryIDs = $this->getCategoryIDs();

        $isEditing = $sender->data('Discussion', false);
        $currentCategoryID = val('CategoryID', $sender->data('Category'), -1);

        // Check that we are in a category we can post in (ie. not the root category)
        $isInCategory = in_array($currentCategoryID, $subCommunityCategoryIDs);

        if ($isInCategory || $isEditing) {
            return;
        }

        if (count($subCommunityCategoryIDs) > 1) {
            if (val('ShowCategorySelector', $sender, null) === false) {
                $sender->ShowCategorySelector = true;
            }
        } else {
            // By default the root category is set in the form.
            // Overwrite that by the only category of this subcommunity.
            $sender->Form->addHidden('CategoryID', $subCommunityCategoryIDs[0]);
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
     * @param SiteNavModule $sender Sending controller instance.
     */
    public function siteNavModule_init_handler($sender) {
        $subName = val('Name', SubcommunityModel::getCurrent());
        $sender->addLink($subName.'<span class="pull-right icon icon-arrow-right"></span>', 'categories/subcommunityselect', 'etc.subcommuntyselect', '', [], ['icon' => 'globe']);
    }

    /**
     * Adds the subcommunity CategoryID to the new discussion button so it can check its permissions.
     *
     * @param DiscussionsController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionsController_beforeNewDiscussionButton_handler($sender, $args) {
        if (val('NewDiscussionModule', $args) && !$args['NewDiscussionModule']->CategoryID) {
            $args['NewDiscussionModule']->CategoryID = val('CategoryID', SubcommunityModel::getCurrent());
        }
    }

    /**
     * Filter permissions when counting questions from the QnA plugin to avoid counting questions for other subcommunities.
     *
     * @param QnAPlugin $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function QnAPlugin_unansweredCount_handler($sender, $args) {
        if (!SubCommunityModel::getCurrent()) {
            return;
        }

        // Check for individual categories.
        $categoryIDs = $this->getCategoryIDs();
        $questionCount = Gdn::sql()
            ->whereIn('CategoryID', $categoryIDs)
            ->whereIn('QnA', array('Unanswered', 'Rejected'))
            ->getCount('Discussion', array('Type' => 'Question'));

        // Pass number of questions back to sender.
        $args['questionCount'] = $questionCount;
    }

    /**
     * Get the canonical URL of a path.
     *
     * @param string $path The path we want te canonical of.
     * @param array|null $subcommunity The subcommunity to which belong that path.
     *
     * @return string
     */
    public static function getCanonicalUrl($path, $subcommunity) {
        if ($subcommunity !== null) {
            // OriginalWebRoot is the un-modified web root, used in case we are already in a subcommunity.
            $targetWebRoot = trim(self::$originalWebRoot."/{$subcommunity['Folder']}", '/');
            // Temporarily swap out the current web root for the modified one, before generating the URL.
            $currentWebRoot = Gdn::request()->webRoot();
            Gdn::request()->webRoot($targetWebRoot);
        }

        $canonicalUrl = url($path, true);

        if ($subcommunity !== null) {
            Gdn::request()->webRoot($currentWebRoot);

            // If viewing a category URL, reset the path to home when viewing the subcommunity's category.
            $canonicalUrl = trim($canonicalUrl, '/');
            if (stringEndsWith($canonicalUrl, "categories/$subcommunity[Folder]")) {
                $canonicalUrl = substr($canonicalUrl, 0, -strlen('/'.$subcommunity['Folder']));
            }
        }


        return $canonicalUrl;
    }

    /**
     * Get a category's subcommunity.
     *
     * @param $categoryID
     * @return array|null The found subcommunity or the default subcommunity is any.
     */
    public static function getSubcommunityFromCategoryID($categoryID) {
        $targetSubcommunity = null;

        if ($categoryID) {
            // Grab this category's ancestors...
            $parents = CategoryModel::getAncestors($categoryID, true, true);
            // ...and pull the one from the top. This should be the highest, non-root parent.
            $topParent = reset($parents);

            if ($topParent) {
                foreach (SubcommunityModel::all() as $subcommunity) {
                    if ($subcommunity['CategoryID'] == $topParent['CategoryID']) {
                        $targetSubcommunity = $subcommunity;
                        break;
                    }
                }
            } else {
                trigger_error("Could not find top parent of categoryID $categoryID.", E_USER_NOTICE);
            }
        }

        return $targetSubcommunity ?: SubcommunityModel::getDefaultSite();
    }



    /**
     *
     *
     * Filter down categories for SiteMapPlugin
     *
     * @param SiteMapPlugin $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function sitemapsPlugin_siteMapCategories_handler($sender, $args) {
        if (!SubCommunityModel::getCurrent()) {
            return;
        }
        $args['Categories'] = array_intersect_key($args['Categories'], array_flip($this->getCategoryIDs()));
    }
}

if (!function_exists('commentUrl')) {
    /**
     * Return a URL for a comment.
     *
     * @param object $comment
     * @param bool $withDomain
     * @return string
     */
    function commentUrl($comment, $withDomain = true) {
        $comment = (object)$comment;
        $commentID = val('CommentID', $comment);

        // This isn't normally on the comment record, but may come across as part of a search result.
        $categoryID = val('CategoryID', $comment);

        if ($categoryID === false) {
            // This would normally be on the comment record, but may not come across as part of a search result.
            $discussionID = val('DiscussionID', $comment);

            // Try to dig up the discussion ID by looking up the comment.
            if ($discussionID === false) {
                $commentModel = new CommentModel();
                $comment = $commentModel->getID($comment->CommentID);
                if ($comment) {
                    $discussionID = val('DiscussionID', $comment);
                }
            }

            // Try to dig up the category ID by looking up the discussion.
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($discussionID);
            if ($discussion) {
                $categoryID = val('CategoryID', $discussion);
            }
        }

        $path = "/discussion/comment/{$commentID}#Comment_{$commentID}";
        $subcommunity = SubcommunitiesPlugin::getSubcommunityFromCategoryID($categoryID);

        return SubcommunitiesPlugin::getCanonicalUrl($path, $subcommunity);
    }
}

if (!function_exists('discussionUrl')) {
    /**
     * Return a URL for a discussion.
     *
     * @param object $discussion
     * @param int|string $page
     * @param bool $withDomain
     * @return string
     */
    function discussionUrl($discussion, $page = '', $withDomain = true) {
        $discussion = (object)$discussion;
        $name = Gdn_Format::url(val('Name', $discussion));
        $categoryID = val('CategoryID', $discussion);
        $discussionID = $discussion->DiscussionID;

        // Disallow an empty name slug in discussion URLs.
        if (empty($name)) {
            $name = 'x';
        }

        $path = "/discussion/{$discussionID}/{$name}";

        if ($page) {
            if ($page > 1 || Gdn::session()->UserID) {
                $path .= "/p{$page}";
            }
        }

        $subcommunity = SubcommunitiesPlugin::getSubcommunityFromCategoryID($categoryID);

        return SubcommunitiesPlugin::getCanonicalUrl($path, $subcommunity);
    }
}
