<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
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
     * @inheritdoc
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

        try {
            $this->api()->delete("{$this->baseUrl}/{$testData['rootCategory']['knowledgeCategoryID']}");
        } catch (ClientException $ex) {
            $this->assertEquals(409, $ex->getCode());
        }

        $this->expectException(NotFoundException::class);
        $this->api()->get("{$this->kbArticlesUrl}/{$article['articleID']}");
    }

    /**
     * Test patch method over root category
     */
    public function testPatchRootCategory() {
        $data = $this->prepareCategoryToDelete();

        try {
            $this->api()->patch(
                "{$this->baseUrl}/{$data['rootCategory']['knowledgeCategoryID']}",
                ['name'=>'New Root Category Title']
            );
            $this->assertTrue(false, 'Root category not failed when PATCH called through API!');
        } catch (ClientException $ex) {
            $this->assertEquals(409, $ex->getCode());
        }
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
}
