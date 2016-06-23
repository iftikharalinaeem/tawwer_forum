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
    public function getData($limit = null) {
        if (!$limit) {
            $limit = $this->Limit;
        }

        $timeSlot = gmdate('Y-m-d', Gdn_Statistics::timeSlotStamp($this->SlotType, false));

        $categoryID = $this->CategoryID;
        if ($categoryID) {
            $Category = CategoryModel::categories($categoryID);
            $categoryID = GetValue('PointsCategoryID', $Category, 0);
            $Category = CategoryModel::categories($categoryID);
            $this->setData('Category', $Category);
        } else {
            $categoryID = 0;
        }

        $excludePermission = c('Badges.ExcludePermission');
        $moderatorRoleIDs = [];
        $rankedPermissions = [
            'Garden.Settings.Manage',
            'Garden.Community.Manage',
            'Garden.Moderation.Manage'
        ];

        if ($excludePermission && in_array($excludePermission, $rankedPermissions)) {
            $roleModel = new RoleModel();
            $roles = $roleModel->getWithRankPermissions()->resultArray();

            $currentPermissionRank = array_search($excludePermission, $rankedPermissions);

            foreach ($roles as $currentRole) {
                for ($i = 0; $i <= $currentPermissionRank; $i++) {
                    if (val($rankedPermissions[$i], $currentRole)) {
                        $moderatorRoleIDs[] = $currentRole['RoleID'];
                        continue 2;
                    }
                }
            }
        }

        $leadersSql = Gdn::sql()
            ->select([
                'SlotType',
                'TimeSlot',
                'Source',
                'CategoryID',
                'up.UserID',
                'Points'
            ])
            ->from('UserPoints up')
            ->where([
                'TimeSlot' => $timeSlot,
                'SlotType' => $this->SlotType,
                'Source' => 'Total',
                'CategoryID' => $categoryID
            ]);

        if (!empty($moderatorRoleIDs)) {
            $leadersSql->join('UserRole ur', 'up.UserID = ur.UserID', 'left')
                ->whereNotIn('ur.RoleID', $moderatorRoleIDs)
            ->distinct(true);
        }

        $data = $leadersSql
            ->orderBy('Points', 'desc')
            ->limit($limit)
            ->get()
            ->resultArray();

        Gdn::userModel()->joinUsers($data, ['UserID']);

        $this->Leaders = $data;
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
