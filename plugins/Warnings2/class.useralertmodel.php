<?php

class UserAlertModel extends Gdn_Model {

    public function __construct() {
        parent::__construct('UserAlert');
        $this->PrimaryKey = 'UserID';
    }

    /**
     * @param mixed $iD
     * @param bool $datasetType Unused
     * @param array $options Unused
     * @return array
     */
    public function getID($iD, $datasetType = false, $options = []) {
        $row = parent::getID($iD, DATASET_TYPE_ARRAY);
        if (empty($row)) {
            return $row;
        }
        $row = $this->expandAttributes($row);
        return $row;
    }

    /**
     * Get a dataset for the user alert model with a where filter.
     *
     * @param bool $where
     * @param string $orderFields
     * @param string $orderDirection
     * @param bool $limit
     * @param bool $offset
     * @return Gdn_DataSet
     */
    public function getWhere($where = false, $orderFields = '', $orderDirection = 'asc', $limit = false, $offset = false) {
        $data = parent::getWhere($where, $orderFields, $orderDirection, $limit, $offset);
        $data->datasetType(DATASET_TYPE_ARRAY);
        $data->expandAttributes();
        return $data;
    }

    /**
     * Set expiring time.
     *
     * @param array $alert
     * @return int|null
     */
    public function setTimeExpires(&$alert) {
        $timeExpires = 0;
        foreach ($alert as $name => $value) {
            if ($name == 'TimeExpires' || !stringEndsWith($name, 'Expires')) {
                continue;
            }
            if (!$timeExpires || ($value && $value < $timeExpires)) {
                $timeExpires = $value;
            }
        }
        if (!$timeExpires) {
            $alert['TimeExpires'] = null;
        } else {
            $alert['TimeExpires'] = $timeExpires;
        }

        return $alert['TimeExpires'];
    }

    /**
     *
     * @param array $formPostValues
     * @param bool $settings
     * @return bool
     */
    public function save($formPostValues, $settings = false) {
        $row = $this->collapseAttributes($formPostValues);

        $currentRow = $this->getID($row['UserID']);
        if ($currentRow) {
            $userID = $row['UserID'];
            unset($row['UserID']);
            if ($this->update($row, ['UserID' => $userID])) {
                return $userID;
            } else {
                return false;
            }
        } else {
            if ($this->insert($row)) {
                return $row['UserID'];
            } else {
                return false;
            }
        }
    }
}
