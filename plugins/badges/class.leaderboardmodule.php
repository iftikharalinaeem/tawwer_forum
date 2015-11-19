<?php
/**
 * @copyright 2011 Vanilla Forums Inc
 * @package Reputation
 */

/**
 * Renders a list of badges given to a particular user.
 */
class LeaderBoardModule extends Gdn_Module {
    /** @var array */
    public $Leaders = array();
    public $SlotType = 'w';
    public $CategoryID = 0;
    public $Limit = 10;

    public function __construct($Sender = '') {
        parent::__construct($Sender, 'plugins/badges');
    }

    public function GetData($Limit = NULL) {
        if (!$Limit)
            $Limit = $this->Limit;

        $TimeSlot = gmdate('Y-m-d', Gdn_Statistics::TimeSlotStamp($this->SlotType, FALSE));

        $CategoryID = $this->CategoryID;
        if ($CategoryID) {
            $Category = CategoryModel::Categories($CategoryID);
            $CategoryID = GetValue('PointsCategoryID', $Category, 0);
            $Category = CategoryModel::Categories($CategoryID);
            $this->SetData('Category', $Category);
        } else {
            $CategoryID = 0;
        }

        $Data = Gdn::SQL()->GetWhere(
            'UserPoints',
            array('TimeSlot' => $TimeSlot, 'SlotType' => $this->SlotType, 'Source' => 'Total', 'CategoryID' => $CategoryID),
            'Points',
            'desc',
            $Limit
            )->ResultArray();

        Gdn::UserModel()->JoinUsers($Data, array('UserID'));

        $this->Leaders = $Data;
    }

    public function AssetTarget() {
        return 'Panel';
    }

    public function Title() {
        switch ($this->SlotType) {
            case 'w':
                $Str = "This Week's Leaders";
                break;
            case 'm':
                $Str = "This Month's Leaders";
                break;
            case 'a':
                $Str = "All Time Leaders";
                break;
            default:
                $Str = "Leaders";
                break;
        }

        if ($this->Data('Category')) {
            return sprintf(T($Str.' in %s'), htmlspecialchars($this->Data('Category.Name')));
        } else {
            return T($Str);
        }
    }

    public function ToString() {
        if (empty($this->Leaders))
            $this->GetData();
        return parent::ToString();
    }
}