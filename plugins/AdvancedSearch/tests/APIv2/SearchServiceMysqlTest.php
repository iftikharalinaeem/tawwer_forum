<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use Garden\Schema\Schema;
use Vanilla\Formatting\Formats\MarkdownFormat;
use Vanilla\Forum\Navigation\ForumBreadcrumbProvider;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbModel;
use VanillaTests\APIv2\AbstractAPIv2Test;
use Vanilla\Search\SearchService;
use Vanilla\Search\AbstractSearchDriver;
use Vanilla\Search\GlobalSearchType;
use Vanilla\Search\MysqlSearchDriver;
use SearchApiController;
use Garden\Container\Reference;
use Vanilla\Forum\Search\DiscussionSearchType;
use Vanilla\Forum\Search\CommentSearchType;

/**
 * Class SearchServiceMysqlTest
 */
class SearchServiceMysqlTest extends AbstractAPIv2Test {

    /** @var array */
    protected static $category;

    /** @var array */
    protected static $discussion;

    /** @var array */
    protected static $comment;

    /** @var Schema */
    protected static $searchResultSchema;

    protected static $addons = ['vanilla', 'advancedsearch'];

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {

        parent::setupBeforeClass();
        \Gdn::config()->saveToConfig('Feature.useSearchService.Enabled', true);

        self::container()->rule(SearchService::class)
            ->addCall('registerActiveDriver', [new Reference(MysqlSearchDriver::class)]);

        self::container()->rule(MysqlSearchDriver::class)
            ->addCall('registerSearchType', [new Reference(GlobalSearchType::class)])
            ->addCall('registerSearchType', [new Reference(DiscussionSearchType::class)])
            ->addCall('registerSearchType', [new Reference(CommentSearchType::class)]);

        self::container()->rule(BreadcrumbModel::class)
            ->addCall('addProvider', [new \Garden\Container\Reference(ForumBreadcrumbProvider::class)]);

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
            'name' => $tmp,
            'body' => $tmp,
            'format' => \Vanilla\Formatting\Formats\WysiwygFormat::FORMAT_KEY,
            'categoryID' => self::$category['categoryID'],
        ]);
        self::$discussion['rawBody'] = $tmp;

        $tmp = uniqid('comment_');
        self::$comment = $commentAPIController->post([
            'body' => $tmp,
            'format' => 'markdown',
            'discussionID' => self::$discussion['discussionID'],
        ]);
        self::$comment['rawBody'] = $tmp;

        /** @var SearchApiController $searchAPIController */
        $searchAPIController = static::container()->get(SearchApiController::class);
        self::$searchResultSchema = $searchAPIController->fullSchema();

        $session->end();
    }

    /**
     * Test that the expand body parameter works.
     */
    public function testExpandAll() {
        // Explicitly passing expandBody=true works.
        $params = [
            'query' => self::$discussion['name'],
            'expand' => ['all'],
        ];
        $item = $this->api()->get('/search?'.http_build_query($params))->getBody()[0];
        $this->assertNotNull($item['body']);
        $this->assertNotNull($item['insertUser']);
        $this->assertIsArray($item['breadcrumbs']);
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

        $this->assertTrue(count($results) === 1);
        $this->assertEquals('discussion', $results[0]['recordType']);
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

        $this->assertTrue(count($results) === 1);
        $this->assertEquals('comment', $results[0]['recordType']);
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

        $this->assertTrue(count($results) === 1);
        $this->assertEquals('comment', $results[0]['recordType']);
    }

    /**
     * Test search scoped to a non existing discussion.
     */
    public function testNonExistingDiscussionID() {
        $params = [
            'query' => self::$comment['rawBody'],
            'discussionID' => 999999,
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) === 0);
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

        $this->assertTrue(count($results) === 1);
    }

    /**
     * Test search scoped to a non existing category.
     */
    public function testNonExistingCategoryID() {
        $params = [
            'query' => self::$discussion['name'],
            'categoryID' => 999999,
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) === 0);
    }

    /**
     * Test search by name.
     */
    public function testName() {
        $params = [
            'name' => self::$discussion['name'],
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) === 2);
    }

    /**
     * Test searching with the insert user expanded.
     */
    public function testExpandInsertUser() {
        $params = [
            'name' => self::$discussion['name'],
            'expand' => ['insertUser'],
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) > 0);

        $sch = new \Vanilla\Models\UserFragmentSchema();
        foreach ($results as $result) {
            $sch->validate($result['insertUser']);
        }
    }

    /**
     * Test search by user names.
     */
    public function testInsertUserNames() {
        $params = [
            'query' => self::$discussion['name'],
            'insertUserNames' => 'circleci',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) === 1);
    }

    /**
     * Test search by user names.
     */
    public function testNonExistingUserNames() {
        $params = [
            'query' => self::$discussion['name'],
            'insertUserNames' => 'ewfega',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) === 0);
    }

    /**
     * Test search by user IDs
     */
    public function testInsertUserIDs() {
        $params = [
            'query' => self::$discussion['name'],
            'insertUserIDs' => '1,2',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) === 1);
    }

    /**
     * Test search using a specific insert date.
     */
    public function testDateInsertedEqual() {
        /** @var DateTimeImmutable $dateTime */
        $dateTime = self::$discussion['dateInserted'];
        $params = [
            'dateInserted' => $dateTime->format(MYSQL_DATE_FORMAT),
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) >= 1);
    }
}
