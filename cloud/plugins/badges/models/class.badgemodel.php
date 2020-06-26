<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */

/**
 * Badge handling.
 */
class BadgeModel extends Gdn_Model {
    const TYPE_MANUAL = 'Manual';

    /**
     * Class constructor. Defines the related database table name.
     *
     * @access public
     */
    public function __construct() {
        parent::__construct('Badge');
    }

    /**
     * Prep data for single badge or array of badges.
     *
     * @param array $badge
     */
    public function calculate(&$badge) {
        if (is_array($badge) && isset($badge[0])) {
            // Multiple badges
            foreach ($badge as &$b) {
                $this->_calculate($b);
            }
        } elseif ($badge) {
            // One valid result
            $this->_calculate($badge);
        }
    }

    /**
     * Prep badge data.
     */
    protected function _calculate(&$badge) {
        if (isset($badge['Attributes']) && !empty($badge['Attributes'])) {
            $badge['Attributes'] = dbdecode($badge['Attributes']);
        } else {
            $badge['Attributes'] = [];
        }

        if ($badge['Photo'] != '') {
            $badge['Photo'] = Gdn_Upload::url($badge['Photo']);
        }
    }

    /**
     * Create a new badge. Do not modify an existing badge.
     */
    public function define($data) {
        $slug = val('Slug', $data);
        $existingBadge = $this->getID($slug, DATASET_TYPE_ARRAY);

        $existingImage = $existingBadge["Photo"] ?? null;
        $newImage = $data["Photo"] ?? null;

        // Check for a default image upgrade.
        if ($existingBadge && $existingImage) { // Make sure we have an image to upgrade.
            $isVanillaImage = parse_url($existingImage, PHP_URL_HOST) === "badges.v-cdn.net";
            if ($isVanillaImage && $existingImage !== $newImage) { // Only upgrade if it's a default Vanilla badge image.
                $this->setField($existingBadge["BadgeID"], ["Photo" => $newImage]);
            }
        }

        return ($existingBadge) ? ($existingBadge['BadgeID'] ?? 0) : $this->save($data);
    }

    /**
     * Return list of badges with only latest in each class.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $badges
     * @return array Filtered badge list.
     */
    public static function filterByClass($badges) {
        $filteredBadges = [];

        foreach ($badges as $badge) {
            $class = val('Class', $badge);

            // Keep highest level badge of each class and all classless badges
            if ($class) {
                if (isset($filteredBadges[$class])) {
                    if (val('Level', $badge) > val('Level', $filteredBadges[$class])) {
                        $filteredBadges[$class] = $badge;
                    }
                } else {
                    $filteredBadges[$class] = $badge;
                }
            } else {
                $filteredBadges[] = $badge;
            }
        }

        return $filteredBadges;
    }

    /**
     * Get badges of a single type.
     *
     * @param string $type Valid: Custom, Manual, UserCount, Timeout, DiscussionContent.
     * @return Dataset
     */
    public function getByType($type) {
        $result = $this->getWhere(['Type' => $type], 'Threshold', 'desc')->resultArray();
        $this->calculate($result);
        return $result;
    }

    /**
     * Get a single badge by ID, slug, or data array.
     *
     * @param mixed $badge Int, string, or array.
     * @param string $datasetType The format for the badge.
     * @param array $options Not used.
     * @return array|ArrayObject|false Returns the badge or **false** if it isn't found.
     */
    public function getID($badge, $datasetType = false, $options = []) {
        $datasetType = $datasetType ?: DATASET_TYPE_ARRAY;

        if (is_numeric($badge)) {
            $result = parent::getID($badge, DATASET_TYPE_ARRAY);
        } elseif (is_string($badge)) {
            $result = $this->getWhere(['Slug' => $badge])->firstRow(DATASET_TYPE_ARRAY);
        } elseif (is_array($badge)) {
            $result = $badge;
        } else {
            return false;
        }

        if ($result) {
            $this->calculate($result);

            if ($datasetType === DATASET_TYPE_OBJECT) {
                $result = new ArrayObject($result);
            }

            return $result;
        }

        return false;
    }

