<?php

use Vanilla\Exception\Database\NoResultsException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Subcommunities\Models\ProductModel;
use Vanilla\Contracts\Site\SiteSectionInterface;

class SubcommunityModel extends Gdn_Model {
    const CACHE_KEY = 'subcommunities';
    const CACHE_KEY_DEFAULTSITE = 'subcommunities.defaultsite';
    const SITE_CACHE_TTL = 600;

    /// Properties ///

    /**
     * @var SubcommunityModel
     */
    protected static $instance;

    /** @var ProductModel */
    private $productModel;

    protected static $all;

    /**
     * @var Array of subcommunities available to the current user.
     */
    protected static $available;

    protected static $current;

    protected static $localeNameTranslations = [
        'ru__PETR1708' => 'ru'
    ];

    /// Methods ///

    public function __construct(ProductModel $productModel, $name = '') {
        parent::__construct('Subcommunity');

        $this->productModel = $productModel;
        $this->Validation->addRule('Folder', 'function:validate_folder');
        $this->Validation->applyRule('Folder', 'Folder', '%s must be a valid folder name.');
    }

    /**
     * Get an array of all multisites indexed by folder.
     *
     * @see SubcommunityModel::clearCache()
     */
    public static function all() {
        if (!isset(self::$all)) {
            $all = Gdn::cache()->get(self::CACHE_KEY);
            if (!$all) {
                $sql = clone Gdn::sql();
                $sql->reset();
                $result = $sql->getWhere('Subcommunity', false, 'Sort,Name')->resultArray();
                $all = array_column(
                    $result,
                    null,
                    'Folder'
                );

                Gdn::cache()->store(self::CACHE_KEY, $all);
            }
            self::$all = $all;
        }

        return self::$all;
    }

    /**
     * Reinterpret the current URL as represented in another subcommunity site.
     *
     * @param array $site A subcommunity site record.
     * @return string
     */
    public static function getAlternativeUrl($site) {
        $path = trim(Gdn::request()->path(), '/');
        $currentSite = static::getCurrent();
        $currentFolder = $currentSite['Folder'];
        $folder = val('Folder', $site);

        // Strip the current folder off of the category code.
        if ($baseCategoryCode = Gdn::controller()->data('Category.UrlCode')) {
            $baseCategoryCode = stringBeginsWith($baseCategoryCode, "$currentFolder-", true, true);
            $baseCategoryCode = stringEndsWith($baseCategoryCode, "-$currentFolder", true, true);
        }

        // Try and find an appropriate alternative category.
        if (!($category = CategoryModel::categories("$folder-$baseCategoryCode"))) {
            $category = CategoryModel::categories("$baseCategoryCode-$folder");
        }

        $altPath = $path;
        if (Gdn_Theme::inSection('CategoryList')) {
            // If this category is the root category for the current site, don't translate the path.
            if (Gdn::controller()->data('Category.CategoryID') == val('CategoryID', $currentSite)) {
                $altPath = '';
            } elseif ($category) {
                $altPath = ltrim(categoryUrl($category, '', '/'), '/');
            }
        } elseif (Gdn_Theme::inSection('DiscussionList')) {
            if ($category) {
                $altPath = ltrim(categoryUrl($category, '', '/'), '/');
            } elseif (stringBeginsWith($path, 'discussions')) {
                $altPath = "discussions";
            } else {
                $altPath = '';
            }
        } elseif (Gdn_Theme::inSection('Discussion')) {
            $altPath = '';
        }

        $fullPath = "/$folder/$altPath";

        // Are we in a node?  If so, prepend the URL we're building with its slug.
        if (Gdn::addonManager()->isEnabled('sitenode', \Vanilla\Addon::TYPE_ADDON)) {
            $siteNode = Gdn::pluginManager()->getPluginInstance('sitenode', Gdn_PluginManager::ACCESS_PLUGINNAME);
            $nodeSlug = $siteNode->slug();
            if ($nodeSlug) {
                $fullPath = "/{$nodeSlug}/{$folder}/{$altPath}";
            }
        }

        $alternateUrl = rtrim(Gdn::request()->urlDomain('//').$fullPath, '/');
        return $alternateUrl;
    }

    /**
     * Get a filtered array of all subcommunities available to the current user
     *
     * @return array Subcommunities available to the current user, indexed by folder.
     */
    public static function getAvailable() {
        if (!isset(self::$available)) {
            $all = self::all();
            $available = [];

            foreach ($all as $folder => $row) {
                $category = CategoryModel::categories($row['CategoryID']);

                if ($category) {
                    $canView = Gdn::session()->checkPermission(
                        'Vanilla.Discussions.View',
                        true,
                        'Category',
                        $category['PermissionCategoryID']
                    );

                    if ($canView) {
                        self::calculateRow($row);
                        $available[$folder] = $row;
                    }
                }
            }

            self::$available = $available;
        }

        return self::$available;
    }

