<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests;

use ReactionModel;
use VanillaTests\SiteTestTrait;

/**
 * Test {@link ReactionsPlugin} API capabilities.
 */
class ReactionsReactTest extends \PHPUnit_Framework_TestCase {

    use SiteTestTrait {
        setupBeforeClass as siteSetupBeforeClass;
    }

    /** @var InternalClient */
    private $api;

    /**
     * Setup routine, run before each test case.
     */
    public function setUp() {
        ReactionModel::$ReactionTypes = null;
        $this->api = static::container()->getArgs(InternalClient::class, [static::container()->get('@baseUrl').'/api/v2']);
        $this->api->setUserID(self::$siteInfo['adminUserID']);
        $this->api->setTransientKey(md5(now()));
        parent::setUp();
    }

    /**
     * Setup routine, run before the test class is instantiated.
     */
    public static function setupBeforeClass() {
        self::$addons = ['reactions', 'stubcontent', 'vanilla'];
        self::siteSetupBeforeClass();
        parent::setUpBeforeClass();
    }

    /**
     * Test changing a user reaction from one type to another.
     */
    public function testChangeReaction() {
        $this->api()->post('/discussions/1/reactions', [
            'reactionType' => 'Like'
        ]);
        $reactions = $this->api()->get('/discussions/1/reactions');
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), 'Like', $reactions->getBody()));
        $this->assertFalse($this->hasUserReaction($this->api()->getUserID(), 'LOL', $reactions->getBody()));

        $this->api()->post('/discussions/1/reactions', [
            'reactionType' => 'LOL'
        ]);
        $reactions = $this->api()->get('/discussions/1/reactions');
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), 'LOL', $reactions->getBody()));
        $this->assertFalse($this->hasUserReaction($this->api()->getUserID(), 'Like', $reactions->getBody()));
    }

    /**
     * Test a user adding the same reaction to the same post, twice.
     */
    public function testDuplicateReaction() {
        $this->api()->post('/discussions/1/reactions', [
            'reactionType' => 'Like'
        ]);
        $summary = $this->api()->post('/discussions/1/reactions', [
            'reactionType' => 'Like'
        ]);

        $this->assertEquals(1, $this->getSummaryCount('Like', $summary->getBody()));

        $reactions = $this->api()->get('/discussions/1/reactions')->getBody();
        $currentUserReactions = 0;
        foreach ($reactions as $row) {
            if ($row['user']['userID'] == $this->api()->getUserID()) {
                $currentUserReactions++;
            }
        }
        $this->assertEquals(1, $currentUserReactions);
    }

    /**
     * Test reacting to a comment.
     */
    public function testPostCommentReaction() {
        $type = 'Like';
        $response = $this->api()->post('/comments/1/reactions', [
            'reactionType' => $type
        ]);
        $this->assertEquals(201, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertInternalType('array', $body);
        $this->assertSummaryHasReactionType($type, $body);
    }

    /**
     * Test getting reactions to a comment.
     *
     * @depends testPostCommentReaction
     */
    public function testGetCommentReactions() {
        $type = 'Like';
        $this->api()->post('/comments/1/reactions', [
            'reactionType' => $type
        ]);

        $response = $this->api()->get('/comments/1/reactions');
        $body = $response->getBody();
        $this->assertInternalType('array', $body);
        $this->assertNotEmpty($body);
        $this->asserttrue($this->hasUserReaction($this->api()->getUserID(), $type, $body));
    }

    /**
     * Test undoing a reaction to a comment.
     *
     * @depends testGetCommentReactions
     */
    public function testDeleteCommentReaction() {
        $type = 'Like';
        $userID = $this->api()->getUserID();
        $this->api()->post('/comments/1/reactions', [
            'reactionType' => $type
        ]);
        $postResponse = $this->api()->get('/comments/1/reactions');
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), $type, $postResponse->getBody()));

        $this->api()->delete("/comments/1/reactions/{$userID}");
        $response = $this->api()->get('/comments/1/reactions');
        $this->assertFalse($this->hasUserReaction($this->api()->getUserID(), $type, $response->getBody()));
    }

    /**
     * Test ability to expand reactions on a comment.
     */
    public function testExpandComment() {
        $getResponse = $this->api()->get('/comments/1', ['expand' => 'reactions']);
        $getBody = $getResponse->getBody();
        $this->assertTrue($this->isReactionSummary($getBody['reactions']));

        $indexResponse = $this->api()->get('/comments', [
            'discussionID' => 1,
            'expand' => 'reactions'
        ]);
        $indexBody = $indexResponse->getBody();
        $indexHasReactions = true;
        foreach ($indexBody as $row) {
            $indexHasReactions = $indexHasReactions && $this->isReactionSummary($row['reactions']);
            if ($indexHasReactions === false) {
                break;
            }
        }
        $this->assertTrue($indexHasReactions);
    }

    /**
     * Test reacting to a discussion.
     */
    public function testPostDiscussionReaction() {
        $type = 'Like';
        $response = $this->api()->post('/discussions/1/reactions', [
            'reactionType' => $type
        ]);
        $this->assertEquals(201, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertInternalType('array', $body);
        $this->assertSummaryHasReactionType($type, $body);
    }

    /**
     * Test getting reactions to a discussion.
     *
     * @depends testPostDiscussionReaction
     */
    public function testGetDiscussionReactions() {
        $type = 'Like';
        $this->api()->post('/discussions/1/reactions', [
            'reactionType' => $type
        ]);

        $response = $this->api()->get('/discussions/1/reactions');
        $body = $response->getBody();
        $this->assertInternalType('array', $body);
        $this->assertNotEmpty($body);
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), $type, $body));
    }

    /**
     * Test undoing a reaction to a discussion.
     *
     * @depends testGetCommentReactions
     */
    public function testDeleteDiscussionReaction() {
        $type = 'Like';
        $userID = $this->api()->getUserID();
        $this->api()->post('/discussions/1/reactions', [
            'reactionType' => $type
        ]);
        $postResponse = $this->api()->get('/discussions/1/reactions');
        $this->assertTrue($this->hasUserReaction($this->api()->getUserID(), $type, $postResponse->getBody()));

        $this->api()->delete("/discussions/1/reactions/{$userID}");
        $response = $this->api()->get('/discussions/1/reactions');
        $this->assertFalse($this->hasUserReaction($this->api()->getUserID(), $type, $response->getBody()));
    }

    /**
     * Test ability to expand reactions on a discussion.
     */
    public function testExpandDiscussion() {
        $getResponse = $this->api()->get('/discussions/1', ['expand' => 'reactions']);
        $getBody = $getResponse->getBody();
        $this->assertTrue($this->isReactionSummary($getBody['reactions']));

        $indexResponse = $this->api()->get('/discussions', ['expand' => 'reactions']);
        $indexBody = $indexResponse->getBody();
        $indexHasReactions = true;
        foreach ($indexBody as $row) {
            $indexHasReactions = $indexHasReactions && $this->isReactionSummary($row['reactions']);
            if ($indexHasReactions === false) {
                break;
            }
        }
        $this->assertTrue($indexHasReactions);
    }

    /**
     * Get the internal client for API tests.
     *
     * @return InternalClient
     */
    public function api() {
        return $this->api;
    }

    /**
     * Get the count for a type from a summary array.
     *
     * @param string $type The URL code of a type.
     * @param array $summary A summary of reactions on a record.
     * @return int
     */
    public function getSummaryCount($type, array $summary) {
        $result = 0;

        foreach ($summary as $row) {
            if ($row['urlCode'] === $type) {
                $result = $row['count'];
                break;
            }
        }

        return $result;
    }

    /**
     * Given a user ID and a reaction type, verify the combination is in a log of reactions.
     *
     * @param int $userID
     * @param string $type
     * @param array $data
     * @return bool
     */
    public function hasUserReaction($userID, $type, array $data) {
        $result = false;

        foreach ($data as $row) {
            if (!array_key_exists('userID', $row) || $row['userID'] !== $userID) {
                continue;
            } elseif (!array_key_exists('reactionType', $row) || !is_array($row['reactionType'])) {
                continue;
            } elseif (!array_key_exists('urlCode', $row['reactionType']) || $row['reactionType']['urlCode'] !== $type) {
                continue;
            } else {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Is the data collection a valid reaction summary?
     *
     * @param array $data
     * @return bool
     */
    public function isReactionSummary(array $data) {
        $result = true;

        foreach ($data as $row) {
            if (!array_key_exists('tagID', $row) || !is_int($row['tagID']) ||
                !array_key_exists('urlCode', $row) || !is_string($row['urlCode']) ||
                !array_key_exists('name', $row) || !is_string($row['name']) ||
                !array_key_exists('class', $row) || !is_string($row['class']) ||
                !array_key_exists('count', $row) || !is_int($row['count'])) {

                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * Assert a reaction summary contains a greater-than-zero number of a particular reaction type.
     *
     * @param string $type A valid URL code for a reaction type.
     * @param array $data Data collection (e.g. a response body).
     */
    public function assertSummaryHasReactionType($type, array $data) {
        $result = false;

        foreach ($data as $row) {
            if (!array_key_exists('urlCode', $row) || !array_key_exists('count', $row)) {
                continue;
            } elseif ($row['urlCode'] !== $type) {
                continue;
            }

            if ($row['count'] > 0) {
                $result = true;
            }
            break;
        }

        $this->assertTrue($result, "Unable to find a greater-than-zero count for reaction type: {$type}");
    }
}
