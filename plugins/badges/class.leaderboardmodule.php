<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */

/**
 * Renders a list of badges given to a particular user.
 */
class LeaderBoardModule extends Gdn_Module {

    /** @var array */
    public $Leaders = array();

    /** @var string  */
    public $SlotType = 'w';

    /** @var int  */
    public $CategoryID = 0;

    /** @var int  */
    public $Limit = 10;

    /**
     *
     *
     * @param string $Sender
     */
    public function __construct($Sender = '') {
        parent::__construct($Sender, 'plugins/badges');
    }

    /**
     *
     *
     * @param null $Limit
     */
    public function getData($Limit = null) {
        if (!$Limit) {
            $Limit = $this->Limit;
        }

        $TimeSlot = gmdate('Y-m-d', Gdn_Statistics::timeSlotStamp($this->SlotType, false));

        $CategoryID = $this->CategoryID;
        if ($CategoryID) {
            $Category = CategoryModel::categories($CategoryID);
            $CategoryID = GetValue('PointsCategoryID', $Category, 0);
            $Category = CategoryModel::categories($CategoryID);
            $this->setData('Category', $Category);
        } else {
            $CategoryID = 0;
        }

        $Data = Gdn::sql()->getWhere(
            'UserPoints',
            array('TimeSlot' => $TimeSlot, 'SlotType' => $this->SlotType, 'Source' => 'Total', 'CategoryID' => $CategoryID),
            'Points',
            'desc',
            $Limit
        )->resultArray();

        Gdn::userModel()->joinUsers($Data, array('UserID'));

        $this->Leaders = $Data;
    }

    /**
     *
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     *
     *
     * @return string
     */
    public function title() {
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

        if ($this->data('Category')) {
            return sprintf(T($Str.' in %s'), htmlspecialchars($this->data('Category.Name')));
        } else {
            return T($Str);
        }
    }

    /**
     *
     *
     * @return string
     */
    public function toString() {
        if (empty($this->Leaders)) {
            $this->getData();
        }
        return parent::toString();
    }
}
