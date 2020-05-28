<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Container\Container;
use Garden\EventManager;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Subcommunities\Models\MultisiteReduxPreloader;
use Vanilla\Subcommunities\Models\SubcommunitySiteSection;
use Vanilla\Web\Page;
use \Garden\Container\Reference;
use Vanilla\Subcommunities\Models\SubcomunitiesSiteSectionProvider;

/**
 * Class SubcommunitiesPlugin
 */
class SubcommunitiesPlugin extends Gdn_Plugin {

    /// Constants
    const URL_TYPE_UNKNOWN = 0;
    const URL_TYPE_DISCUSSION = 1;
    const URL_TYPE_COMMENT = 2;
    const URL_TYPE_CATEGORY = 3;

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
            ->column('ProductID', 'smallint', true)
            ->column('defaultController', 'varchar(30)', true)
            ->column('knowledgeBase', 'tinyint(1)', true)
            ->column('forum', 'tinyint(1)', true)
            ->column('themeID', 'varchar(30)', true)
            ->set();

        Gdn::structure()
            ->table('product')
            ->primaryKey('productID')
            ->column('name', 'varchar(255)')
            ->column('body', 'varchar(255)', true)
            ->column("dateInserted", "datetime")
            ->column("insertUserID", "int")
            ->column("dateUpdated", "datetime", true )
            ->column("updateUserID", "int", true)
            ->set();

