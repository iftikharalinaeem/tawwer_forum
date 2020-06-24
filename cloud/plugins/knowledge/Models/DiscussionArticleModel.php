<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use CommentsApiController;
use DiscussionsApiController;
use Garden\Web\Exception\NotFoundException;

/**
 * A model for aiding in converting a discussion to an article.
 */
class DiscussionArticleModel {

    /** @var CommentsApiController */
    private $commentsApiController;

    /** @var DiscussionsApiController */
    private $discussionsApiController;

    /**
     * Setup the model.
     *
     * @param CommentsApiController $commentsApiController
     * @param DiscussionsApiController $discussionsApiController
     */
    public function __construct(CommentsApiController $commentsApiController, DiscussionsApiController $discussionsApiController) {
        $this->commentsApiController = $commentsApiController;
        $this->discussionsApiController = $discussionsApiController;
    }

    /**
     * Given a discussion, analyze and massage its data into a structure that can easily be consumed to create an article.
     *
     * @param integer $discussionID
     * @throws NotFoundException If the discussion does not exist.
     */
    public function discussionData(int $discussionID) {
        $discussion = $this->discussionsApiController->get($discussionID, []);
        $discussionRaw = $this->discussionsApiController->get_edit($discussionID);

        $result = [
            "body" => $this->discriminateBody($discussion, $discussionRaw),
            "format" => $discussionRaw["format"],
            "name" => $discussion["name"],
            "url" => $discussion["url"],
        ];

        if (isset($discussion["attributes"]["question"]["acceptedAnswers"])
            && is_array($discussion["attributes"]["question"]["acceptedAnswers"])) {
            $result["acceptedAnswers"] = $this->formatAcceptedAnswers($discussion["attributes"]["question"]["acceptedAnswers"]);
        }

        return $result;
    }

    /**
     * Extract the proper body field and format it, as necessary.
     *
     * @param array $post A copy of the post containing rendered markup for the body.
     * @param array $postRaw A copy of the post containing raw, unrendered body content.
     */
    private function discriminateBody(array $post, array $postRaw) {
        if (!array_key_exists("body", $post)) {
            throw new \Exception("No body field found in post.");
        }
        if (!array_key_exists("body", $postRaw)) {
            throw new \Exception("No body field found in raw post.");
        }


        if (array_key_exists("format", $postRaw) && strtolower($postRaw["format"]) === "rich") {
            return json_decode($postRaw["body"], true);
        } else {
            return $post["body"];
        }
    }

    /**
     * Format a discussions "accepted answers" field into a structure to be consumed for an article.
     *
     * @param array $acceptedAnswers
     * @return array
     */
    private function formatAcceptedAnswers(array $acceptedAnswers): array {
        $result = [];

        foreach ($acceptedAnswers as $answer) {
            $commentID = $answer["commentID"] ?? null;
            if ($commentID) {
                $comment = $this->commentsApiController->get($commentID, []);
                $commentRaw = $this->commentsApiController->get_edit($commentID);
                $result[] = [
                    "body" => $this->discriminateBody($comment, $commentRaw),
                    "format" => $commentRaw["format"],
                    "url" => $comment["url"],
                ];
            }
        }

        return $result;
    }
}
