<?php
/**
 * AnalyticsData class file.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */

/**
 * A collection of methods for simplifying the task of gathering information on records.
 */
class AnalyticsData extends Gdn_Model {

    /**
     * @var DateTimeZone An instance of DateTimeZone for date calculations.
     */
    private static $defaultTimeZone = null;

    /**
     * Grab basic information about a category, based on a category ID.
     *
     * @param int $categoryID The target category's integer ID.
     * @return array An array representing basic category data.
     */
    public static function getCategory($categoryID) {
        $categoryModel = new CategoryModel();

        $categoryDetails = $categoryModel->getID($categoryID);
        if ($categoryDetails) {
            $category = [
                'categoryID' => (int)$categoryDetails->CategoryID,
                'name' => $categoryDetails->Name,
                'slug' => $categoryDetails->UrlCode
            ];
        } else {
            // Fallback category data
            $category = ['categoryID' => 0];
        }

        return $category;
    }

    /**
     * Build and return an array mapping category IDs to category names.
     *
     * @return array
     */
    public static function getCategoryMap() {
        $categories = [];
        $categoriesRaw = Gdn::sql()
            ->select('CategoryID')
            ->select('Name')
            ->from('Category')
            ->get()
            ->resultArray();

        foreach ($categoriesRaw as $category) {
            $categories[$category['CategoryID']] = $category['Name'];
        }

        return $categories;
    }

    /**
     * Fetch all ancestors up to, and including, the current category.
     *
     * @param integer $categoryID ID of the category we're tracking down the ancestors of.
     * @return array An array of objects containing the ID and name of each of the category's ancestors.
     */
    public static function getCategoryAncestors($categoryID) {
        $ancestors = [];

        // Grab our category's ancestors, which include the current category.
        $categories = CategoryModel::getAncestors($categoryID);

        $categoryLevel = 0;
        foreach ($categories as $currentCategory) {
            $categoryLabel = 'cat' . sprintf('%02d', ++$categoryLevel);

            $ancestors[$categoryLabel] = [
                'categoryID' => (int)$currentCategory['CategoryID'],
                'name' => $currentCategory['Name'],
                'slug' => $currentCategory['UrlCode']
            ];
        }

        return $ancestors;
    }

    /**
     * Grab standard data for a comment.
     *
     * @param integer $commentID A comment's unique ID, used to query data.
     * @param string $type Event type (e.g. comment_add or comment_edit).
     * @return array|bool Array representing comment row on success, false on failure.
     */
    public static function getComment($commentID, $type = 'comment_add') {
        $commentModel = new CommentModel();
        $comment = $commentModel->getID($commentID);

        if ($comment) {
            $data = [
                'commentID' => (int)$commentID,
                'dateInserted' => self::getDateTime($comment->DateInserted),
                'discussionID' => (int)$comment->DiscussionID,
                'insertUser' => self::getUser($comment->InsertUserID)
            ];
            $discussion = self::getDiscussion($comment->DiscussionID);

            if ($discussion) {
                $commentNumber = val('countComments', $discussion, 0);

                $data['category'] = val('category', $discussion);
                $data['categoryAncestors'] = val('categoryAncestors', $discussion);
                $data['discussionUser'] = val('discussionUser', $discussion);

                $timeSinceDiscussion = $data['dateInserted']['timestamp'] - $discussion['dateInserted']['timestamp'];
                $data['commentMetric'] = [
                    'firstComment' => $commentNumber === 0 ? true : false,
                    'time' => (int)$timeSinceDiscussion
                ];

                // The count of comments we get from the discussion doesn't include this one, so we compensate.
                $commentPosition = ($commentNumber + 1);
                $discussion['countComments'] = $commentPosition;

                if ($type == 'comment_add') {
                    $data['commentPosition'] = $commentPosition;
                } else {
                    $data['commentPosition'] = 0;
                }

                // Removing those redundancies...
                unset(
                    $discussion['category'],
                    $discussion['categoryAncestors'],
                    $discussion['commentMetric'],
                    $discussion['discussionUser']
                );

                $data['discussion'] = $discussion;
            }

            return $data;
        } else {
            // Fallback comment data
            return ['commentID' => 0];
        }
    }

