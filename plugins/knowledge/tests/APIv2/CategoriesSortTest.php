<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * Test the /api/v2/knowledge-categories endpoint.
 */
class CategoriesSortTest extends AbstractAPIv2Test {

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-categories";

    /** @var string The resource route. */
    protected $kbArticlesUrl = "/articles";


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
    public static function setupBeforeClass(): void {
        self::$addons = ["vanilla", "knowledge"];
        parent::setupBeforeClass();
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
     * Test knowledge categories "sort" field calculations when post new articles and categories.
     *
     * @param string $articleKey Key to find real data response
     * @param int $sort Expected correct sort field value
     *
     * @dataProvider provideCategorySorts
     */
    public function testPatchArticleSameSort(string $articleKey, int $sort) {
        $data = $this->prepareFreshSortData(KnowledgeBaseModel::TYPE_GUIDE, 'testPatchArticleSameSort');

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
     * Generate few categories and attach few articles to them to have some variety of counts in DB for integrations tests.
     *
     * @param string $kbType Knowledge Base Type to generate "sort" data
     * @return array Generated categories filled with few articles
     */
    protected function prepareCategorySortData(string $kbType = KnowledgeBaseModel::TYPE_GUIDE): array {

        if (!isset(self::$preparedCategorySortData[$kbType])) {
            $helloWorldBody = json_encode([["insert" => "Hello World"]]);
            $sortArticles = $kbType === KnowledgeBaseModel::TYPE_GUIDE ? KnowledgeBaseModel::ORDER_MANUAL : KnowledgeBaseModel::ORDER_DATE_DESC;

            $newKnowledgeBase = $this->api()->post('knowledge-bases', [
                "name" => __FUNCTION__ . " Test Knowledge Base",
                "description" => 'Some description',
                "urlCode" => slugify(
                    'test-Knowledge-Base-'.round(microtime(true) * 1000).rand(1, 1000)
                ),
                "viewType" => $kbType,
                "sortArticles" => $sortArticles,
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
     * @param string $tmpDataPrefix Prefix to store some data keys statically to avoid unnecessary api calls
     * @return array Generated categories filled with few articles
     */
    protected function prepareFreshSortData(string $kbType = KnowledgeBaseModel::TYPE_GUIDE, string $tmpDataPrefix = ''): array {
        $index = $tmpDataPrefix.$kbType;

        if (!isset(self::$preparedCategorySortData[$index])) {
            $helloWorldBody = json_encode([["insert" => "Hello World"]]);
            $sortArticles = $kbType === KnowledgeBaseModel::TYPE_GUIDE ? KnowledgeBaseModel::ORDER_MANUAL : KnowledgeBaseModel::ORDER_DATE_DESC;

            $newKnowledgeBase = $this->api()->post('knowledge-bases', [
                "name" => __FUNCTION__ . " Test Knowledge Base",
                "description" => 'Some description',
                "urlCode" => slugify(
                    'test-Knowledge-Base-' . round(microtime(true) * 1000) . rand(1, 1000)
                ),
                "viewType" => $kbType,
                "sortArticles" => $sortArticles
            ])->getBody();

            // Setup the test categories.
            $rootCategory = $this->api()->get($this->baseUrl . '/' . $newKnowledgeBase['rootCategoryID'])->getBody();

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
            ])->getBody();

            $patched = $this->api()->patch(
                '/articles/'.$article2['articleID'],
                ['sort' => $article2['sort']]
            )->getBody();

            $patched = $this->api()->patch(
                '/articles/'.$article4['articleID'],
                ['sort' => $article3['sort']]
            )->getBody();

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
     * @param string $kbType Knowledge Base Type to generate "sort" data
     * @return array Generated categories filled with few articles
     */
    protected function prepareCategorySortPatchData(string $kbType = KnowledgeBaseModel::TYPE_GUIDE): array {
        $index = 'patch'.$kbType;
        if (!isset(self::$preparedCategorySortData[$index])) {
            $helloWorldBody = json_encode([["insert" => "Hello World"]]);
            $sortArticles = $kbType === KnowledgeBaseModel::TYPE_GUIDE ? KnowledgeBaseModel::ORDER_MANUAL : KnowledgeBaseModel::ORDER_DATE_DESC;

            $newKnowledgeBase = $this->api()->post('knowledge-bases', [
                "name" => __FUNCTION__ . " Test Knowledge Base",
                "description" => 'Some description',
                "urlCode" => slugify(
                    'test-Knowledge-Base-'.round(microtime(true) * 1000).rand(1, 1000)
                ),
                "viewType" => $kbType,
                "sortArticles" => $sortArticles,
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
     * @return array Data with expected correct Sort values
     */
    public function provideCategorySorts(): array {
        return [
            'article1' => [
                'article1',
                0
            ],
            'article2' => [
                'article2',
                1
            ],
            'subCat1' => [
                'subCat1',
                2
            ],
            'subCat2' => [
                'subCat2',
                3
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
                5
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
     * | Article4 |    6   |     6    |     5    |    4   |        3       |     3    |
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
                3
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
}
