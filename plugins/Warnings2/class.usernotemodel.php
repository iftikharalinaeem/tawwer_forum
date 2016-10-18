<?php

class UserNoteModel extends Gdn_Model {

    public function __construct() {
        parent::__construct('UserNote');
    }

    /***
     * Calculating notes row.
     *
     * @param array $Data
     */
    public function calculate(&$Data) {
        Gdn::userModel()->joinUsers($Data, array('InsertUserID'));
        $IsModerator = Gdn::session()->checkPermission('Garden.Moderation.Manage');
        foreach ($Data as &$Row) {
            $this->calculateRow($Row);
        }
    }

    /***
     * Counting row, unsetting Moderator Note is user is not moderator.
     *
     * @param array $Row
     */
    protected function calculateRow(&$Row) {
        $IsModerator = Gdn::session()->checkPermission('Garden.Moderation.Manage');
        $Row['Body'] = Gdn_Format::to($Row['Body'], $Row['Format']);

        if (!$IsModerator) {
            unset($Row['ModeratorNote']);
        }
    }

    /**
     * Get a UserNote by ID.
     *
     * @param mixed $ID
     * @param bool|false $DatasetType Unused
     * @param array $Options Unused
     * @return array|object
     */
    public function getID($ID, $DatasetType = false, $Options = []) {
        $Row = parent::getID($ID, DATASET_TYPE_ARRAY);
        $Row = $this->expandAttributes($Row);
        $this->calculateRow($Row);
    
        return $Row;
    }

    /***
     * Get a dataset for the model with a where filter.
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
     *
     * @param array $FormPostValues
     * @param bool $Settings
     *
     * @return unknown
     */
    public function save($FormPostValues, $Settings = false) {
        $Row = $this->collapseAttributes($FormPostValues);

        return parent::save($Row, $Settings);
    }

    /***
     * Update a row in the database.
     *
     * @param int $RowID
     * @param array|string $Name
     * @param null $Value
     */
    public function setField($RowID, $Name, $Value = null) {
        if (!is_array($Name))
            $Name = array($Name => $Value);

        $this->defineSchema();
        $Fields = $this->Schema->fields();
        $InSchema = array_intersect_key($Name, $Fields);
        $NotInSchema = array_diff_key($Name, $InSchema);

        if (empty($NotInSchema)) {
            return parent::setField($RowID, $Name);
        } else {
            $Row = $this->SQL->select('Attributes')->getWhere('UserNote', array('UserNoteID' => $RowID))->firstRow(DATASET_TYPE_ARRAY);
            if (isset($Row['Attributes'])) {
                $Attributes = dbdecode($Row['Attributes']);
                if (is_array($Attributes))
                    $Attributes = array_merge($Attributes, $NotInSchema);
                else
                    $Attributes = $NotInSchema;
            } else {
                $Attributes = $NotInSchema;
            }
            $InSchema['Attributes'] = $Attributes;
            return parent::setField($RowID, $InSchema);
        }
    }
}
