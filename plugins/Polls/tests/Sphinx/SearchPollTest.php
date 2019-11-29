<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use Garden\Schema\Schema;
use Vanilla\Sphinx\Tests\Utils\SphinxTestTrait;
use VanillaTests\APIv2\AbstractAPIv2Test;

class SearchPollTest extends AbstractAPIv2Test {
    use SphinxTestTrait;

    /** @var array */
    protected static $category;

    /** @var array */
    protected static $pollCategory;

    /** @var array */
    protected static $discussion;

    /** @var array */
    protected static $pollDiscussion;

    /** @var array */
    protected static $poll;

    /** @var array */
    protected static $discussionAugust;

    /** @var array */
    protected static $comment;

    /** @var Schema */
    protected static $searchResultSchema;

    protected static $addons = ['vanilla', 'sphinx', 'polls', 'advancedsearch'];

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();

        saveToConfig('Plugins.Sphinx.UseDeltas', true);
        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \CategoriesApiController $categoriesAPIController */
        $categoriesAPIController = static::container()->get('CategoriesApiController');
        /** @var \DiscussionsApiController $discussionsAPIController */
        $discussionsAPIController = static::container()->get('DiscussionsApiController');
        /** @var \CommentsApiController $commentAPIController */
        $commentAPIController = static::container()->get('CommentsApiController');

        $tmp = uniqid('category_');
        self::$category = $categoriesAPIController->post([
            'name' => $tmp,
            'urlcode' => $tmp,
        ]);

        $tmp = uniqid('discussion_');
        self::$discussion = $discussionsAPIController->post([
            'name' => 'discussion controller test '.$tmp,
            'body' => $tmp,
            'format' => 'markdown',
            'categoryID' => self::$category['categoryID'],
        ]);
        self::$discussion['rawBody'] = $tmp;

        self::$discussionAugust = $discussionsAPIController->post([
            'name' => 'discussion controller test '.'August test 2019',
            'body' => 'August test 2019',
            'format' => 'markdown',
            'categoryID' => self::$category['categoryID'],
        ]);

        $tmp = uniqid('comment_');
        self::$comment = $commentAPIController->post([
            'body' => $tmp,
            'format' => 'markdown',
            'discussionID' => self::$discussion['discussionID'],
        ]);
        self::$comment['rawBody'] = $tmp;

        self::$pollCategory = $categoriesAPIController->post([
            'name' => 'PollsTest',
            'urlcode' => 'pollstest',
        ]);

        $tmp = uniqid('poll_discussion_');
        self::$pollDiscussion = $discussionsAPIController->post([
            'name' => 'discussion controller test '.$tmp,
            'type' => 'Poll',
            'body' => 'PollsTest body orange',
            'format' => 'markdown',
            'categoryID' => self::$pollCategory['categoryID'],
        ]);
        self::$pollDiscussion['rawBody'] = $tmp;

        $pollsAPIController = static::container()->get('PollsApiController');

        $pollTxt = uniqid(__CLASS__." poll ");


        self::$poll = $pollsAPIController->post([
            'name' => $pollTxt,
            'discussionID' => self::$pollDiscussion['discussionID'],
        ]);

        self::SphinxReindex();

        /** @var SearchApiController $searchAPIController */
        $searchAPIController = static::container()->get('SearchApiController');
        self::$searchResultSchema = $searchAPIController->fullSchema();

        $session->end();
    }

    /**
     * Test search scoped to discussions.
     */
    public function testRecordTypesDiscussion() {
        $params = [
            'query' => self::$discussion['name'],
            'recordTypes' => 'discussion',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(3, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'discussion'], $result);
    }

    /**
     * Test search scoped to discussions.
     */
    public function testTypesDiscussion() {
        $params = [
            'query' => self::$discussion['name'],
            'recordTypes' => 'discussion',
            'types' => 'discussion',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(2, count($results));

        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'discussion'], $result);
    }


    /**
     * Test search scoped to comments.
     */
    public function testRecordTypesComment() {
        $params = [
            'query' => self::$comment['rawBody'],
            'recordTypes' => 'comment',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'comment'], $result);
    }

    /**
     * Test search scoped to comments.
     */
    public function testRecordTypesPoll() {
        $params = [
            'query' => 'discussion',
            'recordTypes' => 'discussion',
            'types' => 'poll'
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'discussion'], $results[0]);
        $this->assertRowsEqual(['type' => 'poll'], $results[0]);
    }

    /**
     * Test search scoped to a discussion.
     */
    public function testExistingDiscussionID() {
        $params = [
            'query' => self::$comment['rawBody'],
            'discussionID' => self::$discussion['discussionID'],
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'comment'], $results[0]);
    }

    /**
     * Test search scoped to a category.
     */
    public function testExistingCategoryID() {
        $params = [
            'query' => self::$discussion['name'],
            'categoryID' => self::$category['categoryID'],
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));

        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(3, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
    }

    /**
     * Test search scoped to a category.
     */
    public function testPollCategoryID() {
        $params = [
            'query' => 'discussion',
            'categoryID' => self::$pollCategory['categoryID'],
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));

        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
    }

    /**
     * Test search by user IDs
     */
    public function testInsertUserIDs() {
        $params = [
            'query' => 'discussion',
            'insertUserIDs' => '1,2',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(4, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
    }
}
