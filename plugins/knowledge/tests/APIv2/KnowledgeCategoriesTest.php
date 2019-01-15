<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Models\ArticleModel;

/**
 * Test the /api/v2/knowledge-categories endpoint.
 */
class KnowledgeCategoriesTest extends AbstractResourceTest {

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-categories";

    /** @var string The resource route. */
    protected $kbArticlesUrl = "/articles";

    /** @var array Fields to be checked with get/<id>/edit */
    protected $editFields = [
        "name",
        "parentID",
        "sort",
        "sortChildren",
    ];

    /** @var array Fields to be checked with get/<id>/edit */
    protected $patchFields = [
        "name",
        "parentID",
        "sort",
        "sortChildren",
    ];


    /** @var string The name of the primary key of the resource. */
    protected $pk = "knowledgeCategoryID";

    /** @var string The singular name of the resource. */
    protected $singular = "knowledgeCategory";

    /** @var bool Whether to check if paging works or not in the index. */
    protected $testPagingOnIndex = false;

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass() {
        self::$addons = ["vanilla", "knowledge"];
        parent::setupBeforeClass();
    }

    /**
     * Modify the row for update requests.
     *
     * @param array $row The row to modify.
     * @return array Returns the modified row.
     */
    public function modifyRow(array $row) {
        $row["name"] = $row["name"] ?? "Knowledge Category";
        $row["parentID"] = $row["parentID"] ?? -1;
        $row["sort"] = $row["sort"] ?? 1;
        $row["sortChildren"] = $row["sortChildren"] ?? "name";

        $row["name"] = md5($row["name"]);
        $row["sort"]++;

        $sortChildrenDiff = array_diff(
            ["name", "dateInserted", "dateInsertedDesc", "manual"],
            [$row["sortChildren"]]
        );
        $row["sortChildren"] = array_rand(array_flip($sortChildrenDiff));

        return $row;
    }

    /**
     * Grab values for inserting a new knowledge category.
     *
     * @return array
     */
    public function record() {
        $record = [
            "name" => "Test Knowledge Category",
            "parentID" => -1,
            "knowledgeBaseID" => 1,
            "sortChildren" => "name",
            "sort" => 0,
        ];
        return $record;
    }

    /**
     * Test knowledge categories "count" fields calculations.
     *
     * @param string $categoryKey Key to find real data response
     * @param array $correctCounts Expected correct count fields provided by dataProvider
     *
     * @dataProvider provideValidCategoriesCounts
     */
    public function testCountFields(string $categoryKey, array $correctCounts) {

        $data = $this->prepareCategoriesData();

        $categoryResponse = $this->api()->get(
            $this->baseUrl.'/'.$data[$categoryKey]['knowledgeCategoryID']
        )->getBody();

        $this->assertEquals($correctCounts['articleCount'], $categoryResponse['articleCount'], 'articleCount');
        $this->assertEquals($correctCounts['articleCountRecursive'], $categoryResponse['articleCountRecursive'], 'articleCountRecursive');
        $this->assertEquals($correctCounts['childCategoryCount'], $categoryResponse['childCategoryCount'], 'childCategoryCount');
    }

    /**
     * Test knowledge categories "count" fields calculations
     * when category moved from some lower level to upper level parent.
     *
     * @param string $categoryKey Key to find real data response
     * @param array $correctCounts Expected correct count fields provided by dataProvider
     *
     * @dataProvider provideValidCategoriesCountsAfterMoveUp
     */
    public function testCountFieldsAfterCategoryMove(string $categoryKey, array $correctCounts) {

        $data = $this->prepareCategoryMove();

        $categoryResponse = $this->api()->get(
            $this->baseUrl.'/'.$data[$categoryKey]['knowledgeCategoryID']
        )->getBody();

        $this->assertEquals($correctCounts['articleCount'], $categoryResponse['articleCount'], 'articleCount');
        $this->assertEquals($correctCounts['articleCountRecursive'], $categoryResponse['articleCountRecursive'], 'articleCountRecursive');
        $this->assertEquals($correctCounts['childCategoryCount'], $categoryResponse['childCategoryCount'], 'childCategoryCount');
    }

    /**
     * Test knowledge categories "count" fields calculations
     * when delete category.
     *
     * @param string $categoryKey Key to find real data response
     * @param array $correctCounts Expected correct count fields provided by dataProvider
     *
     * @dataProvider provideValidCategoriesCountsAfterDelete
     */
    public function testCountFieldsAfterCategoryDelete(string $categoryKey, array $correctCounts) {

        $data = $this->prepareCategoryDelete();

        $categoryResponse = $this->api()->get(
            $this->baseUrl.'/'.$data[$categoryKey]['knowledgeCategoryID']
        )->getBody();

        $this->assertEquals($correctCounts['articleCount'], $categoryResponse['articleCount'], 'articleCount');
        $this->assertEquals($correctCounts['articleCountRecursive'], $categoryResponse['articleCountRecursive'], 'articleCountRecursive');
        $this->assertEquals($correctCounts['childCategoryCount'], $categoryResponse['childCategoryCount'], 'childCategoryCount');
    }

