<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use CategoryModel;
use DiscussionModel;
use ReactionModel;
use StatusModel;
use Vanilla\Utility\CamelCaseScheme;

/**
 * Test voting on an idea.
 */
class ReactionsIdeaTest extends AbstractAPIv2Test {

    /** @var int Category ID associated with an idea category. */
    private static $categoryID;

    /** @var int Category ID associated with an idea category allowing down votes. */
    private static $downVoteCategoryID;

    /**
     * Get a discussion row.
     *
     * @param int $id
     * @return array|mixed
     */
    private function getDiscussion($id) {
        $response = $this->api()->get("discussions/{$id}");
        $row = $response->getBody();
        return $row;
    }

    /**
     * Create a new idea.
     *
     * @param string $type Type of idea: up or up-down.
     * @return array
     */
    private function postIdea($type) {
        switch ($type) {
            case 'up-down':
                $categoryID = self::$downVoteCategoryID;
                break;
            default:
                $categoryID = self::$categoryID;
        }

        $fields = [
            'categoryID' => $categoryID,
            'name' => 'Test Idea',
            'body' => 'Hello world!',
            'format' => 'markdown'
        ];
        $response = $this->api()->post('discussions/idea', $fields);
        $row = $response->getBody();

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        self::$addons = ['vanilla', 'ideation'];
        parent::setupBeforeClass();

        // Bust that cache.
        ReactionModel::$ReactionTypes = null;

        /** @var CategoryModel $categoryModel */
        $categoryModel = self::container()->get('CategoryModel');
        static::$categoryID = $categoryModel->save([
            'Name' => 'Test Vote Category',
            'UrlCode' => 'test-vote-category',
            'InsertUserID' => self::$siteInfo['adminUserID'],
            'IdeationType' => 'up'
        ]);
        static::$downVoteCategoryID = $categoryModel->save([
            'Name' => 'Test Down Vote Category',
            'UrlCode' => 'test-down-vote-category',
            'InsertUserID' => self::$siteInfo['adminUserID'],
            'IdeationType' => 'up-down'
        ]);
    }

    /**
     * Verify ability to down-vote an idea.
     */
    public function testDownVote() {
        $idea = $this->postIdea('up-down');
        $id = $idea['discussionID'];

        $row = $this->getDiscussion($id);
        $this->assertEquals(0, $row['attributes']['idea']['score']);

        $this->api()->post("discussions/{$id}/reactions", ['reactionType' => 'Down']);
        $updated = $this->getDiscussion($id);
        $this->assertEquals(-1, $updated['attributes']['idea']['score']);
    }

    /**
     * Verify ability to up-vote an idea.
     */
    public function testUpVote() {
        $idea = $this->postIdea('up');
        $id = $idea['discussionID'];

        $row = $this->getDiscussion($id);
        $this->assertEquals(0, $row['attributes']['idea']['score']);

        $this->api()->post("discussions/{$id}/reactions", ['reactionType' => 'Up']);
        $updated = $this->getDiscussion($id);
        $this->assertEquals(1, $updated['attributes']['idea']['score']);
    }
}
