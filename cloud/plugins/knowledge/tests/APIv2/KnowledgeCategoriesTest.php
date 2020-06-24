<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
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
        "foreignID"
    ];

    /** @var array Fields to be checked with get/<id>/edit */
    protected $patchFields = [
        "name",
        "parentID",
        "sort",
        "sortChildren",
        "foreignID"
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
    public static function setupBeforeClass(): void {
        self::$addons = ["vanilla", "sphinx", "knowledge"];
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
     * @param array $record Record defaults
     *
     * @return array
     */
    public function record(array $record = []): array {
        $record = [
            "name" => $record['name'] ?? "Test Knowledge Category",
            "parentID" => $record['parentID'] ?? -1,
            "knowledgeBaseID" => $record['knowledgeBaseID'] ?? 1,
            "sortChildren" => $record['sortChildren'] ?? "name",
            "sort" => $record['sort'] ?? 0,
            "foreignID" => "test-foreign-id-123"
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

        $this->expectException(ServerException::class);
        $this->api()->get("{$this->kbArticlesUrl}/{$article['articleID']}");
    }

    /**
     * Test POST /knowledge-categories.
     *
     * @param array $record Record placeholder
     * @param array $extra Extra options to keep compatibility to parent method
     *
     * @return array
     */
    public function testPost($record = null, array $extra = []): array {
        if ($record === null) {
            $record = $this->record();
            $kb = [
                'name' => 'KnowledgeCategoriesTest:'.__FUNCTION__,
                'description' => 'Test Knowledge Base DESCRIPTION',
                'viewType' => 'guide',
                'icon' => '',
                'bannerImage' => '',
                'sortArticles' => 'manual',
                'sourceLocale' => '',
                'urlCode' => slugify(
                    'KnowledgeCategoriesTest-'.__FUNCTION__.'-'.round(microtime(true) * 1000).rand(1, 1000)
                ),
            ];
            $knowledgeBase = $this->api()->post(
                '/knowledge-bases',
                $kb
            );
            $record['knowledgeBaseID'] = $knowledgeBase['knowledgeBaseID'];
        }
        return parent::testPost($record, $extra);
    }

    /**
     * Test POST endpoint when knowledge base has status "deleted"
     */
    public function testPostDeleted() {
        $knowledgeCategory = $this->testPost();
        $this->api()->patch("/knowledge-bases/{$knowledgeCategory['knowledgeBaseID']}", ['status' => KnowledgeBaseModel::STATUS_DELETED]);

        $record = $this->record($knowledgeCategory);
        $record['parentID'] = $knowledgeCategory['knowledgeCategoryID'];
        $this->expectException(NotFoundException::class);
        $newKnowledgeCategory =  $this->api()->post($this->baseUrl, $record);
    }

    /**
     * Generate rows for the index test.
     *
     * @param array $record The record to insert for the index.
     * @return array
     */
    private function generateIndexRowsRecord($record) {
        $rows = [];

        // Insert a few rows.
        for ($i = 0; $i < static::INDEX_ROWS; $i++) {
            $rows[] = $this->testPost($record);
        }

        return $rows;
    }

    /**
     * @inheritdoc
     */
    public function testIndex() {
        $record = $this->testPost();
        $recordPlaceholder = $this->record($record);

        $indexUrl = $this->indexUrl();
        $originalIndex = $this->api()->get($indexUrl);
        $originalRows = $originalIndex->getBody();
        $this->assertEquals(200, $originalIndex->getStatusCode());

        $rows = $this->generateIndexRowsRecord($recordPlaceholder);

        $newIndex = $this->api()->get($indexUrl);

        $newRows = $newIndex->getBody();
        $this->assertEquals(count($originalRows)+count($rows), count($newRows));
        // The index should be a proper indexed array.
        for ($i = 0; $i < count($newRows); $i++) {
            $this->assertArrayHasKey($i, $newRows);
        }

        if ($this->testPagingOnIndex) {
            $this->pagingTest($indexUrl);
        }

        // There's not much we can really test here so just return and let subclasses do some more assertions.
        return [$rows, $newRows];
    }

    /**
     * Test index endpoint when knowledge base status is "deleted"
     */
    public function testIndexDeleted() {
        $record = $this->testPost();
        $recordPlaceholder = $this->record($record);

        $indexUrl = $this->indexUrl();
        $originalIndex = $this->api()->get($indexUrl);
        $this->assertEquals(200, $originalIndex->getStatusCode());

        $rows = $this->generateIndexRowsRecord($recordPlaceholder);

        $this->api()->patch("/knowledge-bases/{$record['knowledgeBaseID']}", ['status' => KnowledgeBaseModel::STATUS_DELETED]);

        try {
            $newIndex = $this->api()->get($indexUrl);
            $newRows = $newIndex->getBody();
            foreach ($newRows as $cat) {
                if ($cat['knowledgeBaseID'] === $record['knowledgeBaseID']) {
                    $this->assertEquals(false, true, 'Index endpoint should fire a NotFoundException when knowledge base has status "deleted"!');
                }
            }
        } catch (NotFoundException $ex) {
            $this->assertEquals(404, $ex->getCode());
        }
    }

    /**
     * Test DELETE endpoint when knowledge base has status "deleted"
     */
    public function testDeleteDeleted() {

        $testData = $this->prepareCategoryToDelete();
        $categoryToDelete = $testData['childCategory'];

        $kb = $this->api()->patch("/knowledge-bases/{$categoryToDelete['knowledgeBaseID']}", ['status' => KnowledgeBaseModel::STATUS_DELETED]);

        $this->expectException(NotFoundException::class);
        $r = $this->api()->delete("{$this->baseUrl}/{$categoryToDelete['knowledgeCategoryID']}", []);
    }

    /**
     * Test GET /knowledge-categories/<id> when parent knowledge base status is "deleted"
     */
    public function testGetDeleted() {
        $row = $this->testPost();

        $this->api()->patch("/knowledge-bases/{$row['knowledgeBaseID']}", ['status' => KnowledgeBaseModel::STATUS_DELETED]);

        $this->expectException(NotFoundException::class);
        $r = $this->api()->get("{$this->baseUrl}/{$row['knowledgeCategoryID']}");
    }

    /**
     * Test GET /knowledge-categories/<id>/edit when parent knowledge base status is "deleted"
     */
    public function testGetEditDeleted() {
        $row = $this->testPost();
        $this->api()->patch("/knowledge-bases/{$row['knowledgeBaseID']}", ['status' => KnowledgeBaseModel::STATUS_DELETED]);

        $this->expectException(NotFoundException::class);
        $r = $this->api()->get("{$this->baseUrl}/{$row['knowledgeCategoryID']}/edit");
    }

    /**
     * Test PATCH /knowledge-categories/<id> when parent knowledge base has status "deleted"
     */
    public function testPatchDeleted() {
        $row = $this->testPost();
        $this->api()->patch("/knowledge-bases/{$row['knowledgeBaseID']}", ['status' => KnowledgeBaseModel::STATUS_DELETED]);

        $this->expectException(NotFoundException::class);
        $r = $this->api()->patch("{$this->baseUrl}/{$row['knowledgeCategoryID']}", $row);
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
            "urlCode" => slugify(
                'test-Knowledge-Base-'.round(microtime(true) * 1000).rand(1, 1000)
            ),
            "viewType" => KnowledgeBaseModel::TYPE_GUIDE,
            "sortArticles" => KnowledgeBaseModel::ORDER_MANUAL
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