    /**
     * @inheritdoc
     *
     */
    public function testDelete() {
        $categoryToDelete = $this->prepareCategoryToDelete();

        $r = $this->api()->delete("{$this->baseUrl}/{$categoryToDelete['knowledgeCategoryID']}", []);

        $this->assertEquals(204, $r->getStatusCode());

        $this->expectException(NotFoundException::class);
        $this->api()->get("{$this->baseUrl}/{$categoryToDelete['knowledgeCategoryID']}");
    }

    /**
     * Generate few categories and attach few articles to them to have some variety of counts in DB for integrations tests.
     *
     * @return array Generated categories filled with few articles
     */
    protected function prepareCategoriesData(): array {
        $helloWorldBody = json_encode([["insert" => "Hello World"]]);

        $newKnowledgeBase = $this->api()->post('knowledge-bases', [
            "name" => __FUNCTION__ . " Test Knowledge Base",
            "description" => 'Some description',
            "urlCode" => 'test-Knowledge-Base-'.$this->ms().rand(1, 1000),
        ])->getBody();
        // Setup the test categories.
        $rootCategory = $this->api()->get($this->baseUrl.'/'.$newKnowledgeBase['rootCategoryID'])->getBody();

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
            "name" => "Primary Category Article",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
            "name" => "Primary Category Article",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
            "name" => "Primary Category Article",
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

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $childCategory["knowledgeCategoryID"],
            "name" => "Primary Category Article",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $childCategory["knowledgeCategoryID"],
            "name" => "Primary Category Article",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        $articleToDelete = $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $childCategory["knowledgeCategoryID"],
            "name" => "Primary Category Article",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        $articleToDelete = $this->api()->patch($this->kbArticlesUrl.'/'.$articleToDelete['articleID'].'/status', [
            "status" => ArticleModel::STATUS_DELETED
        ])->getBody();


        $childCategory2 = $this->api()->post($this->baseUrl, [
            "name" => __FUNCTION__ . " 2nd level child category",
            "parentID" => $childCategory["knowledgeCategoryID"],
        ])->getBody();

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $childCategory2["knowledgeCategoryID"],
            "name" => "Primary Category Article",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $childCategory2["knowledgeCategoryID"],
            "name" => "Primary Category Article",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $childCategory2["knowledgeCategoryID"],
            "name" => "Primary Category Article",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();


        return [
            'rootCategory' => $rootCategory,
            'childCategory' => $childCategory,
            'child2Category' => $child2Category,
            'childCategory2' => $childCategory2,
        ];
    }


    /**
     * Generate few categories and attach few articles to them same way as prepareCategoriesData
     * Then: move one category.
     *
     * @return array Generated categories filled with few articles
     */
    protected function prepareCategoryMove(): array {
        $helloWorldBody = json_encode([["insert" => "Hello World"]]);
        // Setup the test categories.
        $knowledgeBase = $this->api()->post('knowledge-bases', [
            "name" => __FUNCTION__ . " KB #1",
            "Description" => 'Test knowledge base description',
            "urlCode" => 'test-Knowledge-Base'.$this->ms().rand(1, 1000),
        ])->getBody();

        $rootCategory = $this->api()->get($this->baseUrl.'/'.$knowledgeBase['rootCategoryID'])->getBody();

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
            "name" => "Primary Category Article",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
            "name" => "Primary Category Article #2",
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
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        //Lets move this category now level Up
        $childCategory2 = $this->api()->patch($this->baseUrl.'/'.$childCategory2['knowledgeCategoryID'], [
            "name" => __FUNCTION__ . " 2nd level child category MOVED",
            "parentID" => $rootCategory["knowledgeCategoryID"],
        ])->getBody();
//
        $rootCategory = $this->api()->get($this->baseUrl.'/'.$rootCategory["knowledgeCategoryID"]);
        $childCategory = $this->api()->get($this->baseUrl.'/'.$childCategory["knowledgeCategoryID"]);

        return [
            'rootCategory' => $rootCategory,
            'childCategory' => $childCategory,
            'child2Category' => $child2Category,
            'childCategory2' => $childCategory2,
        ];
    }

    /**
     * Get current microtime as an int
     *
     * @return int
     */
    public static function ms() {
        $mt = explode(' ', microtime());
        return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
    }

