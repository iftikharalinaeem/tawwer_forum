<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use Garden\Schema\Schema;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Test sphinx searching on discussions.
 */
class SphinxSearchDiscussionExtensionTest extends AbstractAPIv2Test {
    use \Vanilla\Sphinx\Tests\Utils\SphinxTestTrait;

    /** @var array */
    protected static $category;

    /** @var array */
    protected static $followedCategory;

    /** @var array */
    protected static $discussion;

    /** @var array */
    protected static $discussionAugust;

    /** @var array */
    protected static $discussionFollowed;

    /** @var array */
    protected static $comment;

    /** @var Schema */
    protected static $discussionsApiSchema;

    protected static $addons = ['vanilla', 'sphinx', 'advancedsearch'];

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        saveToConfig('Plugins.Sphinx.UseDeltas', true);
        saveToConfig('Vanilla.EnableCategoryFollowing', '1');

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \CategoriesApiController $categoriesAPIController */
        $categoriesAPIController = static::container()->get('CategoriesApiController');

        /** @var CategoryModel $categoryModel */
        $categoryModel = static::container()->get(CategoryModel::class);

        /** @var \DiscussionsApiController $discussionsAPIController */
        $discussionsAPIController = static::container()->get('DiscussionsApiController');
        /** @var \CommentsApiController $commentAPIController */
        $commentAPIController = static::container()->get('CommentsApiController');

        $tmp = uniqid('category_');
        self::$category = $categoriesAPIController->post([
            'name' => $tmp,
            'urlcode' => $tmp,
        ]);


        $tmp = uniqid('category_');
        self::$followedCategory = $categoriesAPIController->post([
            'name' => 'Followed ' . $tmp,
            'urlcode' => $tmp,
        ]);

        $categoryModel->follow(self::$siteInfo['adminUserID'], self::$followedCategory['categoryID']);

        $tmp = uniqid('discussion_');
        self::$discussion = $discussionsAPIController->post([
            'name' => 'discussion controller test ' . $tmp,
            'body' => $tmp,
            'format' => 'markdown',
            'categoryID' => self::$category['categoryID'],
        ]);
        self::$discussion['rawBody'] = $tmp;

        self::$discussionAugust = $discussionsAPIController->post([
            'name' => 'discussion controller test ' . 'August test 2019',
            'body' => 'August test 2019',
            'format' => 'markdown',
            'categoryID' => self::$category['categoryID'],
        ]);

        self::$discussionFollowed = $discussionsAPIController->post([
            'name' => 'discussion controller test ' . 'followed',
            'body' => 'followed discussion',
            'format' => 'markdown',
            'categoryID' => self::$followedCategory['categoryID'],
        ]);

        $tmp = uniqid('comment_');
        self::$comment = $commentAPIController->post([
            'body' => $tmp,
            'format' => 'markdown',
            'discussionID' => self::$discussion['discussionID'],
        ]);
        self::$comment['rawBody'] = $tmp;

        self::SphinxReindex();

        /** @var DiscussionsApiController $discussionsApiController */
        $discussionsApiController = static::container()->get(DiscussionsApiController::class);
        self::$discussionsApiSchema = $discussionsApiController->discussionSchema();

        $session->end();
    }

    /**
     * Test discussions/search api.
     */
    public function testAllDiscussions() {
        $params = [
            'query' => 'discussion'
        ];
        $response = $this->api()->get('discussions/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(3, count($results));

        foreach ($results as $result) {
            self::$discussionsApiSchema->validate($result);
        }
    }

    /**
     * Test discussions/search api with limit.
     */
    public function testDiscussionsLimit() {
        $params = [
            'query' => 'discussion',
            'limit' => 1
        ];
        $response = $this->api()->get('discussions/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));

        foreach ($results as $result) {
            self::$discussionsApiSchema->validate($result);
        }
        $first = $result;

        $params = [
            'query' => 'discussion',
            'limit' => 1,
            'page' => 2
        ];
        $response = $this->api()->get('discussions/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));

        foreach ($results as $result) {
            self::$discussionsApiSchema->validate($result);
        }

        $this->assertNotEquals($first['discussionID'], $result['discussionID']);
    }

    /**
     * Test discussions/search api scoped to particular categoryID.
     */
    public function testDiscussionsByCategoryID() {
        $params = [
            'query' => 'discussion',
            'categoryID' => self::$category['categoryID']
        ];
        $response = $this->api()->get('discussions/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(2, count($results));

        foreach ($results as $result) {
            self::$discussionsApiSchema->validate($result);
        }
    }

    /**
     * Test discussions/search api scoped to not existing categoryID.
     */
    public function testDiscussionsByWrongCategoryID() {
        $params = [
            'query' => 'discussion',
            'categoryID' => 404
        ];
        $response = $this->api()->get('discussions/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(0, count($results));
    }

    /**
     * Test discussions/search apiscoped to followed categories.
     */
    public function testDiscussionsByFollowedCategories() {
        $params = [
            'query' => 'discussion',
            'followed' => 1
        ];
        $response = $this->api()->get('discussions/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
    }
}
