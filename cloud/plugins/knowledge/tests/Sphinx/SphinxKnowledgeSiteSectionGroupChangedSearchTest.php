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
 *
 * This is a copy of set of tests in SphinxKnowledgeSearchTest
 * but with knowledgebase soft deleted, that means that all variants of search should return 0 results.
 *
 */
class SphinxKnowledgeSiteSectionGroupChangedSearchTest extends AbstractAPIv2Test {

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-categories";

    /** @var string The resource route. */
    protected $kbArticlesUrl = "/articles";

    /** @var array $testData Data prepared for tests */
    protected static $testData;

    /** @var array $dockerResponse Stdout log saved when try to reindex Sphinx indexes */
    protected static $dockerResponse;

   /** @var bool */
    protected static $sphinxReindexed;

    protected static $addons = ['vanilla', 'sphinx', 'knowledge'];

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
    }

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
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }
        $this->assertTrue(true);
    }

    /**
     * @return array Data provider for testSearch
     */
    public function beforeSiteSectionGroupUpdate() {
        $webRoot = 'http://vanilla.test/'.strtolower(__CLASS__);
        return [
            'By name' => [['name' => 'apple'], 1, $webRoot.'/en/kb/articles/'],
            'By body' => [['body' => 'apple'], 1, $webRoot.'/en/kb/articles/'],
            'By all (name or body)' => [['all' => 'apple'], 2, $webRoot.'/en/kb/articles/'],
            'By root knowledgeCategoryID' => [['knowledgeCategoryIDs' => ['rootCategory']], 1, $webRoot.'/en/kb/articles/'],
            'By child knowledgeCategoryID' => [['knowledgeCategoryIDs' => ['childCategory']], 1, $webRoot.'/en/kb/articles/'],
            'By all knowledgeCategoryIDs' => [
                ['knowledgeCategoryIDs' => ['rootCategory', 'childCategory', 'child2Category', 'childCategory2']],
                3,
                $webRoot.'/en/kb/articles/'
            ],
            'By siteSectionGroup-1' => [
                ['all' => 'apple', 'siteSectionGroup' => "mockSiteSectionGroup-1"],
                2,
                $webRoot.'/en/kb/articles/'
            ],
            'By siteSectionGroup-2' => [['all' => 'apple', 'siteSectionGroup' => "mockSiteSectionGroup-2"], 0, ''],

        ];
    }

    /**
     * @return array Data povider for testSearchUpdated
     */
    public function afterSiteSectionGroupUpdate() {
        $webRoot = 'http://vanilla.test/'.strtolower(__CLASS__);
        return [
            'By name' => [
                ['name' => 'apple'],
                1,
                $webRoot.'/ssg2-en/kb/articles/'
            ],
            'By body' => [['body' => 'apple'], 1, $webRoot.'/ssg2-en/kb/articles/'],
            'By all (name or body)' => [['all' => 'apple'], 2, $webRoot.'/ssg2-en/kb/articles/'],
            'By root knowledgeCategoryID' => [['knowledgeCategoryIDs' => ['rootCategory']], 1, $webRoot.'/ssg2-en/kb/articles/'],
            'By child knowledgeCategoryID' => [['knowledgeCategoryIDs' => ['childCategory']], 1, $webRoot.'/ssg2-en/kb/articles/'],
            'By all knowledgeCategoryIDs' => [
                ['knowledgeCategoryIDs' => ['rootCategory', 'childCategory', 'child2Category', 'childCategory2']],
                3,
                $webRoot.'/ssg2-en/kb/articles/'
            ],
            'By siteSectionGroup-1' => [['all' => 'apple', 'siteSectionGroup' => "mockSiteSectionGroup-1"], 0, ''],
            'By siteSectionGroup-2' => [
                ['all' => 'apple', 'siteSectionGroup' => "mockSiteSectionGroup-2"],
                2,
                $webRoot.'/ssg2-en/kb/articles/'
            ],
        ];
    }

    /**
     * Test search results
     *
     * @param array $params Query parameters to pass to the search api
     * @param int $count Expected correct count value
     * @param string $url Expected string url shpuld starts with
     * @depends testData
     * @dataProvider beforeSiteSectionGroupUpdate
     */
    public function testSearch(array $params, int $count, string $url) {
        if (isset($params['knowledgeCategoryIDs'])) {
            $ids = [];
            foreach ($params['knowledgeCategoryIDs'] as $catKey) {
                $ids[] = self::$testData[$catKey]['knowledgeCategoryID'];
            }
            $params['knowledgeCategoryIDs'] = $ids;
        }
        $response = $this->api()->get('/knowledge/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals($count, count($results));
        if ($count > 0) {
            $article = reset($results);
            $this->assertStringStartsWith($url, $article['url']);
        }
    }

    /**
     * @depends testSearch
     */
    public function testPatchKnowledgeBase() {
        $knowledgeBase = $this->api()->patch('knowledge-bases/'.self::$testData['knowledgeBase']['knowledgeBaseID'], [
            "siteSectionGroup" => "mockSiteSectionGroup-2",
        ])->getBody();
        self::sphinxReindex();
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }
        $this->assertTrue(true);
    }

    /**
     * Test search results acter kb update
     *
     * @param array $params Query parameters to pass to the search api
     * @param int $count Expected correct count value
     * @param string $url Expected string url shpuld starts with
     *
     * @depends testPatchKnowledgeBase
     * @dataProvider afterSiteSectionGroupUpdate
     */
    public function testSearchUpdated(array $params, int $count, string $url) {
        if (isset($params['knowledgeCategoryIDs'])) {
            $ids = [];
            foreach ($params['knowledgeCategoryIDs'] as $catKey) {
                $ids[] = self::$testData[$catKey]['knowledgeCategoryID'];
            }
            $params['knowledgeCategoryIDs'] = $ids;
        }
        $response = $this->api()->get('/knowledge/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals($count, count($results));

        if ($count > 0) {
            $article = reset($results);
            $this->assertStringStartsWith($url, $article['url']);
        }
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
            "sortArticles" => KnowledgeBaseModel::ORDER_MANUAL,
            "siteSectionGroup" => "mockSiteSectionGroup-1"
        ])->getBody();

        $rootCategory = $this->api()->get($this->baseUrl.'/'.$knowledgeBase['rootCategoryID'])->getBody();

        $article1 = $this->api()->post($this->kbArticlesUrl, [
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

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $childCategory2["knowledgeCategoryID"],
            "name" => "Primary Category Article",
            "body" => json_encode([["insert" => "Article body with apple"]]),
            "format" => "rich",
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
            'knowledgeBase' => $knowledgeBase
        ];
    }
}
