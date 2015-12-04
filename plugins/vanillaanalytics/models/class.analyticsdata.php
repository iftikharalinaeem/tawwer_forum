<?php

/**
 * A collection of methods for simplifying the task of gathering information on records.
 * @package VanillaAnalytics
 */
class AnalyticsData extends Gdn_Model {

    public static function comment($commentID) {
        $commentModel = new CommentModel();
        $comment = $commentModel->getID($commentID);

        if ($comment) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($comment->DiscussionID);

            if ($discussion) {
                return [
                    'commentID' => (int)$commentID,
                    'discussionID' => (int)$discussion->DiscussionID,
                    'discussionName' => $discussion->Name,
                    'categories' => self::getCategoryAncestors($discussion->CategoryID)
                ];
            } else {
                return [
                    'commentID' => (int)$commentID
                ];
            }
        } else {
            return false;
        }
    }

    /**
     * Grab data about a discussion for use in analytics.
     * @param $discussionID ID of the discussion we're targeting.
     * @return array|bool An array representing the discussion data on success, false on failure.
     */
    public static function discussion($discussionID) {
        // Load up a discussion model and attempt to fetch a record based on the provided ID
        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->getID($discussionID);

        if ($discussion) {
            // We have a valid discussion, so we can put together the basic information using the record.
            return [
                'discussionID' => (int)$discussion->DiscussionID,
                'discussionName' => $discussion->Name,
                'categories' => self::getCategoryAncestors($discussion->CategoryID)
            ];
        } else {
            return false;
        }
    }

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
}
