<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */

// We can't rely on our autoloader in a plugin.
require_once(dirname(__FILE__).'/class.badgesappmodel.php');
 
/**
 * Badge handling.
 */
class BadgeModel extends BadgesAppModel {
    /**
     * Class constructor. Defines the related database table name.
     *
     * @access public
     */
    public function __construct() {
        parent::__construct('Badge');
    }

    /**
     * Set default select conditions.
     */
    protected function _beforeGet() {

    }

    /**
     * Prep data for single badge or array of badges.
     *
     * @param array $Badge
     */
    public function calculate(&$Badge) {
        if (is_array($Badge) && isset($Badge[0])) {
            // Multiple badges
            foreach ($Badge as &$B) {
                $this->_calculate($B);
            }
        } elseif ($Badge) {
            // One valid result
            $this->_calculate($Badge);
        }
    }

    /**
     * Prep badge data.
     */
    protected function _calculate(&$Badge) {
        if (isset($Badge['Attributes']) && !empty($Badge['Attributes'])) {
            $Badge['Attributes'] = @unserialize($Badge['Attributes']);
        } else {
            $Badge['Attributes'] = array();
        }

        $Badge['Photo'] = Gdn_Upload::url($Badge['Photo']);
    }

    /**
     * Create a new badge. Do not modify an existing badge.
     */
    public function define($Data) {
        $Slug = val('Slug', $Data);
        $ExistingBadge = $this->getID($Slug);

        return ($ExistingBadge) ? val('BadgeID', $ExistingBadge) : $this->save($Data);
    }

    /**
     * Return list of badges with only latest in each class.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $Badges
     * @return array Filtered badge list.
     */
    public static function filterByClass($Badges) {
        $FilteredBadges = array();

        foreach ($Badges as $Badge) {
            $Class = val('Class', $Badge);

            // Keep highest level badge of each class and all classless badges
            if ($Class) {
                if (isset($FilteredBadges[$Class])) {
                    if (val('Level', $Badge) > val('Level', $FilteredBadges[$Class])) {
                        $FilteredBadges[$Class] = $Badge;
                    }
                } else {
                    $FilteredBadges[$Class] = $Badge;
                }
            } else {
                $FilteredBadges[] = $Badge;
            }
        }

        return $FilteredBadges;
    }

    /**
     * Get badges of a single type.
     *
     * @param string $Type Valid: Custom, Manual, UserCount, Timeout, DiscussionContent.
     * @return Dataset
     */
    public function getByType($Type) {
        $Result = $this->getWhere(array('Type' => $Type), 'Threshold', 'desc')->resultArray();
        $this->calculate($Result);
        return $Result;
    }

    /**
     * Get a single badge by ID, slug, or data array.
     *
     * @param mixed $Badge Int, string, or array.
     * @return mixed Array if badge exists or false.
     */
    public function getID($Badge) {
        if (is_numeric($Badge)) {
            $Result = parent::getID($Badge, DATASET_TYPE_ARRAY);
        } elseif (is_string($Badge)) {
            $Result = $this->getWhere(array('Slug' => $Badge))->firstRow(DATASET_TYPE_ARRAY);
        } elseif (is_array($Badge)) {
            $Result = $Badge;
        } else {
            return false;
        }

        if ($Result) {
            $this->calculate($Result);
            return $Result;
        }

        return false;
    }

    /**
     * Get badges list for viewing.
     */
    public function getList() {
        if (!CheckPermission('Reputation.Badges.Give') && !CheckPermission('Garden.Settings.Manage')) {
            $this->SQL->where('Visible', 1);
        }

        $this->SQL->orderBy('Class, Threshold, Name', 'asc');

        return $this->get();
    }

    /**
     * Get badges list for public viewing.
     */
    public function getFilteredList($UserID, $Exclusive = false) {
        $ListQuery = $this->SQL
            ->select('b.*')
            ->select('ub.DateInserted', '', 'DateGiven')
            ->select('ub.InsertUserID', '', 'GivenByUserID')
            ->select('ub.Reason')
            ->from('Badge b');

        // Only badges this user has earned
        if ($Exclusive) {
            $ListQuery->join('UserBadge ub', 'b.BadgeID = ub.BadgeID AND ub.UserID = '.intval($UserID).' AND ub.DateCompleted is not null');
        } // All badges, highlighting user's earned badges
        else {
            $ListQuery->leftJoin('UserBadge ub', 'b.BadgeID = ub.BadgeID AND ub.UserID = '.intval($UserID).' AND ub.DateCompleted is not null');
        }

        $Badges = $ListQuery->where('Visible', 1)
            ->where('Active', 1)
            ->orderBy('Name', 'asc')
            ->get()->resultArray();

        $this->calculate($Badges);
        return $Badges;
    }

    /**
     * Get badges for dropdown.
     */
    public function getMenu() {
        $this->SQL
            ->where('Active', 1)
            ->orderBy('Name', 'asc');
        return $this->get();
    }

    /**
     * Insert or update badge data.
     *
     * @param array $Data The badge we're creating or updating.
     */
    public function save($Data) {
        // See if there is an existing badge.
        if (val('Slug', $Data) && !val('BadgeID', $Data)) {
            $ExistingBadge = $this->setID($Data['Slug']);
            if ($ExistingBadge) {
                $Different = false;
                foreach ($Data as $Key => $Value) {
                    if (array_key_exists($Key, $ExistingBadge) && $ExistingBadge[$Key] != $Value) {
                        $Different = true;
                        break;
                    }
                }
                if (!$Different) {
                    return $ExistingBadge['BadgeID'];
                }
                $Data['BadgeID'] = $ExistingBadge['BadgeID'];

            }
        }
        if (isset($Data['Attributes']) && is_array($Data['Attributes'])) {
            $Data['Attributes'] = serialize($Data['Attributes']);
        }
        if (!isset($Data['BadgeID'])) {
            TouchValue('Threshold', $Data, 0);
        }

        // Strict-mode.
        if (isset($Data['Level']) && !is_int($Data['Level'])) {
            $Data['Level'] = null;
        }

        return parent::save($Data);
    }
}
