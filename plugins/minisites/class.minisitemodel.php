<?php

class MinisiteModel extends Gdn_Model {
    /// Properties ///

    /**
     * @var MinisiteModel
     */
    protected static $instance;

    /// Methods ///

    public function __construct($name = '') {
        parent::__construct('Minisite');

        $this->Validation->AddRule('Folder', 'function:validate_folder');
        $this->Validation->ApplyRule('Folder', 'Folder', '%s must be a valid folder name.');
    }

    public function calculateRow(&$row) {
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