    /**
     * Get the default site's record.
     *
     * @see SubcommunityModel::clearCache()
     * @return array|null
     */
    public static function getDefaultSite() {
        $defaultSite = Gdn::cache()->get(self::CACHE_KEY_DEFAULTSITE);
        if (!is_array($defaultSite)) {
            $sql = clone Gdn::sql();
            $sql->reset();
            $row = $sql->getWhere('Subcommunity', ['IsDefault' => 1], '', '', 1)->resultArray();
            if (is_array($row) && count($row) === 1) {
                $defaultSite = current($row);
                self::calculateRow($defaultSite);
            }

            Gdn::cache()->store(self::CACHE_KEY_DEFAULTSITE, $defaultSite, [
                Gdn_Cache::FEATURE_EXPIRY => self::SITE_CACHE_TTL
            ]);
        }

        return $defaultSite;
    }

    /**
     * Get a site record.
     *
     * @param string $folder The unique identifier, used to lookup a site.
     * @return array|null
     */
    public static function getSite($folder) {
        $site = null;
        $sql = clone Gdn::sql();
        $sql->reset();
        $row = $sql->getWhere('Subcommunity', ['Folder' => $folder], '', '', 1)->resultArray();
        if (is_array($row) && count($row) === 1) {
            $site = current($row);
            self::calculateRow($site);
        }

        return $site;
    }

    /**
     * @return mixed
     */
    public static function getCurrent() {
        return self::$current;
    }

    /**
     * Determine if slug is reserved for internal use.
     *
     * @param string $slug
     * @return bool
     */
    public static function isReservedSlug($slug): bool {
       /** @var \Vanilla\Contracts\Site\ApplicationProviderInterface $apps */
        $apps = Gdn::getContainer()->get(\Vanilla\Contracts\Site\ApplicationProviderInterface::class);
        return in_array(strtolower($slug), $apps->getReservedSlugs());
    }

    /**
     * @param mixed $current
     */
    public static function setCurrent($current) {
        self::$current = $current;
    }

    /**
     * Unpack and calculate data for a single subcommunity record.
     *
     * @param array $row
     */
    public static function calculateRow(&$row) {
        $locale = val('Locale', $row);
        $canonicalLocale = Gdn_Locale::canonicalize($locale);
        if (class_exists('Locale')) {
            $displayLocale = val($canonicalLocale, self::$localeNameTranslations, $canonicalLocale);
            $row['LocaleDisplayName'] = static::mb_ucfirst(Locale::getDisplayName($displayLocale, $canonicalLocale));
        } else {
            $row['LocaleDisplayName'] = $row['Name'];
        }
        $row['Locale'] = $canonicalLocale;
        $row['LocaleShortName'] = str_replace('_', '-', $canonicalLocale);

        $row['Url'] = Gdn::request()->getSimpleUrl("/{$row['Folder']}");

        if (array_key_exists("Attributes", $row)) {
            $attributes = dbdecode($row['Attributes']);
            if (is_array($attributes)) {
                $row = array_replace($attributes, $row);
                unset($row['Attributes']);
            }
        }
    }

