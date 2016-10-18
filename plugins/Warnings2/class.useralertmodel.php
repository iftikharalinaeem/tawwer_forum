<?php

class UserAlertModel extends Gdn_Model {

    public function __construct() {
        parent::__construct('UserAlert');
        $this->PrimaryKey = 'UserID';
    }

    /**
     * @param mixed $ID
     * @param bool $DatasetType Unused
     * @param array $Options Unused
     * @return array
     */
    public function getID($ID, $DatasetType = false, $Options = array()) {
        $Row = parent::getID($ID, DATASET_TYPE_ARRAY);
        if (empty($Row))
            return $Row;
        $Row = $this->expandAttributes($Row);
        return $Row;
    }

    /***
     * Get a dataset for the user alert model with a where filter.
     *
     * @param bool $Where
     * @param string $OrderFields
     * @param string $OrderDirection
     * @param bool $Limit
     * @param bool $Offset
     * @return Gdn_DataSet
     */
    public function getWhere($Where = false, $OrderFields = '', $OrderDirection = 'asc', $Limit = false, $Offset = false) {
        $Data = parent::getWhere($Where, $OrderFields, $OrderDirection, $Limit, $Offset);
        $Data->datasetType(DATASET_TYPE_ARRAY);
        $Data->expandAttributes();
        return $Data;
    }

    /***
     * Set expiring time.
     *
     * @param array $Alert
     * @return int|null
     */
    public function setTimeExpires(&$Alert) {
        $TimeExpires = 0;
        foreach ($Alert as $Name => $Value) {
            if ($Name == 'TimeExpires' || !stringEndsWith($Name, 'Expires'))
                continue;
            if (!$TimeExpires || ($Value && $Value < $TimeExpires))
                $TimeExpires = $Value;
        }
        if (!$TimeExpires)
            $Alert['TimeExpires'] = null;
        else
            $Alert['TimeExpires'] = $TimeExpires;

        return $Alert['TimeExpires'];
    }

    /***
     *
     * @param array $FormPostValues
     * @param bool $Settings
     * @return bool
     */
    public function save($FormPostValues, $Settings = false) {
        $Row = $this->collapseAttributes($FormPostValues);

        $CurrentRow = $this->getID($Row['UserID']);
        if ($CurrentRow) {
            $UserID = $Row['UserID'];
            unset($Row['UserID']);
            if ($this->update($Row, array('UserID' => $UserID)))
                return $UserID;
            else
                return false;
        } else {
            if ($this->insert($Row))
                return $Row['UserID'];
            else
                return false;
        }
    }
}
