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
        $Row = $this->ExpandAttributes($Row);
        return $Row;
    }

    public function GetWhere($Where = false, $OrderFields = '', $OrderDirection = 'asc', $Limit = false, $Offset = false) {
        $Data = parent::GetWhere($Where, $OrderFields, $OrderDirection, $Limit, $Offset);
        $Data->DatasetType(DATASET_TYPE_ARRAY);
        $Data->ExpandAttributes();
        return $Data;
    }

    public function SetTimeExpires(&$Alert) {
        $TimeExpires = 0;
        foreach ($Alert as $Name => $Value) {
            if ($Name == 'TimeExpires' || !StringEndsWith($Name, 'Expires'))
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

    public function Save($FormPostValues, $Settings = false) {
        $Row = $this->CollapseAttributes($FormPostValues);

        $CurrentRow = $this->GetID($Row['UserID']);
        if ($CurrentRow) {
            $UserID = $Row['UserID'];
            unset($Row['UserID']);
            if ($this->Update($Row, array('UserID' => $UserID)))
                return $UserID;
            else
                return false;
        } else {
            if ($this->Insert($Row))
                return $Row['UserID'];
            else
                return false;
        }
    }
}