    /**
     * Grab the default time zone for creating dates/times.
     *
     * @return DateTimeZone
     */
    public static function getDefaultTimeZone() {
        if (is_null(self::$defaultTimeZone)) {
            self::$defaultTimeZone = new DateTimeZone('UTC');
        }

        return self::$defaultTimeZone;
    }

    /**
     * Build an array of analytics data for the current user, based on whether or not they are a logged-in user.
     *
     * @return array
     */
    public static function getCurrentUser() {
        return Gdn::session()->isValid() ? self::getUser(Gdn::session()->UserID) : self::getGuest();
    }

    /**
     * Filter elements from getDateTime down to date-only fields.
     *
     * @param string $time Time to breakdown.
     * @param DateTimeZone|null $timeZone Time zone to represent the specified time in.
     * @return array
     */
    public static function getDate($time = 'now', DateTimeZone $timeZone = null) {
        $dateTime = self::getDateTime($time, $timeZone);

        return [
            'year' => $dateTime['year'],
            'month' => $dateTime['month'],
            'day' => $dateTime['day'],
            'dayOfWeek' => $dateTime['dayOfWeek']
        ];
    }

    /**
     * Grab an array of date/time parts representing the specified date/time.
     *
     * @param string $time Time to breakdown.
     * @param DateTimeZone|null $timeZone Time zone to represent the specified time in.
     * @return array
     */
    public static function getDateTime($time = 'now', DateTimeZone $timeZone = null ) {
        $dateTime = new DateTime(
            $time,
            is_null($timeZone) ? self::getDefaultTimeZone() : $timeZone
        );

        $startOfWeek = $dateTime->format('w') === 0 ? 'today' : 'last sunday';

        return [
            'year' => (int)$dateTime->format('Y'),
            'month' => (int)$dateTime->format('n'),
            'day' => (int)$dateTime->format('j'),
            'hour' => (int)$dateTime->format('G'),
            'minute' => (int)$dateTime->format('i'),
            'dayOfWeek' => (int)$dateTime->format('w'),
            'startOfWeek' => (int)strtotime($startOfWeek, $dateTime->format('U')),
            'timestamp' => (int)$dateTime->format('U'),
            'timeZone' => $dateTime->format('T'),
        ];
    }

    /**
     * Grab data about a discussion for use in analytics.
     *
     * @param integer $discussionID ID of the discussion we're targeting.
     * @return array|bool An array representing the discussion data on success, false on failure.
     */
    public static function getDiscussion($discussionID) {
        // Load up a discussion model and attempt to fetch a record based on the provided ID
        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->getID($discussionID);

        if ($discussion) {
            // We have a valid discussion, so we can put together the basic information using the record.
            return [
                'category' => self::getCategory($discussion->CategoryID),
                'categoryAncestors' => self::getCategoryAncestors($discussion->CategoryID),
                'commentMetric' => [
                    'firstComment' => false,
                    'time' => null
                ],
                'countComments' => (int)$discussion->CountComments,
                'dateInserted' => self::getDateTime($discussion->DateInserted),
                'discussionID' => (int)$discussion->DiscussionID,
                'discussionType' => $discussion->Type,
                'discussionUser' => self::getUser($discussion->InsertUserID),
                'name' => $discussion->Name
            ];
        } else {
            // Fallback discussion data
            return [
                'discussionID' => 0
            ];
        }
    }

    /**
     * Retrieve site information.  Use Infrastructure class, if available.  If not, try to load config values.
     *
     * @return array Basic information related to the current site.
     */
    public static function getSite() {
        if (class_exists('\Infrastructure')) {
            $site = [
                'accountID' => \Infrastructure::site('accountid'),
                'name' => \Infrastructure::site('name'),
                'siteID' => \Infrastructure::site('siteid')
            ];
        } else {
            $site = [
                'accountID' => c('Vanilla.VanillaForums.AccountID', null),
                'name'  => c('Garden.Domain', null),
                'siteID' => c('Vanilla.VanillaForums.SiteID', null)
            ];
        }

        return $site;
    }

    /**
     * Build and return guest data for the current user.
     *
     * @todo Add cookie and session values
     * @return array An array representing analytics data for the current user as a guest.
     */
    public static function getGuest() {
        return [
            'dateFirstVisit' => null,
            'name' => '@guest',
            'roleType' => 'guest',
            'userID' => 0
        ];
    }