        // Fixup any products that got empty names inserted before hand.
        Gdn::sql()->update('product', ['name' => \Gdn::translate("(Untitled)")], ["name" => ""])->put();
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
                $this->categories = CategoryModel::getSubtree($categoryID, true);
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
    public function analyticsTracker_getDefaultData_handler($sender, $args) {
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

    /**
     * @param Gdn_Controller $sender
     */
    public function discussionsController_render_before(Gdn_Controller $sender) {
        $site = SubcommunityModel::getCurrent();
        if (!$site) {
            return;
        }
        $categoryID = $site['CategoryID'];
        $sender->setData('ContextualCategoryID', $categoryID);
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

        // Just set the root if the property exists.
        if (property_exists($sender, 'root') && !$sender->root) {
            $sender->root = $categoryID;
            return;
        }

        $categoryModel = new CategoryModel();
        $categories = $categoryModel
            ->setJoinUserCategory(true)
            ->getChildTree($categoryID, ['collapseCategories' => val('collapseCategories', $sender)]);
        $categories = CategoryModel::flattenTree($categories);

        // Remove categories I can't view.
        $categories = array_filter($categories, function($category) {
           return val('PermsDiscussionsView', $category) && val('Following', $category);
        });

        $data = new Gdn_DataSet($categories, DATASET_TYPE_ARRAY);
        $data->datasetType(DATASET_TYPE_OBJECT);
        $sender->Data = $data;
    }

    /**
     * Determine if the filter menu for followed categories should be displayed on a category page.
     *
     * @param CategoriesController $sender
     * @param array $args
     */
    public function categoriesController_enableFollowingFilter_handler($sender, $args) {
        $categoryIdentifier = $args['CategoryIdentifier'];

        // If we're in a subcommunity, and the category is the subcommunity root, display the filter menu.
        if ($categoryIdentifier !== '') {
            $subcommunity = SubcommunityModel::getCurrent();
            if (is_array($subcommunity)) {
                $category = CategoryModel::categories($categoryIdentifier);
                if (is_array($category)) {
                    $args['EnableFollowingFilter'] = $subcommunity['CategoryID'] == $category['CategoryID'];
                }
            }
        }
    }

    /**
     * Adjust the depth of the categories so that they start at 1.
     *
     * @param CategoriesController $sender
     */
    public function categoriesController_render_before($sender) {
        $categoryID = val('CategoryID', $sender->data('Category'));
        $subcommunity = self::getCanonicalSubcommunity($categoryID);

        //set canonical tag
        $canonicalUrl = empty($sender->Data['isHomepage']) ?
            self::getCanonicalSubcommunityUrl(Gdn::request()->path(), $subcommunity, self::URL_TYPE_CATEGORY) :
            url('/', true);
        $sender->canonicalUrl($canonicalUrl);

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
        // Avoid attempting to determine the canonical URL or redirecting the request if we aren't displaying a full page.
        if ($sender->deliveryType() !== \DELIVERY_TYPE_ALL) {
            return;
        }

        // We only need to set the canonical or redirect for discussion pages.
        if (!in_array(strtolower($sender->RequestMethod), ["comment", "embed", "index"])) {
            return;
        }

        // We need a category ID to get the subcommunity. No category? Bail.
        $categoryID = val('CategoryID', $sender->data('Category'), null);
        if ($categoryID === null) {
            return;
        }

        $subcommunity = $this->getCanonicalSubcommunity($categoryID);

        // If the discussion isn't in the current subcommunity, redirect to the proper subcommunity.
        $isGetRequest = Gdn::request()->getMethod() === Gdn_Request::METHOD_GET;
        $isGroupDiscussion = !empty($sender->Discussion->GroupID);
        if ($isGetRequest && $isGroupDiscussion === false) {
            $subPath = ltrim(self::$originalWebRoot.'/'.$subcommunity['Folder'], "/");
            $fullPath = ltrim(Gdn::request()->getFullPath(), "/");

            $isInSubcommunity = strcmp($subPath, substr($fullPath, 0, strlen($subPath))) === 0;
            if ($isInSubcommunity === false) {
                redirectTo(
                    $this->getCanonicalSubcommunityUrl(Gdn::request()->pathAndQuery(),
                    $subcommunity,
                    self::URL_TYPE_DISCUSSION, $categoryID),
                    301
                );
            }
        }

        $discussionUrl = $this->getCanonicalSubcommunityUrl(
            Gdn::request()->path(),
            $subcommunity,
            self::URL_TYPE_DISCUSSION, $categoryID
        );
        $sender->canonicalUrl($discussionUrl);
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
        self::$originalWebRoot = Gdn::request()->webRoot();

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

        if ($site) {
            Gdn::request()->path($path);
            $webroot = self::$originalWebRoot;

            Gdn::request()->setAssetRoot($webroot);
            Gdn::request()->webRoot(trim("$webroot/$root", '/'));
            $this->initializeSite($site);
        } elseif (!$this->api) {
            if ($defaultSite) {
                // Redirect to the canonicalURL
                redirectTo(self::getCanonicalUrl(Gdn::request()->pathAndQuery(), $defaultSite), 301);
            }
        } else {
            $site = $defaultSite;
        }

        $this->savedDoHeadings = c('Vanilla.Categories.DoHeadings');
        $navDepth = c('Vanilla.Categories.NavDepth', 0);
        if ($navDepth == 0) {
            saveToConfig('Vanilla.Categories.NavDepth', 1);
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
     * Filter visible categories.
     *
     * @param array|bool $categories An array of IDs representing categories available to the current user. True if all are available.
     * @return array|bool array of categories or true.
     */
    public function categoryModel_visibleCategories_handler($categories) {
        if (SubCommunityModel::getCurrent()) {
            $subcommunityCategories = $this->getCategories();
            $filteredCategories = Gdn::getContainer()->get(EventManager::class)->fireFilter('subcommunitiesPlugin_subcommunityVisibleCategories', $subcommunityCategories, $categories);
            if ($categories === true) {
                $categories = $filteredCategories;
            } elseif (is_array($categories)) {
                $filteredCategoriesID = array_column($filteredCategories, 'CategoryID');
                $categories = array_filter($categories, function($category) use ($filteredCategoriesID) {
                    return in_array($category['CategoryID'], $filteredCategoriesID);
                });
            }
        }
        return $categories;
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
    public function qnAPlugin_unansweredBeforeSetCategories_handler($sender, $args) {
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
        $categoryModel = new CategoryModel();
        $visibleCategories = $categoryModel->getVisibleCategories() ?? null;
        $visibleCategoriesIDs = array_column($visibleCategories, 'CategoryID');
        $isEditing = $sender->data('Discussion', false);
        $currentCategoryID = val('CategoryID', $sender->data('Category'), -1);

        // Check that we are in a category we can post in (ie. not the root category)
        $isInCategory = in_array($currentCategoryID, $visibleCategoriesIDs);

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
     * @param Gdn_Controller $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function gdn_form_beforeCategoryDropDown_handler($sender, $args) {
        //Drafts is shared content so subcommunities should not intervene
        if (!SubCommunityModel::getCurrent() || array_key_exists('DraftID', $args['Options'])) {
            return;
        }

        $categories = $this->getCategories();

        // Because including the ParentCategory throws off the depths, we are building the depth.
        $categories = $this->rebuildCategoryDepths($categories);

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
            foreach ($subcommunities as $subcommunity) {
                $defaultCategories[$subcommunity['CategoryID']]['AllowDiscussions'] = 0;
            }

            $categories = $defaultCategories;
        }

        $args['Options']['CategoryData'] = $categories;
        $subcommunityID = SubcommunityModel::getCurrent();
        $formValue = !empty($sender->getFormValue('cat')) ? $sender->getFormValue('cat') : null;
        $categoryID = $formValue ?:  $subcommunityID['CategoryID'];
        $args['Options']['Value'] = $categoryID;
    }

    /**
     * Rebuilds the Depth of Subcommunity Categories for the CategoryDropDown.
     *
     * @param array $categories
     * @return array
     */
    private function rebuildCategoryDepths(array $categories): array {
        foreach ($categories as $categoryID => $category) {
            if ($category['ParentCategoryID'] < 0) {
                $categories[$categoryID]['Depth'] = 1;
            } else {
                $categories[$categoryID]['Depth'] = (int)$categories[$category['ParentCategoryID']]['Depth'] + 1;
            }
        }
        return $categories;
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
    public function qnAPlugin_unansweredCount_handler($sender, $args) {
        if (!SubCommunityModel::getCurrent()) {
            return;
        }

        // Check for individual categories.
        $categoryIDs = $this->getCategoryIDs();
        $questionCount = Gdn::sql()
            ->whereIn('CategoryID', $categoryIDs)
            ->whereIn('QnA', ['Unanswered', 'Rejected'])
            ->getCount('Discussion', ['Type' => 'Question']);

        // Pass number of questions back to sender.
        $args['questionCount'] = $questionCount;
    }

    /**
     * Get the canonical URL of a path.
     * This method is deprecated. Use private getCanonicalSubcommunityUrl() instead.
     *
     * @deprecated
     */
    public static function getCanonicalUrl($path, $subcommunity) {
        return Gdn::getContainer()
            ->get(SubcommunitiesPlugin::class)
            ->getCanonicalSubcommunityUrl($path, $subcommunity, self::URL_TYPE_UNKNOWN);

        return $canonicalUrl;
    }

    /**
     * Get the canonical URL of a path.
     *
     * @param string $path The path we want te canonical of.
     * @param array|null $subcommunity The subcommunity to which belong that path.
     *
     * @return string
     */
    private function getCanonicalSubcommunityUrl($path, $subcommunity, int $recordType, int $categoryID = null): string {
        if ($subcommunity !== null) {
            $targetWebRoot = trim(self::$originalWebRoot."/{$subcommunity['Folder']}", '/');
            // Temporarily swap out the current web root for the modified one, before generating the URL.
            $currentWebRoot = Gdn::request()->webRoot();
            Gdn::request()->webRoot($targetWebRoot);

            $canonicalUrl = Gdn::request()->url(strval($path), true);
            // Restore current webroot
            Gdn::request()->webRoot($currentWebRoot);

            // If viewing a category URL, reset the path to home when viewing the subcommunity's category.
            $canonicalUrl = trim($canonicalUrl, '/');
            if ($recordType === self::URL_TYPE_CATEGORY && $categoryID == $subcommunity['CategoryID']) {
                $canonicalUrl = substr($canonicalUrl, 0, strrpos($canonicalUrl,'/categories/') + 11);
            }
        } else {
            $canonicalUrl = url($path, true);
        }

        return $canonicalUrl;
    }


    /**
     * Attempt to get a category's subcommunity.
     *
     * @param int|string $categoryID The categoryID to lookup.
     * @return array|null The found subcommunity or null if not found.
     */
    private static function getTargetSubcommunity($categoryID) {
        $targetSubcommunity = null;

        // Use our own category collection to circumvent a possible recursive call stack because of CategoryModel->calculate()
        // calling categoryURL() which we redefine here.
        static $categoryCollection = null;
        if ($categoryCollection === null) {
            $noop = function(){};
            $categoryCollection = CategoryModel::instance()->createCollection();
            $categoryCollection
                ->setStaticCalculator($noop)
                ->setUserCalculator($noop);
            $categoryCollection->setCacheReadOnly(true);
        }

        if ($categoryID) {
            // Grab this category's ancestors...
            $parents = $categoryCollection->getAncestors($categoryID, true);
            // ...and pull the one from the top. This should be the highest, non-root parent.
            $topParent = reset($parents);

            if ($topParent) {
                $subcommunities = SubcommunityModel::all();
                foreach ($subcommunities as $subcommunity) {
                    if ($subcommunity['CategoryID'] == $topParent['CategoryID']) {
                        $targetSubcommunity = $subcommunity;
                        break;
                    }
                }
            } else {
                trigger_error("Could not find top parent of categoryID $categoryID.", E_USER_NOTICE);
            }
        }

        return $targetSubcommunity;
    }

    /**
     * Get the a non-canonical subcommunity for a category. This will be based off the current subcommunity.
     *
     * @param int|string $categoryID The categoryID to lookup.
     * @return array The non-canonical subcommunity for a category.
     */
    public static function getNonCanonicalSubcommunity($categoryID) {
        return self::getTargetSubcommunity($categoryID) ?: SubcommunityModel::getCurrent();
    }

    /**
     * Get the a canonical subcommunity for a category.
     *
     * @param int|string $categoryID The categoryID to lookup.
     * @return array The canonical subcommunity for a category.
     */
    public static function getCanonicalSubcommunity($categoryID) {
        return self::getTargetSubcommunity($categoryID) ?: SubcommunityModel::getDefaultSite();
    }

    /**
     * Get a category's subcommunity.
     *
     * @param int|string $categoryID The categoryID to lookup.
     * @return array The found subcommunity or the default subcommunity (will be canonical).
     *
     * @deprecated since 2.6, reason: poorly named.
     */
    public static function getSubcommunityFromCategoryID($categoryID) {
        deprecated(__METHOD__.'()', self::class.'::getCanonicalSubcommunity()');
        return self::getCanonicalSubcommunity($categoryID);
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

    /**
     * Generate a subcommunity URL as the url() would.
     *
     * if $withDomain === '/' then the path is returned as is.
     *
     * @param $categoryID The categoryID of which the path belongs to.
     * @param $path A relative path.
     * @param bool $withDomain See Gdn_Request->url() option.
     * @param bool $page (optional) Current page.
     * @param int $recordType Url record type. Ex: URL_TYPE_DISCUSSION, URL_TYPE_COMMENT, URL_TYPE_CATEGORY, etc...
     * @return string The URL.
     */
    public function subcommunityURL($categoryID, $path, $withDomain = true, $page = false, int $recordType = self::URL_TYPE_UNKNOWN): string {
        if ($withDomain === '/') {
            // Skip webroot / return as is
            $url = $path;
        } else {
            $subcommunity = $this->getNonCanonicalSubcommunity($categoryID);
            $cannonicalURL = $this->getCanonicalSubcommunityUrl($path, $subcommunity, $recordType, $categoryID);

            // The url is supposed to be relative.
            if (!$withDomain) {
                $parsedURL = parse_url($cannonicalURL);
                $url = $parsedURL['path'].$parsedURL['query'].$parsedURL['fragment'];
            } else if ($withDomain === '//') {
                $url = url($cannonicalURL, '//');
            } else {
                $url = $cannonicalURL;
            }
        }

        if ($page && ($page > 1 || Gdn::session()->UserID)) {
            $url .= "/p{$page}";
        }

        return $url;
    }

    /**
     * Update where clause when calling DiscussionModel->get()
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_beforeGet_handler($sender, $args) {
        $this->dicussionQueryFiltering($args);
    }

    /**
     * Update where clause when calling DiscussionModel->getCount()
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_beforeGetCount_handler($sender, $args) {
        $this->dicussionQueryFiltering($args);
    }

    /**
     * Restricting searches to the immediate subcommunity.
     *
     * @param  array $args
     * @return mixed
     */
    public function advancedSearchPlugin_beforeSearch_handler($args) {
        $searchCategory = $args['search']['cat'] ?? '';
        $inSubCommunity = true;

        if ($searchCategory !== 'all') {
            $subCommunityCategory = SubcommunityModel::getCurrent() ?? null;

            // If the search category id isn't the subcommunity category id, check if it's in
            // the subcommunity and use that id for the search.
            if ($searchCategory !== $subCommunityCategory['CategoryID']) {
                $subcommunityCategoryIDs = $this->getCategoryIDs();
                $inSubCommunity = in_array($searchCategory, $subcommunityCategoryIDs);
            }
            $args['categoryID'] = ($inSubCommunity) ? $searchCategory : $subCommunityCategory['CategoryID'];
            $args['search']['subcats'] = ( $args['categoryID']) ? 1 : $args['subcats'];
        }
        return $args;
    }

    /**
     * Add filter to discussion queries based on certain conditions.
     *
     * @param array $args
     */
    private function dicussionQueryFiltering($args) {
        if (!SubCommunityModel::getCurrent()) {
            return;
        }

        if (!isset($args['Wheres']) || !is_array($args['Wheres'])) {
            return;
        }

        $wheres = array_change_key_case($args['Wheres']);

        // If the query is filtered by "resolved"
        if (!array_key_exists('resolved', $wheres) && !array_key_exists('d.resolved', $wheres)) {
            return;
        }

        $args['Wheres']['d.CategoryID'] = $this->getCategoryIDs();
    }

    /**
     * Change the target to /  when subcommunities is used and the default subcommunity route destination is equal to target.
     *
     * @param \Gdn_Dispatcher $sender
     * @param array $args
     */
    public function gdn_dispatcher_beforeDispatch_handler(\Gdn_Dispatcher $sender, array $args) {
        /** @var \Gdn_Request $request */
        $request = $args['Request'];
        /** @var SiteSectionModel $siteSectionModel */
        $siteSectionModel =  Gdn::getContainer()->get(SiteSectionModel::class);
        if ($siteSectionModel->getCurrentSiteSection()->getDefaultRoute()['Destination'] == $request->get('Target')) {
            $request->setQueryItem('Target', '/');
        }
    }

    /**
     * Add multi role input to pocket filters.
     *
     * @param array $args
     */
    public function settingsController_additionalPocketFilterInputs_handler($args) {
        $Form = $args['form'];
        echo $Form->react(
            "SubcommunityIDs",
            "pocket-subcommunity-chooser",
            [
                "tag" => "li",
                "value" => $Form->getvalue("SubcommunityIDs") ?? []
            ]
        );
    }

    /**
     * Add some event handling for pocket rendering.
     *
     * @param bool $existingCanRender
     * @param Pocket $pocket
     * @param array $requestData
     *
     * @return bool
     */
    public function pocket_canRender_handler(bool $existingCanRender, Pocket $pocket, array $requestData): bool {
        if (!$existingCanRender) {
            return $existingCanRender;
        }
        $pocketData = $pocket->Data;

        $subcommunityIDs = $pocketData['SubcommunityIDs'] ?? [];

        if (empty($subcommunityIDs)) {
            return false;
        }

        $currentSubcommunity = SubcommunityModel::getCurrent();
        if (empty($currentSubcommunity)) {
            return false;
        }

        $currentSubcommunityID = strval($currentSubcommunity["SubcommunityID"]);
        $result = array_search($currentSubcommunityID, $subcommunityIDs, true) !== false;
        return (bool) $result;
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
        $comment = (array)$comment;
        $commentID = $comment['CommentID'];
        $path = "/discussion/comment/{$commentID}#Comment_{$commentID}";

        // This isn't normally on the comment record, but may come across as part of a search result.
        $categoryID = val('CategoryID', $comment);

        if (!$categoryID) {
            // This would normally be on the comment record, but may not come across as part of a search result.
            $discussionID = val('DiscussionID', $comment);

            // Try to dig up the discussion ID by looking up the comment.
            if ($discussionID === false) {
                $commentModel = new CommentModel();
                $comment = $commentModel->getID($commentID, DATASET_TYPE_ARRAY);
                if ($comment) {
                    $discussionID = $comment['DiscussionID'];
                }
            }

            // Try to dig up the category ID by looking up the discussion.
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
            if ($discussion) {
                $categoryID = $discussion['CategoryID'];
            }
        }

        if (!$categoryID) {
            return '/home/notfound';
        }

        return Gdn::getContainer()
            ->get(SubcommunitiesPlugin::class)
            ->subcommunityURL($categoryID, $path, $withDomain, false, SubcommunitiesPlugin::URL_TYPE_COMMENT);
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
        $discussion = (array)$discussion;
        $name = Gdn_Format::url($discussion['Name']);
        $categoryID = $discussion['CategoryID'];
        $discussionID = $discussion['DiscussionID'];

        // Disallow an empty name slug in discussion URLs.
        if (empty($name)) {
            $name = 'x';
        }

        $path = "/discussion/$discussionID/$name";

        return Gdn::getContainer()
            ->get(SubcommunitiesPlugin::class)
            ->subcommunityURL($categoryID, $path, $withDomain, $page, SubcommunitiesPlugin::URL_TYPE_DISCUSSION);
    }

}

if (!function_exists('categoryUrl')) {
    /**
     * Return a URL for a category.
     *
     * @param object $category
     * @param int|string $page
     * @param bool $withDomain
     * @return string
     */
    function categoryUrl($category, $page = '', $withDomain = true) {
        if (is_string($category)) {
            $category = CategoryModel::categories($category);
        }
        $category = (array)$category;
        $categoryID = $category['CategoryID'];
        $path = '/categories/'.rawurlencode($category['UrlCode']);
        return Gdn::getContainer()
            ->get(SubcommunitiesPlugin::class)
            ->subcommunityURL($categoryID, $path, $withDomain, $page, SubcommunitiesPlugin::URL_TYPE_CATEGORY);
    }
}
