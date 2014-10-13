<?php

class MinisiteModel extends Gdn_Model {
    const CACHE_KEY = 'minisites';

    /// Properties ///

    /**
     * @var MinisiteModel
     */
    protected static $instance;

    protected static $all;

    /// Methods ///

    public function __construct($name = '') {
        parent::__construct('Minisite');

        $this->Validation->AddRule('Folder', 'function:validate_folder');
        $this->Validation->ApplyRule('Folder', 'Folder', '%s must be a valid folder name.');
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

    public function calculateRow(&$row) {
        $locale = val('Locale', $row);
        if ($locale === 'en_CA' || $locale === 'en-CA') {
            $locale = 'en';
        }
        if (class_exists('Locale')) {
            $row['LocaleDisplayName'] = static::mb_ucfirst(Locale::getDisplayName($locale, $locale));
        } else {
            $row['LocaleDisplayName'] = $row['Name'];
        }
        $row['LocaleShortName'] = str_replace('_', '-', $locale);
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
                $this->SQL->Put('Minisite', ['IsDefault' => null]);
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
                $this->SQL->Put('Minisite', ['IsDefault' => null], ['MinisiteID <>' => $allFields['MinisiteID']]);
            }
            parent::Update($row, $where);
            static::clearCache();
        }
    }

    /**
     * Gets the singleton instance of this class.
     *
     * @return MinisiteModel Returns the singleton instance of this class.
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new MinisiteModel();
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
