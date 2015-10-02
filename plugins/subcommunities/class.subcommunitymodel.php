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

        $this->Validation->AddRule('Folder', 'function:validate_folder');
        $this->Validation->ApplyRule('Folder', 'Folder', '%s must be a valid folder name.');
    }

    public static function addAlternativeUrls() {
        static::all();
        $sites =& static::$all;

        $currentSite = static::getCurrent();
        $currentFolder = $currentSite['Folder'];
        $path = trim(Gdn::Request()->Path(), '/');

        // Strip the current folder off of the category code.
        if ($baseCategoryCode = Gdn::Controller()->Data('Category.UrlCode')) {
            $baseCategoryCode = StringBeginsWith($baseCategoryCode, "$currentFolder-", true, true);
            $baseCategoryCode = StringEndsWith($baseCategoryCode, "-$currentFolder", true, true);
        }

        foreach ($sites as &$site) {
            $folder = $site['Folder'];

            if (!$path || $folder === $currentFolder || $currentSite['CategoryID'] == $site['CategoryID']) {
                $site['AlternatePath'] = rtrim("/$path", '/');
                $site['AlternateUrl'] = Gdn::Request()->UrlDomain('//')."/$folder/$path";
                continue;
            }

            // Try and find an appropriate alternative category.
            if (!($category = CategoryModel::Categories("$folder-$baseCategoryCode"))) {
                $category = CategoryModel::Categories("$baseCategoryCode-$folder");
            }

            $altPath = $path;
            if (Gdn_Theme::InSection('CategoryList')) {
                if ($category) {
                    $altPath = ltrim(CategoryUrl($category, '', '/'), '/');
                }
            } elseif (Gdn_Theme::InSection('DiscussionList')) {
                if ($category) {
                    $altPath = ltrim(CategoryUrl($category, '', '/'), '/');
                } elseif (StringBeginsWith($path, 'discussions')) {
                    $altPath = "discussions";
                } else {
                    $altPath = '';
                }
            } elseif (Gdn_Theme::InSection('Discussion')) {
                $altPath = '';
            }

            $site['AlternatePath'] = rtrim("/$altPath", '/');
            $site['AlternateUrl'] = rtrim(Gdn::Request()->UrlDomain('//')."/$folder/$altPath", '/');
        }
    }

    /**
     * Get an array of all multisites indexed by folder.
     */
    public static function all() {
        if (!isset(self::$all)) {
            $all = Gdn::Cache()->Get(self::CACHE_KEY);
            if (!$all) {
                $all = array_column(
                    static::instance()->getWhere(false, 'Sort,Name')->ResultArray(),
                    null,
                    'Folder'
                );

                Gdn::Cache()->Store(self::CACHE_KEY, $all);
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
        $canonicalLocale = Gdn_Locale::Canonicalize($locale);
        if (class_exists('Locale')) {
            $displayLocale = val($canonicalLocale, $this->localeNameTranslations, $canonicalLocale);
            $row['LocaleDisplayName'] = static::mb_ucfirst(Locale::getDisplayName($displayLocale, $canonicalLocale));
        } else {
            $row['LocaleDisplayName'] = $row['Name'];
        }
        $row['Locale'] = $canonicalLocale;
        $row['LocaleShortName'] = str_replace('_', '-', $canonicalLocale);
        $row['Url'] = Gdn::Request()->UrlDomain('//').'/'.$row['Folder'];
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
        Gdn::Cache()->Remove(self::CACHE_KEY);
        self::$all = null;
    }

    public function delete($where = '', $Limit = FALSE, $ResetData = FALSE) {
        $result = parent::Delete($where, $Limit, $ResetData);
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

    public function getWhere($where = FALSE, $orderFields = '', $orderDirection = 'asc', $limit = FALSE, $offset = FALSE) {
        if (!$limit) {
            $limit = 1000;
        }

        if (empty($where)) {
            $rows = parent::Get($orderFields, $orderDirection, $limit, PageNumber($offset, $limit));
        } else {
            $rows = parent::GetWhere($where, $orderFields, $orderDirection, $limit, $offset);
        }
        array_walk($rows->ResultArray(), [$this, 'calculateRow']);
        return $rows;
    }

    public function insert($Fields) {
        $this->AddInsertFields($Fields);
        if ($this->Validate($Fields, TRUE)) {
            if (val('IsDefault', $Fields)) {
                $this->SQL->Put('Subcommunity', ['IsDefault' => null]);
            }

            static::clearCache();

            return parent::Insert($Fields);
        }
    }

    public function update($row, $where) {
        $Result = FALSE;

        // primary key (always included in $Where when updating) might be "required"
        $allFields = $row;
        if (is_array($where)) {
            $allFields = array_merge($row, $where);
        }

        if ($this->Validate($allFields)) {
            if (val('IsDefault', $row)) {
                $this->SQL->Put('Subcommunity', ['IsDefault' => null], ['SubcommunityID <>' => $allFields['SubcommunityID']]);
            }
            parent::Update($row, $where);
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
    public function search($search, $orderFields = '', $orderDirection = 'asc', $limit = FALSE, $offset = FALSE) {
        if (!$search) {
            return $this->getWhere(false, $orderFields, $orderDirection, $limit, $offset);
        }

        $this->SQL
            ->BeginWhereGroup()
            ->OrLike('Name', $search)
            ->OrLike('Folder', $search)
            ->EndWhereGroup();

        return $this->getWhere(false, $orderFields, $orderDirection, $limit, $offset);
    }
}

if (!function_exists('validate_folder')) {
    function validate_folder($value) {
        if (!ValidateRequired($value)) {
            return true;
        }
        return !preg_match('`[\\/]`', $value);
    }
}
