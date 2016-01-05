<?php

/**
 * A collection of methods for simplifying the task of gathering information on records.
 * @package VanillaAnalytics
 */
class AnalyticsData extends Gdn_Model {

    private static $defaultTimeZone = null;

    /**
     * Fetch all ancestors up to, and including, the current category.
     *
     * @param $categoryID ID of the category we're tracking down the ancestors of.
     * @return array An array of objects containing the ID and name of each of the category's ancestors.
     */
    public static function getCategoryAncestors($categoryID) {
        $ancestors = [];

        $categories = CategoryModel::getAncestors($categoryID);
        foreach ($categories as $currentCategory) {
            $ancestors[] = [
                'categoryID' => (int)$currentCategory['CategoryID'],
                'name' => $currentCategory['Name']
            ];
        }

        return $ancestors;
    }

    /**
     * Grab standard data for a comment.
     *
     * @param $commentID A comment's unique ID, used to query data.
     * @return array|bool Array representing comment row on success, false on failure.
     */
    public static function getComment($commentID) {
        $commentModel = new CommentModel();
        $comment = $commentModel->getID($commentID);

        if ($comment) {
            $data = [
                'commentID' => (int)$commentID
            ];
            $discussion = self::discussion($comment->DiscussionID);

            if ($discussion) {
                $data['discussion'] = $discussion;
            }

            return $data;
        } else {
            return false;
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
            'day'         => (int)$dateTime->format('j'),
            'dayOfWeek'   => (int)$dateTime->format('w'),
            'hour'        => (int)$dateTime->format('G'),
            'minute'      => (int)$dateTime->format('i'),
            'month'       => (int)$dateTime->format('n'),
            'startOfWeek' => (int)strtotime($startOfWeek, $dateTime->format('U')),
            'timestamp'   => (int)$dateTime->format('U'),
            'timeZone'    => $dateTime->format('T'),
            'year'        => (int)$dateTime->format('Y'),
        ];
    }

    /**
     * Grab data about a discussion for use in analytics.
     * @param $discussionID ID of the discussion we're targeting.
     * @return array|bool An array representing the discussion data on success, false on failure.
     */
    public static function getDiscussion($discussionID) {
        // Load up a discussion model and attempt to fetch a record based on the provided ID
        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->getID($discussionID);

        if ($discussion) {
            // We have a valid discussion, so we can put together the basic information using the record.
            return [
                'discussionID' => (int)$discussion->DiscussionID,
                'name' => $discussion->Name,
                'categories' => self::getCategoryAncestors($discussion->CategoryID)
            ];
        } else {
            return false;
        }
    }

    /**
     * Build and return guest data for the current user.
     *
     * @return array An array representing analytics data for the current user as a guest.
     */
    public static function getGuest() {
        return [
            'userID'         => -1,
            'name'           => null,
            'dateFirstVisit' => null
        ];
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
                'name'      => \Infrastructure::site('name'),
                'siteID'    => \Infrastructure::site('siteid')
            ];
        } else {
            $site = [
                'accountID' => c('Vanilla.VanillaForums.AccountID', null),
                'name'      => c('Garden.Domain', null),
                'siteID'    => c('Vanilla.VanillaForums.SiteID', null)
            ];
        }

        return $site;
    }

    /**
     * Retrieve information about a particular user for user in analytics.
     *
     * @param int $userID Record ID of the user to fetch.
     * @return array|bool An array representing the user data on success, false on failure.
     */
    public static function getUser($userID) {
        $user = Gdn::userModel()->getID($userID);

        if ($user) {
            $userInfo = [
                'userID'         => (int)$user->UserID,
                'name'           => $user->Name,
                'timeFirstVisit' => $user->DateFirstVisit
            ];

            return $userInfo;
        } else {
            return false;
        }
    }

    /**
     * Set the default time zone to the specified parameter.
     *
     * @param DateTimeZone $timeZone Target time zone.
     */
    public static function setDefaultTimeZone(DateTimeZone $timeZone) {
        self::$defaultTimezone = $timeZone;
    }
}
