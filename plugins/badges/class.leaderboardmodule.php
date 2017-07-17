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
    public $Leaders = [];

    /** @var string  */
    public $SlotType = 'w';

    /** @var int  */
    public $CategoryID = 0;

    /** @var int  */
    public $Limit = 10;

    /**
     * Create the module instance.
     *
     * @param string $Sender
     */
    public function __construct($Sender = '') {
        parent::__construct($Sender, 'plugins/badges');
    }

    /**
     * Load module data.
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
        $moderatorIDs = [];
        $rankedPermissions = [
            'Garden.Settings.Manage',
            'Garden.Community.Manage',
            'Garden.Moderation.Manage'
        ];

        if ($excludePermission && in_array($excludePermission, $rankedPermissions)) {
            $moderatorRoleIDs = [];
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

            if ($moderatorRoleIDs) {
                $userModel = new UserModel();
                $moderators = $userModel->getByRole($moderatorRoleIDs)->resultArray();
                $moderatorIDs = array_column($moderators, 'UserID');
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

        if ($moderatorIDs) {
            $leadersSql->whereNotIn('UserID', $moderatorIDs);
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
     * Where the module will render by default.
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
     * Render the module.
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
