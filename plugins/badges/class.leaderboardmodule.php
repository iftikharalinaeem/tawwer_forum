<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */

/**
 * Renders a list of badges given to a particular user.
 */
class LeaderBoardModule extends Gdn_Module {

    const CACHE_KEY = 'badge.leaderboard.%s.%s.%s.%s.%s.module';

    /** @var array */
    public $Leaders = [];

    /** @var string  */
    public $SlotType = 'w';

    /** @var int  */
    public $CategoryID = 0;

    /** @var int  */
    public $Limit = 10;

    /** @var int */
    public $CacheTTL;

    /**
     * Create the module instance.
     *
     * @param string $sender
     */
    public function __construct($sender = '') {
        parent::__construct($sender, 'plugins/badges');
        $this->CacheTTL = (int)c('Badges.LeaderBoardModule.CacheDefaultTTL', 600);
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
            $category = CategoryModel::categories($categoryID);
            $categoryID = getValue('PointsCategoryID', $category, 0);
            $category = CategoryModel::categories($categoryID);
            $this->setData('Category', $category);
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

        $cacheKey = sprintf(self::CACHE_KEY, $this->SlotType, $timeSlot, $categoryID, $moderatorIDs ? 1 : 0, $limit);
        $data = Gdn::cache()->get($cacheKey);

        if ($data === Gdn_Cache::CACHEOP_FAILURE) {
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

            Gdn::cache()->store($cacheKey, $data, [
                Gdn_Cache::FEATURE_EXPIRY => (int)$this->CacheTTL,
            ]);
        }


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
                $str = "This Week's Leaders";
                break;
            case 'm':
                $str = "This Month's Leaders";
                break;
            case 'a':
                $str = "All Time Leaders";
                break;
            default:
                $str = "Leaders";
                break;
        }

        if ($this->data('Category')) {
            return sprintf(t($str.' in %s'), htmlspecialchars($this->data('Category.Name')));
        } else {
            return t($str);
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
