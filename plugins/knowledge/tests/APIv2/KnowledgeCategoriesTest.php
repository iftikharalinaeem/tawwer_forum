<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;

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

    private static $preparedCategorySortData = [];

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
     * Test knowledge categories "sort" field calculations when post new articles and categories.
     *
     * @param string $articleKey Key to find real data response
     * @param int $sort Expected correct sort field value
     *
     * @dataProvider provideValidCategorySorts
     */
    public function testSortField(string $articleKey, int $sort) {

        $data = $this->prepareCategorySortData();

        if (substr($articleKey, 0, 7) === 'article') {
            $r = $this->api()->get(
                '/articles/'.$data[$articleKey]['articleID']
            )->getBody();
        } else {
            $r = $this->api()->get(
                $this->baseUrl.'/'.$data[$articleKey]['knowledgeCategoryID']
            )->getBody();
        }
        $this->assertEquals($sort, $r['sort']);
    }

    /**
     * Test knowledge categories "sort" field calculations when patch existing articles and categories.
     *
     * @param string $articleKey Key to find real data response
     * @param int $sort Expected correct sort field value
     *
     * @dataProvider provideValidCategorySortsPatch
     */
    public function testSortFieldPatch(string $articleKey, int $sort) {

        $data = $this->prepareCategorySortPatchData();

        if (substr($articleKey, 0, 7) === 'article') {
            $r = $this->api()->get(
                '/articles/'.$data[$articleKey]['articleID']
            )->getBody();
        } else {
            $r = $this->api()->get(
                $this->baseUrl.'/'.$data[$articleKey]['knowledgeCategoryID']
            )->getBody();
        }
        $this->assertEquals($sort, $r['sort']);
    }

    /**
     * Test knowledge categories "sort" field calculations when patch existing articles and categories.
     * (when KnowledgeBase is Help center type)
     *
     * @param string $articleKey Key to find real data response
     * @param int $sort Expected correct sort field value
     *
     * @dataProvider provideValidCategorySortsPatchHelp
     */
    public function testSortFieldPatchHelpMode(string $articleKey, array $sort) {

        $data = $this->prepareCategorySortPatchData(KnowledgeBaseModel::TYPE_HELP);

        if (substr($articleKey, 0, 7) === 'article') {
            $r = $this->api()->get(
                '/articles/'.$data[$articleKey]['articleID']
            )->getBody();
        } else {
            $r = $this->api()->get(
                $this->baseUrl.'/'.$data[$articleKey]['knowledgeCategoryID']
            )->getBody();
        }
        $this->assertEquals($sort[0], $r['sort']);
    }


    /**
     * Test knowledge categories "sort" field calculations when "help" center mode.
     *
     * @param string $articleKey Key to find real data response
     * @param int $sort Expected correct sort field value
     *
     * @dataProvider provideValidCategoryHelpSorts
     */
    public function testSortFieldHelpMode(string $articleKey, int $sort) {

        $data = $this->prepareCategorySortData(KnowledgeBaseModel::TYPE_HELP);

        if (substr($articleKey, 0, 7) === 'article') {
            $r = $this->api()->get(
                '/articles/'.$data[$articleKey]['articleID']
            )->getBody();
        } else {
            $r = $this->api()->get(
                $this->baseUrl.'/'.$data[$articleKey]['knowledgeCategoryID']
            )->getBody();
        }

        $this->assertEquals($sort, $r['sort']);
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

        $testData = $this->prepareCategoryToDelete();
        $categoryToDelete = $testData['childCategory'];
        $article = $testData['article'];


        $r = $this->api()->delete("{$this->baseUrl}/{$categoryToDelete['knowledgeCategoryID']}", []);

        $this->assertEquals(204, $r->getStatusCode());

        try {
            $this->api()->get("{$this->baseUrl}/{$categoryToDelete['knowledgeCategoryID']}");
        } catch (NotFoundException $ex) {
            $this->assertEquals(404, $ex->getCode());
        }

        $this->expectException(NotFoundException::class);
        $this->api()->get("{$this->kbArticlesUrl}/{$article['articleID']}");
    }

    /**
     * Generate few categories and attach few articles to them to have some variety of counts in DB for integrations tests.
     *
     * @param string $kbType Knowledge Base Type to generate "sort" data
     * @return array Generated categories filled with few articles
     */
    protected function prepareCategorySortData(string $kbType = KnowledgeBaseModel::TYPE_GUIDE): array {

        if (!isset(self::$preparedCategorySortData[$kbType])) {
            $helloWorldBody = json_encode([["insert" => "Hello World"]]);

            $newKnowledgeBase = $this->api()->post('knowledge-bases', [
                "name" => __FUNCTION__ . " Test Knowledge Base",
                "description" => 'Some description',
                "urlCode" => 'test-Knowledge-Base-'.round(microtime(true) * 1000).rand(1, 1000),
                "viewType" => $kbType
            ])->getBody();

            // Setup the test categories.
            $rootCategory = $this->api()->get($this->baseUrl.'/'.$newKnowledgeBase['rootCategoryID'])->getBody();

            $article1 = $this->api()->post($this->kbArticlesUrl, [
                "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Primary Category Article",
                "body" => $helloWorldBody,
                "format" => "rich",
            ])->getBody();

            $article2 = $this->api()->post($this->kbArticlesUrl, [
                "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Primary Category Article",
                "body" => $helloWorldBody,
                "format" => "rich",
            ])->getBody();

            $subCat1 = $this->api()->post($this->baseUrl, [
                "parentID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Sub Category",
            ])->getBody();

            $subCat2 = $this->api()->post($this->baseUrl, [
                "parentID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Sub Category 2",
            ])->getBody();

            $subCat3 = $this->api()->post($this->baseUrl, [
                "parentID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Sub Category 3",
                'sort' => $subCat2['sort']
            ])->getBody();


            $article3 = $this->api()->post($this->kbArticlesUrl, [
                "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Primary Category Article 3",
                "body" => $helloWorldBody,
                "format" => "rich",
            ])->getBody();

            $article4 = $this->api()->post($this->kbArticlesUrl, [
                "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Primary Category Article 4",
                "body" => $helloWorldBody,
                "format" => "rich",
                "sort" => 1,
            ])->getBody();

            self::$preparedCategorySortData[$kbType] = [
                'rootCategory' => $rootCategory,
                'subCat1' => $subCat1,
                'subCat2' => $subCat2,
                'subCat3' => $subCat3,
                'article1' => $article1,
                'article2' => $article2,
                'article3' => $article3,
                'article4' => $article4,
            ];
        }

        return self::$preparedCategorySortData[$kbType];
    }

    /**
     * Generate few categories and attach few articles to them to have some variety of counts in DB for integrations tests.
     *
     * @param string $kbType Knowledge Base Type to generate "sort" data
     * @return array Generated categories filled with few articles
     */
    protected function prepareCategorySortPatchData(string $kbType = KnowledgeBaseModel::TYPE_GUIDE): array {
        $index = 'patch'.$kbType;
        if (!isset(self::$preparedCategorySortData[$index])) {
            $helloWorldBody = json_encode([["insert" => "Hello World"]]);

            $newKnowledgeBase = $this->api()->post('knowledge-bases', [
                "name" => __FUNCTION__ . " Test Knowledge Base",
                "description" => 'Some description',
                "urlCode" => 'test-Knowledge-Base-'.round(microtime(true) * 1000).rand(1, 1000),
                "viewType" => $kbType
            ])->getBody();

            // Setup the test categories.
            $rootCategory = $this->api()->get($this->baseUrl.'/'.$newKnowledgeBase['rootCategoryID'])->getBody();

            $article1 = $this->api()->post($this->kbArticlesUrl, [
                "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Primary Category Article",
                "body" => $helloWorldBody,
                "format" => "rich",
            ])->getBody();

            $article2 = $this->api()->post($this->kbArticlesUrl, [
                "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Primary Category Article",
                "body" => $helloWorldBody,
                "format" => "rich",
            ])->getBody();

            $subCat1 = $this->api()->post($this->baseUrl, [
                "parentID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Sub Category",
            ])->getBody();

            $subCat2 = $this->api()->post($this->baseUrl, [
                "parentID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Sub Category 2",
            ])->getBody();

            $subCat3 = $this->api()->post($this->baseUrl, [
                "parentID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Sub Category 3"
            ])->getBody();

            $article3 = $this->api()->post($this->kbArticlesUrl, [
                "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Primary Category Article 3",
                "body" => $helloWorldBody,
                "format" => "rich",
            ])->getBody();

            $article4 = $this->api()->post($this->kbArticlesUrl, [
                "knowledgeCategoryID" => $rootCategory["knowledgeCategoryID"],
                "name" => "Primary Category Article 4",
                "body" => $helloWorldBody,
                "format" => "rich"
            ])->getBody();

            $article3 = $this->api()->patch($this->kbArticlesUrl.'/'.$article3['articleID'], [
                "sort" => 1,
                "knowledgeCategoryID" => $article3["knowledgeCategoryID"],
            ])->getBody();

            $article2 = $this->api()->patch($this->kbArticlesUrl.'/'.$article2['articleID'], [
                "knowledgeCategoryID" => $subCat3["knowledgeCategoryID"],
            ])->getBody();

            $subCat1 = $this->api()->patch($this->baseUrl.'/'.$subCat1['knowledgeCategoryID'], [
                "parentID" => $subCat3["knowledgeCategoryID"]
            ])->getBody();

            $subCat2 = $this->api()->patch($this->baseUrl.'/'.$subCat2['knowledgeCategoryID'], [
                "parentID" => $subCat3["knowledgeCategoryID"],
                "sort" => 1,
            ])->getBody();

            $subCat3 = $this->api()->patch($this->baseUrl.'/'.$subCat3['knowledgeCategoryID'], [
                "sort" => 0,
            ])->getBody();

            self::$preparedCategorySortData[$index] = [
                'rootCategory' => $rootCategory,
                'subCat1' => $subCat1,
                'subCat2' => $subCat2,
                'subCat3' => $subCat3,
                'article1' => $article1,
                'article2' => $article2,
                'article3' => $article3,
                'article4' => $article4,
            ];
        }



        return self::$preparedCategorySortData[$index];
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
            "urlCode" => 'test-Knowledge-Base-'.round(microtime(true) * 1000).rand(1, 1000),
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
            "urlCode" => 'test-Knowledge-Base'.round(microtime(true) * 1000).rand(1, 1000),
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
            "urlCode" => 'test-Knowledge-Base-'.round(microtime(true) * 1000).rand(1, 1000),
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
            "urlCode" => 'test-Knowledge-Base-'.round(microtime(true) * 1000).rand(1, 1000),
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

        return [
            'rootCategory' => $rootCategory,
            'childCategory' => $childCategory,
            'article' => $articleToDelete
        ];
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
     * @return array Data with expected correct Sort values
     */
    public function provideValidCategorySorts(): array {
        return [
            'article1' => [
                'article1',
                0
            ],
            'article2' => [
                'article2',
                2
            ],
            'subCat1' => [
                'subCat1',
                3
            ],
            'subCat2' => [
                'subCat2',
                5
            ],
            'subCat3' => [
                'subCat3',
                4
            ],
            'article3' => [
                'article3',
                6
            ],
            'article4' => [
                'article4',
                1
            ]
        ];
    }

    /**
     * @return array Data with expected correct sort values Help center type KB
     */
    public function provideValidCategoryHelpSorts(): array {
        return [
            'article1' => [
                'article1',
                0
            ],
            'article2' => [
                'article2',
                0
            ],
            'subCat1' => [
                'subCat1',
                0
            ],
            'subCat2' => [
                'subCat2',
                2
            ],
            'subCat3' => [
                'subCat3',
                1
            ],
            'article3' => [
                'article3',
                0
            ],
            'article4' => [
                'article4',
                1
            ]
        ];
    }

    /**
     * This data relates on $this->prepareCategorySortPatchData(KnowledgeBaseModel::TYPE_GUIDE)
     *
     * +----------+--------+----------+----------+--------+----------------+----------+
     * |          |  Init  |   Patch  |   Patch  |  Patch |      Patch     |   Patch  |
     * |          | (post) | Article3 | Article2 |  Cat1  |      Cat2      |   Cat3   |
     * |          |        | (sort=1) |  (move)  | (move) | (move; sort=1) | (sort=0) |
     * +----------+--------+----------+----------+--------+----------------+----------+
     * | Article1 |    0   |     0    |     0    |    0   |        0       |     1    |
     * +----------+--------+----------+----------+--------+----------------+----------+
     * | Article2 |    1   |     2    |     0    |    0   |        0       |     0    |
     * +----------+--------+----------+----------+--------+----------------+----------+
     * | Cat1     |    2   |     3    |     2    |    1   |        2       |     2    |
     * +----------+--------+----------+----------+--------+----------------+----------+
     * | Cat2     |    3   |     4    |     3    |    2   |        1       |     1    |
     * +----------+--------+----------+----------+--------+----------------+----------+
     * | Cat3     |    4   |     5    |     4    |    3   |        2       |     0    |
     * +----------+--------+----------+----------+--------+----------------+----------+
     * | Article3 |    5   |     1    |     1    |    1   |        1       |     2    |
     * +----------+--------+----------+----------+--------+----------------+----------+
     * | Article4 |    6   |     7    |     6    |    5   |        4       |     5    |
     * +----------+--------+----------+----------+--------+----------------+----------+
     *
     * @return array Data with expected correct Sort values
     */
    public function provideValidCategorySortsPatch(): array {
        return [
            'article1' => [
                'article1',
                1
            ],
            'article2' => [
                'article2',
                0
            ],
            'subCat1' => [
                'subCat1',
                2
            ],
            'subCat2' => [
                'subCat2',
                1
            ],
            'subCat3' => [
                'subCat3',
                0
            ],
            'article3' => [
                'article3',
                2
            ],
            'article4' => [
                'article4',
                5
            ]
        ];
    }

    /**
     * This data relates on $this->prepareCategorySortPatchData(KnowledgeBaseModel::TYPE_HELP)
     *
     * +-----------+--------+----------+----------+--------+----------------+----------+
     * |           |  Init  |   Patch  |   Patch  |  Patch |      Patch     |   Patch  |
     * |           | (post) | Article3 | Article2 |  Cat1  |      Cat2      |   Cat3   |
     * |           |        | (sort=1) |  (move)  | (move) | (move; sort=1) | (sort=0) |
     * +-----------+--------+----------+----------+--------+----------------+----------+
     * | Article1  |  null  |   null   |   null   |  null  |      null      |   null   |
     * +-----------+--------+----------+----------+--------+----------------+----------+
     * | Article2  |  null  |   null   |   null   |  null  |      null      |   null   |
     * +-----------+--------+----------+----------+--------+----------------+----------+
     * | Category1 |    0   |     0    |     0    |    0   |        0       |     0    |
     * +-----------+--------+----------+----------+--------+----------------+----------+
     * | Category2 |    1   |     2    |     2    |    1   |        1       |     1    |
     * +-----------+--------+----------+----------+--------+----------------+----------+
     * | Category3 |    2   |     3    |     3    |    2   |        1       |     0    |
     * +-----------+--------+----------+----------+--------+----------------+----------+
     * | Article3  |  null  |     1    |     1    |    1   |        1       |     1    |
     * +-----------+--------+----------+----------+--------+----------------+----------+
     * | Article4  |  null  |   null   |   null   |  null  |      null      |   null   |
     * +-----------+--------+----------+----------+--------+----------------+----------+
     *
     * @return array Data with expected correct Sort values
     */
    public function provideValidCategorySortsPatchHelp(): array {

        return [
            'article1' => [
                'article1',
                [null]
            ],
            'article2' => [
                'article2',
                [null]
            ],
            'subCat1' => [
                'subCat1',
                [0]
            ],
            'subCat2' => [
                'subCat2',
                [1]
            ],
            'subCat3' => [
                'subCat3',
                [0]
            ],
            'article3' => [
                'article3',
                [1]
            ],
            'article4' => [
                'article4',
                [null]
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