    /**
     * Generate few categories and attach few articles to them same way as prepareCategoriesData
     * Then: delete one category.
     *
     * @return array Generated categories filled with few articles
     */
    protected function prepareCategoryDelete(): array {
        $helloWorldBody = json_encode([["insert" => "Hello World"]]);
        // Setup the test categories.
        $knowledgeBase = $this->api()->post('knowledge-bases', [
            "name" => __FUNCTION__ . " KB #1",
            "Description" => 'Test knowledge base description',
            "urlCode" => 'test-Knowledge-Base-'.$this->ms().rand(1, 1000),
        ])->getBody();

        $rootCategory = $this->api()->get($this->baseUrl.'/'.$knowledgeBase['rootCategoryID'])->getBody();

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
            "name" => "Primary Category Article",
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
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        //Lets move this category now level Up
        $child2Category = $this->api()
            ->delete($this->baseUrl.'/'.$child2Category['knowledgeCategoryID'])
            ->getBody();
//
        $rootCategory = $this->api()->get($this->baseUrl.'/'.$rootCategory["knowledgeCategoryID"]);
        $childCategory = $this->api()->get($this->baseUrl.'/'.$childCategory["knowledgeCategoryID"]);

        return [
            'rootCategory' => $rootCategory,
            'childCategory' => $childCategory,
            'child2Category' => $child2Category,
            'childCategory2' => $childCategory2,
        ];
    }

    /**
     * Prepare one category with one soft deleted article
     *
     * @return array Generated categories filled with few articles
     */
    protected function prepareCategoryToDelete(): array {
        $helloWorldBody = json_encode([["insert" => "Hello World"]]);
        // Setup the test categories.
        $knowledgeBase = $this->api()->post('knowledge-bases', [
            "name" => __FUNCTION__ . " KB #1",
            "Description" => 'Test knowledge base description',
            "urlCode" => 'test-Knowledge-Base-'.$this->ms().rand(1, 1000),
        ])->getBody();

        $rootCategory = $this->api()->get($this->baseUrl.'/'.$knowledgeBase['rootCategoryID'])->getBody();

        $childCategory = $this->api()->post($this->baseUrl, [
            "name" => __FUNCTION__ . " Child category",
            "parentID" => $rootCategory["knowledgeCategoryID"],
        ])->getBody();

        $articleToDelete = $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $childCategory["knowledgeCategoryID"],
            "name" => "Primary Category Article",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        $articleToDelete = $this->api()->patch($this->kbArticlesUrl.'/'.$articleToDelete['articleID'].'/status', [
            "status" => ArticleModel::STATUS_DELETED
        ])->getBody();

        $rootCategory = $this->api()->get($this->baseUrl.'/'.$rootCategory["knowledgeCategoryID"]);
        $childCategory = $this->api()->get($this->baseUrl.'/'.$childCategory["knowledgeCategoryID"]);

        return  $childCategory->getBody();
    }
    /**
     * @return array Data with expected correct Count values
     */
    public function provideValidCategoriesCounts(): array {
        return [
            'Root category' => [
                'rootCategory',
                ['articleCount' => 3, 'articleCountRecursive' => 9, 'childCategoryCount' => 2]
            ],
            '1st Level Child Category' => [
                'childCategory',
                ['articleCount' => 3, 'articleCountRecursive' => 6, 'childCategoryCount' => 1]
            ],
            '1st Level 2nd Child Empty Category' => [
                'child2Category',
                ['articleCount' => 0, 'articleCountRecursive' => 0, 'childCategoryCount' => 0]
            ],
            '2nd Level Child Category' => [
                'childCategory2',
                ['articleCount' => 3, 'articleCountRecursive' => 3, 'childCategoryCount' => 0]
            ]
        ];
    }

    /**
     * @return array Data with expected correct Count values
     */
    public function provideValidCategoriesCountsAfterMoveUp(): array {
        return [
            'Root category' => [
                'rootCategory',
                ['articleCount' => 2, 'articleCountRecursive' => 4, 'childCategoryCount' => 3]
            ],
            '1st Level Child Category' => [
                'childCategory',
                ['articleCount' => 1, 'articleCountRecursive' => 1, 'childCategoryCount' => 0]
            ],
            '1st Level 2nd Child Empty Category' => [
                'child2Category',
                ['articleCount' => 0, 'articleCountRecursive' => 0, 'childCategoryCount' => 0]
            ],
            '2nd Level Child Category' => [
                'childCategory2',
                ['articleCount' => 1, 'articleCountRecursive' => 1, 'childCategoryCount' => 0]
            ],
        ];
    }

    /**
     * @return array Data with expected correct Count values
     */
    public function provideValidCategoriesCountsAfterDelete(): array {
        return [
            'Root category' => [
                'rootCategory',
                ['articleCount' => 1, 'articleCountRecursive' => 3, 'childCategoryCount' => 1]
            ],
            '1st Level Child Category' => [
                'childCategory',
                ['articleCount' => 1, 'articleCountRecursive' => 2, 'childCategoryCount' => 1]
            ],
            '2nd Level Child Category' => [
                'childCategory2',
                ['articleCount' => 1, 'articleCountRecursive' => 1, 'childCategoryCount' => 0]
            ],
        ];
    }
}
