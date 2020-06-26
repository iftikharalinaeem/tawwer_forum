<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use Garden\Schema\Schema;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Models\ModelTestTrait;

class SphinxSearchTest extends AbstractAPIv2Test {
    use \Vanilla\Sphinx\Tests\Utils\SphinxTestTrait;
    use ModelTestTrait;

    /** @var array */
    protected static $category;

    /** @var array */
    protected static $discussion;

    /** @var array */
    protected static $discussionAugust;

    /** @var array */
    protected static $comment;

    /** @var Schema */
    protected static $searchResultSchema;

    /** @var bool */
    protected static $sphinxReindexed;

    /** @var array */
    protected static $dockerResponse;

    protected static $addons = ['vanilla', 'sphinx', 'advancedsearch'];

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
            'name' => $tmp,
            'body' => $tmp,
            'format' => 'markdown',
            'categoryID' => self::$category['categoryID'],
        ]);
        self::$discussion['rawBody'] = $tmp;

        self::$discussionAugust = $discussionsAPIController->post([
            'name' => 'August test 2019',
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

        self::SphinxReindex();

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
        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        $this->assertRowsEqual(['recordType' => 'discussion'], $results[0]);
    }

    /**
     * Test search scoped to comments.
     */
    public function testRecordTypesComment() {
        $params = [
            'query' => self::$comment['rawBody'],
            'recordTypes' => 'comment',
        ];
        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        $this->assertRowsEqual(['recordType' => 'comment'], $results[0]);
    }

    /**
     * Test search scoped to a discussion.
     */
    public function testExistingDiscussionID() {
        $params = [
            'query' => self::$discussion['name'],
            'discussionID' => self::$discussion['discussionID'],
        ];
        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        // Should return 2 both: discussion and comment for it
        $this->assertEquals(2, count($results));
    }

    /**
     * Test search scoped to a non existing discussion.
     */
    public function testNonExistingDiscussionID() {
        $params = [
            'query' => self::$comment['rawBody'],
            'discussionID' => 999999,
        ];
        $response = $this->api()->get('/search?' . http_build_query($params));
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
        $response = $this->api()->get('/search?' . http_build_query($params));

        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        // Correct value is 2.
        // Partially fixed https://github.com/vanilla/internal/issues/1963
        $this->assertEquals(2, count($results));
    }

    /**
     * Test search scoped to a non existing category.
     */
    public function testNonExistingCategoryID() {
        $params = [
            'query' => self::$discussion['name'],
            'categoryID' => 777,
        ];
        $api = $this->api();
        $response = $api->get('/search?' . http_build_query($params));

        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(0, count($results));
    }


    /**
     * Test search by user names.
     */
    public function testInsertUserNames() {
        $params = [
            'query' => self::$discussion['name'],
            'insertUserNames' => 'travis,daffewfega',
        ];
        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        // Correct value is 2.
        // Partially fixed https://github.com/vanilla/internal/issues/1963
        $this->assertEquals(2, count($results));
    }

    /**
     * Test search by user IDs
     */
    public function testInsertUserIDs() {
        $params = [
            'query' => self::$discussion['name'],
            'insertUserIDs' => '1,2',
        ];
        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        // Correct value is 2.
        // Partially fixed https://github.com/vanilla/internal/issues/1963
        $this->assertEquals(2, count($results));
    }

    /**
     * Test searching in the title field.
     */
    public function testSearchTitle() {
        /** @var DiscussionModel $discussionModel */
        $discussionModel = self::container()->get(DiscussionModel::class);
        $discussionTitle = $discussionModel->save([
            'Name' => 'Title Query',
            'Body' => 'Body',
            'Format' => 'markdown',
            'CategoryID' => self::$category['categoryID'],
        ]);

        $discussionWrongTitle = $discussionModel->save([
            'Name' => 'Wrong',
            'Body' => 'Title Query in body',
            'Format' => 'markdown',
            'CategoryID' => self::$category['categoryID'],
        ]);

        $this->sphinxReindex();

        $this->assertSearchResultIDs(
            [
                'name' => 'title query',
            ],
            [$discussionTitle]
        );
    }

    /**
     * Test date ranges for the search query.
     */
    public function testDates() {
        /** @var DiscussionModel $discussionModel */
        $discussionModel = self::container()->get(DiscussionModel::class);
        $discussion7_1 = $discussionModel->save([
            'Name' => 'date test 7 1',
            'Body' => '7 1',
            'Format' => 'markdown',
            'CategoryID' => self::$category['categoryID'],
            'DateInserted' => "2019-07-01 12:00:00"
        ]);

        $discussion7_2 = $discussionModel->save([
            'Name' => 'date test 7 2',
            'Body' => '7 2',
            'Format' => 'markdown',
            'CategoryID' => self::$category['categoryID'],
            'DateInserted' => "2019-07-02 12:00:00"
        ]);

        $discussion7_3 = $discussionModel->save([
            'Name' => 'date test 7 3',
            'Body' => '7 3',
            'Format' => 'markdown',
            'CategoryID' => self::$category['categoryID'],
            'DateInserted' => "2019-07-03 12:00:00"
        ]);

        $this->sphinxReindex();

        $this->assertSearchResultIDs(
            [
                'query' => 'date test',
            ],
            [$discussion7_1, $discussion7_2, $discussion7_3]
        );

        $this->assertSearchResultIDs(
            [
                'query' => 'date test',
                'dateInserted' => '2019-07-03'
            ],
            [$discussion7_3]
        );

        $this->assertSearchResultIDs(
            [
                'query' => 'date test',
                'dateInserted' => '[2019-07-01, 2019-07-02]'
            ],
            [$discussion7_1, $discussion7_2]
        );

        $this->assertSearchResultIDs(
            [
                'query' => 'date test',
                'dateInserted' => '[2019-07-01, 2019-07-02)'
            ],
            [$discussion7_1]
        );
    }

    /**
     * Assert a search results in some particular result IDs.
     *
     * @param array $query
     * @param array $expectedIDs
     */
    protected function assertSearchResultIDs(array $query, array $expectedIDs) {
        $response = $this->api()->get('/search', $query);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $actualIDs = array_column($results, 'recordID');
        $this->assertIDsEqual($expectedIDs, $actualIDs);
    }
}
