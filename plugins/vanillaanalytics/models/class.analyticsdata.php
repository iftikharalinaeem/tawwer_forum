<?php

/**
 * A collection of methods for simplifying the task of gathering information on records.
 * @package VanillaAnalytics
 */
class AnalyticsData extends Gdn_Model {

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
     * Build an array of analytics data for the current user, based on whether or not they are a logged-in user.
     *
     * @return array
     */
    public static function getCurrentUser() {
        return Gdn::session()->isValid() ? self::getUser(Gdn::session()->UserID) : self::getGuest();
    }

    public static function getDateTime($time = 'now', DateTimeZone $timezone = null ) {
        $dateTime = new DateTime($time, $timezone);

        return [
            'day'       => (int)$dateTime->format('j'),
            'dayOfWeek' => (int)$dateTime->format('w'),
            'hour'      => (int)$dateTime->format('G'),
            'minute'    => (int)$dateTime->format('i'),
            'month'     => (int)$dateTime->format('n'),
            'timestamp' => (int)$dateTime->format('U'),
            'timezone'  => $dateTime->format('T'),
            'year'      => (int)$dateTime->format('Y'),
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
}
