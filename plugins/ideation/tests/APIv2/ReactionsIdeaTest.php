<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use CategoryModel;
use Garden\Http\HttpResponse;
use IdeationPlugin;
use ReactionModel;

/**
 * Test voting on an idea.
 */
class ReactionsIdeaTest extends AbstractAPIv2Test {

    /** @var int Category ID associated with an idea category. */
    private static $categoryID;

    /** @var int Category ID associated with an idea category allowing down votes. */
    private static $downVoteCategoryID;

    /**
     * Verify a response is valid for a vote on an idea.
     *
     * @param HttpResponse $response
     * @param $type
     */
    private function assertIsVoteResponse(HttpResponse $response, $type) {
        $this->assertEquals(201, $response->getStatusCode());

        $body = $response->getBody();
        $reactions = array_column($body, null, 'urlcode');
        $this->assertArrayHasKey(IdeationPlugin::REACTION_UP, $reactions);
        if ($type === 'up-down') {
            $this->assertArrayHasKey(IdeationPlugin::REACTION_DOWN, $reactions);
            $this->assertCount(2, $reactions); // Only up-vote and down-vote reactions.
        } else {
            $this->assertCount(1, $reactions); // Only up-vote reactions.
        }
    }

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
    public static function setupBeforeClass(): void {
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
     *
     * @param string $type Ideation category type: up or up-down
     * @param string $vote Vote reaction: up or down
     * @param int $score Expected score for the new idea, after the vote.
     * @dataProvider provideVotes
     */
    public function testVote($type, $vote, $score) {
        // Start with a clean slate.
        $idea = $this->postIdea($type);
        $this->assertEquals(0, $idea['score']);

        $id = $idea['discussionID'];
        $response = $this->api()->post("discussions/{$id}/reactions", ['reactionType' => $vote]);
        $this->assertIsVoteResponse($response, $type);
        $updated = $this->getDiscussion($id);
        $this->assertEquals($score, $updated['score']);

        $reactions = $this->api()->get("discussions/{$id}/reactions", ['reactionType' => $vote])->getBody();
        $found = false;
        foreach ($reactions as $reaction) {
            if ($reaction['userID'] === $this->api()->getUserID()) {
                $found = true;
                $this->assertEqualsIgnoringCase($vote, $reaction['reactionType']['urlcode']);
            }
        }
        $this->assertTrue($found);
    }

    /**
     * Provide parameters for testing voting.
     */
    public function provideVotes() {
        $result = [
            ['up-down', 'down', -1],
            ['up-down', 'up', 1],
            ['up-down', 'up', 1]
        ];
        return $result;
    }
}