    public static function mb_ucfirst($str, $encoding = "UTF-8", $lower_str_end = false) {
        $first_letter = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding);
        if ($lower_str_end) {
            $str_end = mb_strtolower(mb_substr($str, 1, mb_strlen($str, $encoding), $encoding), $encoding);
        } else {
            $str_end = mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
        }
        $str = $first_letter . $str_end;
        return $str;
    }

    /**
     * Clear the subcommunity cache. Particularly useful for tests.
     */
    public static function clearStaticCache() {
        self::$all = null;
    }

    /**
     * Clear the subcommunity cache.
     */
    protected static function clearCache() {
        Gdn::cache()->remove(self::CACHE_KEY);
        Gdn::cache()->remove(self::CACHE_KEY_DEFAULTSITE);
        self::clearStaticCache();
    }

    /**
     * @inheritdoc
     */
    public function delete($where = [], $options = []) {
        $result = parent::delete($where, $options);
        static::clearCache();
        return $result;
    }

    /**
     * Get a subcommunity record by its ID
     *
     * @param mixed $id The value of the primary key in the database.
     * @param null $datasetType Unused
     * @param null $options Unused
     * @return array|object
     */
    public function getID($id, $datasetType = null, $options = null) {
        $row = parent::getID($id, DATASET_TYPE_ARRAY);
        if ($row) {
            self::calculateRow($row);
        }
        return $row;
    }

    public function getWhere($where = false, $orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {
        if (!$limit) {
            $limit = 1000;
        }

        if (empty($where)) {
            $rows = parent::get($orderFields, $orderDirection, $limit, pageNumber($offset, $limit));
        } else {
            $rows = parent::getWhere($where, $orderFields, $orderDirection, $limit, $offset);
        }
        array_walk($rows->resultArray(), [$this, 'calculateRow']);
        return $rows;
    }

    public function insert($fields) {
        $this->addInsertFields($fields);
        $fields = $this->serialize($fields);
        if ($this->validate($fields, true)) {
            if (val('IsDefault', $fields)) {
                $this->SQL->put('Subcommunity', ['IsDefault' => null]);
            }

            static::clearCache();

            return parent::insert($fields);
        }
    }

    private function serialize($fields) {
        // Get the columns and put the extended data in the attributes.
        $this->defineSchema();
        $columns = $this->Schema->fields();
        $remove = ['TransientKey' => 1, 'hpt' => 1, 'Save' => 1, 'Checkboxes' => 1];
        $fields = array_diff_key($fields, $remove);
        $attributes = array_diff_key($fields, $columns);

        if (!empty($attributes)) {
            $fields['Attributes'] = dbencode($attributes);
        }
        return $fields;
    }

    public function update($row, $where = false, $limit = false) {
        // primary key (always included in $Where when updating) might be "required"
        $allFields = $row;
        if (is_array($where)) {
            $allFields = array_merge($row, $where);
        }

        if ($this->validate($allFields)) {
            if (val('IsDefault', $row)) {
                $this->SQL->put('Subcommunity', ['IsDefault' => null], ['SubcommunityID <>' => $allFields['SubcommunityID']]);
            }
            parent::update($this->serialize($row), $where, $limit);
            static::clearCache();
        }
    }

    /**
     * Gets the singleton instance of this class.
     *
     * @param SubcommunityModel|null $instance
     * @return SubcommunityModel Returns the singleton instance of this class.
     */
    public static function instance(?SubcommunityModel $instance = null): SubcommunityModel {
        if ($instance instanceof SubcommunityModel) {
            self::$instance = $instance;
        } elseif (!isset(self::$instance)) {
            self::$instance = \Gdn::getContainer()->get(\SubcommunityModel::class);
        }
        return self::$instance;
    }

    /**
     * Search for sites based on a search string.
     *
     * @param string $search
     * @param string $orderFields
     * @param string $orderDirection
     * @param int|bool $limit
     * @param int|bool $offset
     * @return Gdn_DataSet
     */
    public function search($search, $orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {
        if (!$search) {
            return $this->getWhere(false, $orderFields, $orderDirection, $limit, $offset);
        }

        $this->SQL
            ->beginWhereGroup()
            ->orLike('Name', $search)
            ->orLike('Folder', $search)
            ->endWhereGroup();

        return $this->getWhere(false, $orderFields, $orderDirection, $limit, $offset);
    }

    /**
     * @param array $formPostValues
     * @param bool $insert
     * @return bool
     */
    public function validate($formPostValues, $insert = false) {
        $this->defineSchema();

        $slug = val('Folder', $formPostValues);
        if (self::isReservedSlug($slug)) {
            $this->Validation->addValidationResult('Folder', 'Folder is reserved for system use.');
        }

        return $this->Validation->validate($formPostValues, $insert);
    }

    /**
     * Ensure that product has been assigned to a subcommunity.
     *
     * @param $formPostValues
     */
    public function validateProduct(array $formPostValues): void {
        $product = $formPostValues['ProductID'] ?? null;
        if (!$product) {
                $this->Validation->addValidationResult('ProductID', 'A product must be assigned to a subcommunity');
        } else {
            try {
                $this->productModel->selectSingle(['productID' =>$product]);
            } catch (NoResultsException $e) {
                $this->Validation->addValidationResult('ProductID', 'The specified product doesn\'t exist');
            }
        }
    }

    /**
     * Ensure that at least one app is enabled.
     *
     * @param array $formPostValues
     * @param string $defaultHomepage
     */
    public function validateApps(array $formPostValues, string $defaultHomepage): void {
        if ((bool)($formPostValues[SiteSectionInterface::APP_KB] ?? false)
            && (bool)($formPostValues[SiteSectionInterface::APP_FORUM] ?? false)) {
            $this->Validation->addValidationResult($formPostValues[SiteSectionInterface::APP_KB], 'At least one application should stay enabled.');
        }
        if ((bool)($formPostValues[SiteSectionInterface::APP_FORUM] ?? false)) {
            $homepage = $formPostValues['defaultController'] ?? $defaultHomepage;
            if (in_array($homepage, ['discussions', 'categories'])) {
                $this->Validation->addValidationResult(
                    $formPostValues['defaultController'],
                    'Selected homepage belongs to disabled forum application.'
                );
            }
        }
        if ((bool)($formPostValues[SiteSectionInterface::APP_KB] ?? false)) {
            $homepage = $formPostValues['defaultController'] ?? $defaultHomepage;
            if ($homepage === 'kb') {
                $this->Validation->addValidationResult(
                    $formPostValues['defaultController'],
                    'Selected homepage belongs to disabled knowledge base application.'
                );
            }
        }
    }
}

if (!function_exists('validate_folder')) {
    function validate_folder($value) {
        if (!validateRequired($value)) {
            return true;
        }
        return !preg_match('`[\\/]`', $value);
    }
}
