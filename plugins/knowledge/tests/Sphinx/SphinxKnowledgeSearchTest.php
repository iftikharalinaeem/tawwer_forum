<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Schema\Schema;
use VanillaTests\APIv2\AbstractAPIv2Test;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Garden\Web\Exception\ServerException;

/**
 * Class SphinxKnowledgeSearchTest
 */
class SphinxKnowledgeSearchTest extends AbstractAPIv2Test {

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-categories";

    /** @var string The resource route. */
    protected $kbArticlesUrl = "/articles";

    /** @var array $testData Data prepared for tests */
    protected static $testData;

    /** @var array $dockerResponse Stdout log saved when try to reindex Sphinx indexes */
    protected static $dockerResponse;

    /** @var Schema */
    protected static $searchResultSchema;

   /** @var bool */
    protected static $sphinxReindexed;

    protected static $addons = ['vanilla', 'sphinx', 'knowledge'];

    /**
     * Call sphinx server port to trigger reindexing
     */
    public static function sphinxReindex() {
         $sphinxHost = c('Plugins.Sphinx.Server');
         exec('curl '.$sphinxHost.':9399', $dockerResponse);
         self::$dockerResponse = $dockerResponse;
         self::$sphinxReindexed = ('Sphinx reindexed.' === end($dockerResponse));
         sleep(1);
    }

   /**
    * Prepare knowledge base data for tests and reindex Sphinx indexes.
    */
    public function testData() {
        $this->prepareData();
        self::sphinxReindex();
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!'."\n".end(self::$dockerResponse));
        }
        $this->assertTrue(true);
    }

    /**
     * @depends testData
     */
    public function testSearchByName() {
        $params = [
            'name' => 'apple'
        ];
        $response = $this->api()->get('/knowledge/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
    }

    /**
     * @depends testData
     */
    public function testSearchByBody() {
        $params = [
            'body' => 'apple'
        ];
        $response = $this->api()->get('/knowledge/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
    }

    /**
     * @depends testData
     */
    public function testSearchByAll() {
        $params = [
            'all' => 'apple'
        ];
        $response = $this->api()->get('/knowledge/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(2, count($results));
    }


    /**
     * @depends testData
     */
    public function testSearchByRootCategory() {
        $params = [
            'knowledgeCategoryIDs' => [self::$testData['rootCategory']['knowledgeCategoryID']]
        ];
        $response = $this->api()->get('/knowledge/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
    }

    /**
     * @depends testData
     */
    public function testSearchByChildCategory() {
        $params = [
            'knowledgeCategoryIDs' => [self::$testData['childCategory']['knowledgeCategoryID']]
        ];
        $response = $this->api()->get('/knowledge/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
    }

    /**
     * @depends testData
     */
    public function testFeatured() {
        $params = [
            'featured' => true
        ];
        $response = $this->api()->get('/knowledge/search', $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        $this->assertEquals(self::$testData['featuredArticle']['articleID'], $results[0]['recordID']);
    }

    /**
     * @depends testData
     */
    public function testSearchByAllCategoryIDs() {
        $params = [
            'knowledgeCategoryIDs' => [
                self::$testData['rootCategory']['knowledgeCategoryID'],
                self::$testData['childCategory']['knowledgeCategoryID'],
                self::$testData['child2Category']['knowledgeCategoryID'],
                self::$testData['childCategory2']['knowledgeCategoryID']
            ]
        ];
        $response = $this->api()->get('/knowledge/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(3, count($results));
    }

    /**
     * The get the GET /articles/related-articles endpoint.
     *
     * @depends testData
     */
    public function testGetRelatedArticles() {
        $params = [
            'knowledgeBaseID' => self::$testData['rootCategory']['knowledgeBaseID']
        ];
        $response = $this->api()->get('/knowledge/search', $params);
        $result = $response->getBody();

        $article = reset($result);
        $response = $this->api()->get('/articles/'.$article['recordID'].'/articles-related');

        $this->assertEquals(1, count($response->getBody()));
    }

    /**
     * The get the GET /articles/related-articles endpoint.
     *
     * @depends testData
     */
    public function testGetRelatedArticleswithMinimum() {
        $article = $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => self::$testData['rootCategory']['knowledgeBaseID'],
            "name" => "Article apple",
            "body" => json_encode([["insert" => "apples are good"]]),
            "format" => "rich",
        ])->getBody();

        self::sphinxReindex();

        $params = [
            'minimumArticles' => 2
        ];

        $response = $this->api()->get('/articles/'.$article['articleID'].'/articles-related?'.http_build_query($params));

        $this->assertEquals(2, count($response->getBody()));
    }



    /**
     * Generate few categories and attach few articles
     */
    protected function prepareData() {
        $helloWorldBody = json_encode([["insert" => "Hello World"]]);
        // Setup the test categories.
        $knowledgeBase = $this->api()->post('knowledge-bases', [
            "name" => __FUNCTION__ . " KB #1",
            "Description" => 'Test knowledge base description',
            "urlCode" => slugify(
                'test-Knowledge-Base-'.round(microtime(true) * 1000).rand(1, 1000)
            ),
            "viewType" => KnowledgeBaseModel::TYPE_GUIDE,
            "sortArticles" => KnowledgeBaseModel::ORDER_MANUAL
        ])->getBody();

        $rootCategory = $this->api()->get($this->baseUrl.'/'.$knowledgeBase['rootCategoryID'])->getBody();

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
            "name" => "Primary Category Article apple",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        $childCategory = $this->api()->post($this->baseUrl, [
            "name" => __FUNCTION__ . " Child category",
            "parentID" => $rootCategory["knowledgeCategoryID"],
        ])->getBody();

        $child2Category = $this->api()->post($this->baseUrl, [
            "name" => __FUNCTION__ . " Child  2 category",
            "parentID" => $rootCategory["knowledgeCategoryID"],
        ])->getBody();

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $childCategory["knowledgeCategoryID"],
            "name" => "Primary Category Article",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        $childCategory2 = $this->api()->post($this->baseUrl, [
            "name" => __FUNCTION__ . " 2nd level child category",
            "parentID" => $childCategory["knowledgeCategoryID"],
        ])->getBody();

        $featuredArticle = $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $childCategory2["knowledgeCategoryID"],
            "name" => "Primary Category Article",
            "body" => json_encode([["insert" => "Article body with apple"]]),
            "format" => "rich",
        ])->getBody();

        $this->api()->put($this->kbArticlesUrl.'/'.$featuredArticle['articleID'].'/featured', [
            "featured" => true
        ])->getBody();

        //Lets move this category now level Up
        $child2Category = $this->api()
            ->delete($this->baseUrl.'/'.$child2Category['knowledgeCategoryID'])
            ->getBody();
    //
        $rootCategory = $this->api()->get($this->baseUrl.'/'.$rootCategory["knowledgeCategoryID"]);
        $childCategory = $this->api()->get($this->baseUrl.'/'.$childCategory["knowledgeCategoryID"]);

        self::$testData = [
            'rootCategory' => $rootCategory,
            'childCategory' => $childCategory,
            'child2Category' => $child2Category,
            'childCategory2' => $childCategory2,
            'featuredArticle' => $featuredArticle
        ];
    }
}