    /**
     * Retrieve information about a particular user for user in analytics.
     *
     * @todo Add topBadge
     * @param integer $userID Record ID of the user to fetch.
     * @return array|bool An array representing the user data on success, false on failure.
     */
    public static function getUser($userID) {
        $userModel = Gdn::userModel();
        $user = $userModel->getID($userID);
        $roles = [];
        $trackingIDs = AnalyticsTracker::getInstance()->trackingIDs();

        if ($user) {
            /**
             * Fetch the target user's roles.  If we have any (and we should), iterate through them and grab the
             * relevant attributes.
             */
            $userRoles = $userModel->getRoles($userID);
            if ($userRoles->count() > 0) {
                foreach ($userRoles->resultObject() as $currentRole) {
                    $roles[] = [
                        'name' => $currentRole->Name,
                        'roleID' => $currentRole->RoleID,
                        'type' => $currentRole->Type
                    ];
                }
            }

            if ($userModel->checkPermission($user, 'Garden.Settings.Manage')) {
                $roleType = 'admin';
            } elseif ($userModel->checkPermission($user, 'Garden.Community.Manage')) {
                $roleType = 'cm';
            } elseif ($userModel->checkPermission($user, 'Garden.Moderation.Manage')) {
                $roleType = 'mod';
            } else {
                $roleType = 'member';
            }

            $userInfo = [
                'commentCount' => (int)$user->CountComments,
                'dateFirstVisit' => self::getDateTime($user->DateFirstVisit),
                'dateRegistered' => self::getDateTime($user->DateInserted),
                'discussionCount' => (int)$user->CountDiscussions,
                'name' => $user->Name,
                'roles' => $roles,
                'roleType' => $roleType,
                'userID' => (int)$user->UserID,
                'uuid' => $trackingIDs['uuid'],
                'sessionID' => $trackingIDs['sessionID']
            ];

            $timeFirstVisit = strtotime($user->DateFirstVisit) ?: 0;
            $timeRegistered = strtotime($user->DateInserted) ?: 0;

            $userInfo['sinceFirstVisit'] = time() - $timeFirstVisit;
            $userInfo['sinceRegistered'] = time() - $timeRegistered;

            $userInfo['points'] = val('Points', $user, 0);

            // Attempto to fetch rank info for the current user.
            if (($rankID = val('RankID', $user)) && class_exists('RankModel')) {
                // If the rank ID is invalid, RankModel::ranks will return a null value.
                $rankDetails = RankModel::ranks($rankID);
            } else {
                $rankDetails = null;
            }

            // Did the user have a rank ID and was it valid?
            if (!is_null($rankDetails)) {
                $rank = [
                    'label' => $rankDetails['Label'],
                    'level' => $rankDetails['Level'],
                    'name' => $rankDetails['Name'],
                    'rankID' => $rankDetails['RankID']
                ];
            } else {
                // Fallback rank data
                $rank = ['rankID' => 0];
            }

            $userInfo['rank'] = $rank;

            return $userInfo;
        } else {
            // Fallback user data
            return [
                'userID' => 0,
                'name' => '@notfound',
            ];
        }
    }

    /**
     * Grab the saved UUID for a user or a new one for a guest.
     *
     * @param bool $create Should a new UUID be created and saved for a user if one doesn't exist?
     * @return string A universally unique identifier.
     */
    public static function getUserUuid($create = true) {
        if (Gdn::session()->isValid()) {
            $user = Gdn::userModel()->getID(Gdn::session()->UserID);
            $attributes = UserModel::attributes($user);
            $uuid = val('UUID', $attributes, null);

            if (empty($uuid) && $create) {
                $uuid = self::uuid();
                Gdn::userModel()->saveAttribute(Gdn::session()->UserID, 'UUID', $uuid);
            }
        } else {
            $uuid = self::uuid();
        }

        return $uuid;
    }

    /**
     * Set the default time zone to the specified parameter.
     *
     * @link http://php.net/manual/en/timezones.php
     * @param DateTimeZone $timeZone Target time zone.
     */
    public static function setDefaultTimeZone(DateTimeZone $timeZone) {
        self::$defaultTimezone = $timeZone;
    }

    /**
     * Generate a random universally unique identifier.
     *
     * @link https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_4_.28random.29
     * @link http://php.net/manual/en/function.uniqid.php#94959
     * @return string A UUIDv4-compliant string
     */
    public static function uuid() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
