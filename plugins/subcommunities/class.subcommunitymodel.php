<?php

class SubcommunityModel extends Gdn_Model {
    const CACHE_KEY = 'subcommunities';

    /// Properties ///

    /**
     * @var SubcommunityModel
     */
    protected static $instance;

    protected static $all;

    /**
     * @var Array of subcommunities available to the current user.
     */
    protected static $available;

    protected static $current;

    protected $localeNameTranslations = [
        'ru__PETR1708' => 'ru'
    ];

    /// Methods ///

    public function __construct($name = '') {
        parent::__construct('Subcommunity');

        $this->Validation->addRule('Folder', 'function:validate_folder');
        $this->Validation->applyRule('Folder', 'Folder', '%s must be a valid folder name.');
    }

    public static function addAlternativeUrls() {
        static::all();
        $sites =& static::$all;

        $currentSite = static::getCurrent();
        $currentFolder = $currentSite['Folder'];
        $path = trim(Gdn::request()->path(), '/');

        // Strip the current folder off of the category code.
        if ($baseCategoryCode = Gdn::controller()->data('Category.UrlCode')) {
            $baseCategoryCode = stringBeginsWith($baseCategoryCode, "$currentFolder-", true, true);
            $baseCategoryCode = stringEndsWith($baseCategoryCode, "-$currentFolder", true, true);
        }

        foreach ($sites as &$site) {
            $folder = $site['Folder'];

            if (!$path || $folder === $currentFolder || $currentSite['CategoryID'] == $site['CategoryID']) {
                $site['AlternatePath'] = rtrim("/$path", '/');
                $site['AlternateUrl'] = Gdn::request()->urlDomain('//')."/$folder/$path";
                continue;
            }

            // Try and find an appropriate alternative category.
            if (!($category = CategoryModel::categories("$folder-$baseCategoryCode"))) {
                $category = CategoryModel::categories("$baseCategoryCode-$folder");
            }

            $altPath = $path;
            if (Gdn_Theme::inSection('CategoryList')) {
                if ($category) {
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

            $site['AlternatePath'] = rtrim("/$altPath", '/');
            $site['AlternateUrl'] = rtrim(Gdn::request()->urlDomain('//')."/$folder/$altPath", '/');
        }
    }

    /**
     * Get an array of all multisites indexed by folder.
     */
    public static function all() {
        if (!isset(self::$all)) {
            $all = Gdn::cache()->get(self::CACHE_KEY);
            if (!$all) {
                $all = array_column(
                    static::instance()->getWhere(false, 'Sort,Name')->resultArray(),
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
     * Get a filtered array of all subcommunities available to the current user
     *
     * @return array Subcommunities available to the current user, indexed by folder.
     */
    public static function getAvailable() {
        if (!isset(self::$available)) {
            $all = self::all();
            $available = array();

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
                        $available[$folder] = $row;
                    }
                }
            }

            self::$available = $available;
        }

        return self::$available;
    }

    public static function getDefaultSite() {
        foreach (self::all() as $site) {
            if ($site['IsDefault']) {
                return $site;
            }
        }
        return null;
    }

    public static function getSite($folder) {
        $site = val($folder, static::all(), null);
        return $site;
    }

    /**
     * @return mixed
     */
    public static function getCurrent() {
        return self::$current;
    }

    /**
     * @param mixed $current
     */
    public static function setCurrent($current) {
        self::$current = $current;
    }

    public function calculateRow(&$row) {
        $locale = val('Locale', $row);
        $canonicalLocale = Gdn_Locale::canonicalize($locale);
        if (class_exists('Locale')) {
            $displayLocale = val($canonicalLocale, $this->localeNameTranslations, $canonicalLocale);
            $row['LocaleDisplayName'] = static::mb_ucfirst(Locale::getDisplayName($displayLocale, $canonicalLocale));
        } else {
            $row['LocaleDisplayName'] = $row['Name'];
        }
        $row['Locale'] = $canonicalLocale;
        $row['LocaleShortName'] = str_replace('_', '-', $canonicalLocale);
        $row['Url'] = Gdn::request()->urlDomain('//').'/'.$row['Folder'];
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

    protected static function clearCache() {
        Gdn::cache()->remove(self::CACHE_KEY);
        self::$all = null;
    }

    public function delete($where = '', $Limit = false, $ResetData = false) {
        $result = parent::delete($where, $Limit, $ResetData);
        static::clearCache();
        return $result;
    }

    public function getID($id) {
        $row = parent::getID($id, DATASET_TYPE_ARRAY);
        if ($row) {
            $this->calculateRow($row);
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

    public function insert($Fields) {
        $this->addInsertFields($Fields);
        if ($this->validate($Fields, true)) {
            if (val('IsDefault', $Fields)) {
                $this->SQL->put('Subcommunity', ['IsDefault' => null]);
            }

            static::clearCache();

            return parent::insert($Fields);
        }
    }

    public function update($row, $where) {
        $Result = false;

        // primary key (always included in $Where when updating) might be "required"
        $allFields = $row;
        if (is_array($where)) {
            $allFields = array_merge($row, $where);
        }

        if ($this->validate($allFields)) {
            if (val('IsDefault', $row)) {
                $this->SQL->put('Subcommunity', ['IsDefault' => null], ['SubcommunityID <>' => $allFields['SubcommunityID']]);
            }
            parent::update($row, $where);
            static::clearCache();
        }
    }

    /**
     * Gets the singleton instance of this class.
     *
     * @return SubcommunityModel Returns the singleton instance of this class.
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new SubcommunityModel();
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
}

if (!function_exists('validate_folder')) {
    function validate_folder($value) {
        if (!validateRequired($value)) {
            return true;
        }
        return !preg_match('`[\\/]`', $value);
    }
}