    /**
     * Get badges list for viewing.
     */
    public function getList() {
        if (!checkPermission('Reputation.Badges.Give') && !checkPermission('Garden.Settings.Manage')) {
            $this->SQL->where('Active', 1);
            $this->SQL->where('Visible', 1);
        }
        $this->SQL->orderBy('Class, Threshold, Name', 'asc');

        return $this->get();
    }

    /**
     * Get badges list for public viewing.
     */
    public function getFilteredList($userID, $exclusive = false) {
        $listQuery = $this->SQL
            ->select('b.*')
            ->select('ub.DateInserted', '', 'DateGiven')
            ->select('ub.InsertUserID', '', 'GivenByUserID')
            ->select('ub.Reason')
            ->from('Badge b');

        // Only badges this user has earned
        if ($exclusive) {
            $listQuery->join('UserBadge ub', 'b.BadgeID = ub.BadgeID AND ub.UserID = '.intval($userID).' AND ub.DateCompleted is not null');
        } // All badges, highlighting user's earned badges
        else {
            $listQuery->leftJoin('UserBadge ub', 'b.BadgeID = ub.BadgeID AND ub.UserID = '.intval($userID).' AND ub.DateCompleted is not null');
        }

        $badges = $listQuery->where('Visible', 1)
            ->where('Active', 1)
            ->orderBy('Name', 'asc')
            ->get()->resultArray();

        $this->calculate($badges);
        return $badges;
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
     * Detect whether a given badge is requestable.
     *
     * @param $data array|object Badge.
     * @return bool Whether it is requestable.
     */
    public static function isRequestable($data) {
        // Disabled badges cannot be requested.
        if (!val('Active', $data)) {
            return false;
        }

        // Non-manual badge types cannot be requested.
        $type = val('Type', $data);
        if ($type == self::TYPE_MANUAL || is_null($type)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Insert or update badge data.
     *
     * @param array $data The badge we're creating or updating.
     * @param array $settings If settings is not included or does not have a 'replaceAttributes' key,
     * it will add any new attributes to the attribute array. If 'replaceAttributes' is set to false,
     * it will overwrite the attribute array.
     * @return int|false Returns the ID of the badge or **false** on error.
     */
    public function save($data, $settings = []) {
        if (!is_array($settings)) {
            $settings = [];
        }
        $settings += [
            'overwriteAttributes' => false,
            ];

        // See if there is an existing badge.
        $existingBadge = null;
        if (val('BadgeID', $data)) {
            $existingBadge = $this->getID((int)$data['BadgeID']);
        } elseif (val('Slug', $data)) {
            $existingBadge = $this->getID($data['Slug']);
        }

        // The second condition of this if-statement--!val('BadgeID', $data)--was part of the function's logic
        // before the most recent refactoring. I'm not sure why it's there and think we could get rid of it, but
        // didn't want to touch it while refactoring to address another issue.
        if ($existingBadge && !val('BadgeID', $data)) {
            $different = false;
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $existingBadge) && $existingBadge[$key] != $value) {
                    $different = true;
                    break;
                }
            }
            if (!$different) {
                return $existingBadge['BadgeID'];
            }
            $data['BadgeID'] = $existingBadge['BadgeID'];
        }

        if (isset($data['Attributes']) && is_array($data['Attributes'])) {
            if ($existingBadge && !$settings['overwriteAttributes']) {
                $data['Attributes'] = array_replace($existingBadge['Attributes'], $data['Attributes']);
            }
            $data['Attributes'] = dbencode($data['Attributes']);
        }
        if (!isset($data['BadgeID'])) {
            touchValue('Threshold', $data, 0);
        }

        // Strict-mode.
        if (isset($data['Level'])) {
            if (is_numeric($data['Level'])) {
                $data['Level'] = (int)$data['Level'];
            } else {
                $data['Level'] = null;
            }
        }

        // Be sure that we won't have the duplicate name (emptystring) since the column is unique.
        if (isset($data['Slug']) && $data['Slug'] === '') {
            $data['Slug'] = null;
        }

        return parent::save($data);
    }
}
