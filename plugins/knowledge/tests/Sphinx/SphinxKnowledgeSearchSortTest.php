<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use VanillaTests\APIv2\AbstractAPIv2Test;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use \Vanilla\Sphinx\Tests\Utils\SphinxTestTrait;

/**
 * Class SphinxKnowledgeSearchSortTest
 */
class SphinxKnowledgeSearchSortTest extends AbstractAPIv2Test {
    use SphinxTestTrait;

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-categories";

    /** @var string The resource route. */
    protected $kbArticlesUrl = "/articles";

    /** @var array $testData Data prepared for tests */
    protected static $testData;

    protected static $addons = ['vanilla', 'sphinx', 'knowledge'];

   /**
    * Prepare knowledge base data for tests and reindex Sphinx indexes.
    */
    public function testData() {
        $this->prepareData();
        self::sphinxReindex();
        $this->assertTrue(true);
    }

    /**
     * Test name, dateInserted, dateFeatured sorting options (both ascending and descending variants).
     *
     * @param string $sort
     * @param array $correctOrder
     * @depends testData
     * @dataProvider sortOptionsProvider
     */
    public function testSort(string $sort, array $correctOrder) {
        $response = $this->api()->get('/knowledge/search', ['sort' => $sort]);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(4, count($results));
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals(self::$testData[$correctOrder[$i]]['articleID'], $results[$i]['recordID']);
        }
    }

    /**
     * Data provider for Sort test.
     *
     * @return array
     */
    public function sortOptionsProvider() {
        return [
            'ASC name' => [
                'name',
                [
                    'A_Article',
                    'B_Article',
                    'C_Article',
                    'Z_Article'
                ],
            ],
            'DESC name' => [
                '-name',
                [
                    'Z_Article',
                    'C_Article',
                    'B_Article',
                    'A_Article'
                ],
            ],
            'ASC dateInserted' => [
                'dateInserted',
                [
                    'A_Article',
                    'B_Article',
                    'Z_Article',
                    'C_Article'
                ],
            ],
            'DESC dateInserted' => [
                '-dateInserted',
                [
                    'C_Article',
                    'Z_Article',
                    'B_Article',
                    'A_Article'
                ],
            ],
            'ASC dateFeatured' => [
                'dateFeatured',
                [
                    'B_Article',
                    'A_Article',
                    'C_Article',
                    'Z_Article'
                ],
            ],
            'DESC dateFeatured' => [
                '-dateFeatured',
                [
                    'Z_Article',
                    'C_Article',
                    'A_Article',
                    'B_Article'
                ],
            ]
        ];
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

        $article1 = $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
            "name" => "A Article #1",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        sleep(1);

        $article2 = $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
            "name" => "B Article #2",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        sleep(1);

        $article3 = $featuredArticle = $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
            "name" => "Z Article #3",
            "body" => json_encode([["insert" => "Article body with apple"]]),
            "format" => "rich",
        ])->getBody();

        $this->api()->put($this->kbArticlesUrl.'/'.$article2['articleID'].'/featured', [
            "featured" => true
        ])->getBody();

        sleep(1);
        $article4 = $featuredArticle = $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
            "name" => "C Article #4",
            "body" => json_encode([["insert" => "Article body with apple"]]),
            "format" => "rich",
        ])->getBody();

        $this->api()->put($this->kbArticlesUrl.'/'.$article1['articleID'].'/featured', [
            "featured" => true
        ])->getBody();
        sleep(1);
        $this->api()->put($this->kbArticlesUrl.'/'.$article4['articleID'].'/featured', [
            "featured" => true
        ])->getBody();

        sleep(1);
        $this->api()->put($this->kbArticlesUrl.'/'.$article3['articleID'].'/featured', [
            "featured" => true
        ])->getBody();

        self::$testData = [
            'rootCategory' => $rootCategory,
            'A_Article' => $article1,
            'B_Article' => $article2,
            'C_Article' => $article4,
            'Z_Article' => $article3
        ];
    }
}
