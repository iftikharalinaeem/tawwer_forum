<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use Garden\Schema\Schema;
use VanillaTests\APIv2\AbstractAPIv2Test;
use \Vanilla\Knowledge\Controllers\Api\KnowledgeCategoriesApiController;
use \Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController;
use \Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * Class AdvancedSearchArticlesTest
 */
class AdvancedSearchArticlesTest extends AbstractAPIv2Test {

    /** @var array */
    protected static $category;

    /** @var array */
    protected static $pollCategory;

    /** @var array */
    protected static $discussion;

    /** @var array */
    protected static $article;

    /** @var array */
    protected static $comment;

    /** @var Schema */
    protected static $searchResultSchema;

    /** @var bool */
    protected static $sphinxReindexed;

    /** @var array */
    protected static $dockerResponse;

    protected static $addons = ['vanilla', 'advancedsearch', 'sphinx', 'knowledge'];

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

        $tmp = uniqid('comment_');
        self::$comment = $commentAPIController->post([
            'body' => $tmp,
            'format' => 'markdown',
            'discussionID' => self::$discussion['discussionID'],
        ]);
        self::$comment['rawBody'] = $tmp;

        /** @var KnowledgeCategoriesApiController $kbApi */
        $kbApi = static::container()->get(KnowledgeBasesApiController::class);
        /** @var KnowledgeCategoriesApiController $kbCategoriesApi */
        $kbCategoriesApi = static::container()->get(KnowledgeCategoriesApiController::class);
        /** @var ArticlesApiController $articlesApi */
        $articlesApi = static::container()->get(ArticlesApiController::class);

        $helloWorldBody = json_encode([["insert" => "Hello World"]]);
        // Setup the test categories.
        $knowledgeBase = $kbApi->post([
            "name" => __FUNCTION__ . " KB #1",
            "Description" => 'Test knowledge base description',
            "urlCode" => slugify(
                'test-Knowledge-Base-'.round(microtime(true) * 1000).rand(1, 1000)
            ),
            "viewType" => KnowledgeBaseModel::TYPE_GUIDE,
            "sortArticles" => KnowledgeBaseModel::ORDER_MANUAL
        ]);

        $rootCategory = $kbCategoriesApi->get($knowledgeBase['rootCategoryID']);

        self::$article = $articlesApi->post([
            "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
            "name" => "Primary Category Article apple",
            "body" => $helloWorldBody,
            "format" => "rich",
        ]);


        self::SphinxReindex();

        /** @var SearchApiController $searchAPIController */
        $searchAPIController = static::container()->get(SearchApiController::class);
        self::$searchResultSchema = $searchAPIController->fullSchema();

        $session->end();
    }

    /**
     * Method to call aphinx reindex command and check if response is successful
     */
    public static function sphinxReindex() {
        $sphinxHost = c('Plugins.Sphinx.Server');
        exec('curl '.$sphinxHost.':9399', $dockerResponse);
        self::$dockerResponse = $dockerResponse;
        self::$sphinxReindexed = ('Sphinx reindexed.' === end(self::$dockerResponse));
        sleep(1);
    }

    /**
     * Test search scoped to discussions.
     */
    public function testRecordTypesDiscussion() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!'."\n".end(self::$dockerResponse));
        }

        $params = [
            'query' => self::$discussion['name'],
            'recordTypes' => 'discussion',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) === 1);
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'discussion'], $results[0]);
    }

    /**
     * Test search scoped to comments.
     */
    public function testRecordTypesComment() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

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
        $this->assertRowsEqual(['recordType' => 'comment'], $results[0]);
    }

    /**
     * Test search scoped to comments.
     */
    public function testRecordTypesArticle() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

        $params = [
            'query' => self::$article['name'],
            'recordTypes' => 'article'
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'article'], $results[0]);
        $this->assertRowsEqual(['type' => 'article'], $results[0]);
    }

    /**
     * Test search scoped to a discussion.
     */
    public function testExistingDiscussionID() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

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
     * Test search scoped to a discussion.
     */
    public function testExistingArticleDiscussionID() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

        $params = [
            'discussionID' => self::$article['articleID'],
            'recordTypes' => 'article'
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'article'], $results[0]);
    }

    /**
     * Test search scoped to a category.
     */
    public function testExistingCategoryID() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

        $params = [
            'query' => self::$discussion['name'],
            'categoryID' => self::$category['categoryID'],
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));

        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        // Correct value is 2.
        // Partially fixed https://github.com/vanilla/internal/issues/1963
        $this->assertEquals(2, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
    }


    /**
     * Test search article by user names.
     */
    public function testInsertUserNames() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

        $params = [
            'query' => self::$discussion['name'],
            'insertUserNames' => 'travis,daffewfega',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        // Correct value is 2.
        // Partially fixed https://github.com/vanilla/internal/issues/1963
        $this->assertEquals(2, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
    }

    /**
     * Test search article by wrong user IDs
     */
    public function testArticleWrongInsertUserIDs() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

        $params = [
            'query' => self::$article['name'],
            'insertUserIDs' => '100',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(0, count($results));
    }
}
