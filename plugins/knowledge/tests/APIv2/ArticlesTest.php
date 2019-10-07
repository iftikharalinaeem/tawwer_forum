<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Garden\Web\Exception\NotFoundException;
use VanillaTests\Fixtures\MockSiteSectionProvider;

/**
 * Test the /api/v2/articles endpoint.
 */
class ArticlesTest extends AbstractResourceTest {

    /** @var int */
    private static $knowledgeBaseID;

    /** @var int */
    private static $knowledgeCategoryID;

    /** @var string The resource route. */
    protected $baseUrl = "/articles";

    /** @var array Fields to be checked with get/<id>/edit */
    protected $editFields = [
        "body",
        "format",
        "knowledgeCategoryID",
        "locale",
        "name",
        "sort",
    ];

    /** @var string The name of the primary key of the resource. */
    protected $pk = "articleID";

    /** @var string The singular name of the resource. */
    protected $singular = "article";

    private $defaultKB = [];

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass() {
        self::$addons = ["vanilla", "knowledge"];
        parent::setupBeforeClass();

        /** @var KnowledgeBaseModel $knowledgeBaseModel */
        $knowledgeBaseModel = self::container()->get(KnowledgeBaseModel::class);
        self::$knowledgeBaseID = $knowledgeBaseModel->insert([
            "name" => __CLASS__,
            "description" => "Basic knowledge base for testing.",
            "urlCode" => strtolower(substr(strrchr(__CLASS__, "\\"), 1)),
        ]);

        /** @var KnowledgeCategoryModel $knowledgeCategoryModel */
        $knowledgeCategoryModel = self::container()->get(KnowledgeCategoryModel::class);
        self::$knowledgeCategoryID = $knowledgeCategoryModel->insert([
            "name" => __CLASS__,
            "parentID" => -1,
            "knowledgeBaseID" => self::$knowledgeBaseID,
        ]);

    }

    /**
     * Modify the row for update requests.
     *
     * @param array $row The row to modify.
     * @return array Returns the modified row.
     */
    public function modifyRow(array $row): array {
        $row = $row + $this->record();

        $row["body"] = md5($row["body"]);
        $row["format"] = $row["body"] === "markdown" ? "text" : "markdown";
        $row["locale"] = $row["locale"] === "en" ? "fr" : "en";
        $row["name"] = md5($row["name"]);
        $row["sort"]++;

        return $row;
    }

    /**
     * Provide status patching data.
     *
     * @return array
     */
    public function providePatchStatusData(): array {
        // PHPUnit has issues with auto-loading the ArticleModel class for the status constants when data providers are invoked.
        return [
            ["deleted"], // ArticleModel::STATUS_DELETED
            ["published"], // ArticleModel::STATUS_PUBLISHED
            ["undeleted"], // ArticleModel::STATUS_UNDELETED
        ];
    }

    /**
     * Grab values for inserting a new article.
     *
     * @return array
     */
    public function record(): array {
        $record = [
            "body" => "Hello. I am a test for articles.",
            "format" => "markdown",
            "knowledgeCategoryID" => self::$knowledgeCategoryID,
            "locale" => "en",
            "name" => "Example Article",
            "sort" => 1,
        ];
        return $record;
    }

    /**
     * Create new knowledge base.
     *
     * @return array Knowledge base
     */
    public function newKnowledgeBase(): array {
        $salt = '-' . round(microtime(true) * 1000) . rand(1, 1000);
        $record = [
            'name' => 'Test Knowledge Base',
            'description' => 'Test Knowledge Base ' . $salt,
            'viewType' => 'guide',
            'icon' => '',
            'bannerImage' => '',
            'sortArticles' => 'manual',
            'sourceLocale' => 'en',
            'urlCode' => 'test-knowledge-base' . $salt,
        ];
        $kb = $this->api()
            ->post('/knowledge-bases', $record)
            ->getBody();
        return $kb;
    }

    /**
     * Test DELETE /articles/<id>.
     */
    public function testDelete() {
        if (!method_exists(ArticlesApiController::class, "delete")) {
            $this->markTestSkipped("Deleting an article is not implemented.");
        } else {
            $this->fail("Missing test for deleting an article.");
        }
    }

    /**
     * Test GET /articles/<id> when knowledge base has status "deleted"
     */
    public function testGetDeleted() {
        $article = $this->prepareDeletedKnowledgeBase();

        $this->expectException(NotFoundException::class);

        $r = $this->api()->get(
            "{$this->baseUrl}/{$article[$this->pk]}"
        );
    }

    /**
     * Test POST /articles when knowledge base has status "deleted"
     */
    public function testPostDeleted() {
        $kb = $this->newKnowledgeBase();
        $record = $this->record();
        $record['knowledgeCategoryID'] = $kb['rootCategoryID'];

        $this->api()->patch(
            "/knowledge-bases/{$kb['knowledgeBaseID']}",
            ['status' => KnowledgeBaseModel::STATUS_DELETED]
        );
        $this->expectException(NotFoundException::class);
        $article = $this->testPost($record);
    }

    /**
     * Test GET /articles/<id>/edit when knowledge base has status "deleted"
     */
    public function testGetEditDeleted() {
        $article = $this->prepareDeletedKnowledgeBase();

        $this->expectException(NotFoundException::class);

        $r = $this->api()->get(
            "{$this->baseUrl}/{$article[$this->pk]}/edit"
        );
    }

    /**
     * Test PATCH /resource/<id> with a a single field update.
     *
     * Patch endpoints should be able to update every field on its own.
     *
     * @param string $field The name of the field to patch.
     * @dataProvider providePatchFields
     */
    public function testPatchSparse($field) {
        if (empty($this->defaultKB)) {
            $this->defaultKB = $this->api()->post('knowledge-bases', [
                "name" => __FUNCTION__ . " KB #1",
                "Description" => 'Test knowledge base description',
                "urlCode" => slugify('test-' . __FUNCTION__ . '-' . round(microtime(true) * 1000) . rand(1, 1000)),
                "viewType" => KnowledgeBaseModel::TYPE_GUIDE,
                "sortArticles" => KnowledgeBaseModel::ORDER_MANUAL
            ])->getBody();
        }

        $row = $this->testGetEdit();

        $patchRow = $this->modifyRow($row);

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            [
                'knowledgeCategoryID' => $patchRow['knowledgeCategoryID'],
                $field => $patchRow[$field]
            ]
        );

        $this->assertEquals(200, $r->getStatusCode());

        $newRow = $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}/edit");
        $this->assertSame($patchRow[$field], $newRow[$field]);
    }

    /**
     * Test PATCH /resource/<id> with previousRevisionID.
     *
     * Patch endpoints should be able to update 1st attempt and generate conflict with 2nd one.
     */
    public function testPatchPreviousRevisionID() {
        if (empty($this->defaultKB)) {
            $this->defaultKB = $this->api()->post('knowledge-bases', [
                "name" => __FUNCTION__ . " KB #1",
                "Description" => 'Test knowledge base description',
                "urlCode" => slugify('test-' . __FUNCTION__ . '-' . round(microtime(true) * 1000) . rand(1, 1000)),
                "viewType" => KnowledgeBaseModel::TYPE_GUIDE,
                "sortArticles" => KnowledgeBaseModel::ORDER_MANUAL
            ])->getBody();
        }

        $row = $this->testGetEdit();

        $patchRow = $this->modifyRow($row);
        $revisions = $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}/revisions")->getBody();

        // Verify there is one, and only one, published revision and that it is the latest revision.
        $publishedRevision = null;
        foreach ($revisions as $revision) {
            if ($revision["status"] === ArticleModel::STATUS_PUBLISHED) {
                if ($publishedRevision) {
                    $this->fail("Multiple published revisions detected for a single article.");
                }
                $publishedRevision = $revision;
            }
        }

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            [
                'knowledgeCategoryID' => $patchRow['knowledgeCategoryID'],
                'previousRevisionID' => $publishedRevision['articleRevisionID'],
                'body' => $patchRow['body']
            ]
        );

        $this->assertEquals(200, $r->getStatusCode());

        $newRow = $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}/edit");
        $this->assertSame($patchRow['body'], $newRow['body']);

        $this->expectException(ClientException::class);
        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            [
                'knowledgeCategoryID' => $patchRow['knowledgeCategoryID'],
                'previousRevisionID' => $publishedRevision['articleRevisionID'],
                'body' => $patchRow['body']
            ]
        );
    }

    /**
     * Test PATCH /articles/<id>/status.
     *
     * @param string $status
     * @dataProvider providePatchStatusData
     */
    public function testPatchStatus(string $status) {
        $row = $this->testGetEdit();

        $patchResponse = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}/status",
            ["status" => $status]
        );
        $this->assertEquals(200, $patchResponse->getStatusCode());
        $patchResponseBody = $patchResponse->getBody();
        $this->assertEquals($status, $patchResponseBody["status"]);

        $getResponse = $this->api()->get(
            "{$this->baseUrl}/{$row[$this->pk]}"
        );
        $getResponseBody = $getResponse->getBody();
        $this->assertEquals($status, $getResponseBody["status"]);
    }

    /**
     * Test PATCH /articles/<id> when knowledge base has status "deleted".
     */
    public function testPatchDeleted() {
        $article = $this->prepareDeletedKnowledgeBase();

        $this->expectException(NotFoundException::class);
        $this->api()->patch(
            "{$this->baseUrl}/{$article[$this->pk]}",
            ['name' => 'Patched test article']
        );
    }

    /**
     * Test PUT /articles/<id>/react when knowledge base has status "deleted".
     */
    public function testPutReactHelpfulDeleted() {
        $article = $this->prepareDeletedKnowledgeBase();

        $this->expectException(NotFoundException::class);
        $this->api()->put(
            "{$this->baseUrl}/{$article[$this->pk]}/react",
            ['helpful' => 'yes']
        );
    }

    /**
     * Test PUT /articles/<id>/react.
     */
    public function testPutReactHelpful() {
        $article = $this->testPost();

        $body = $this->api()->put(
            "{$this->baseUrl}/{$article[$this->pk]}/react",
            ['helpful' => 'yes']
        )->getBody();

        $this->assertEquals($article['name'], $body['name']);
        $this->assertEquals('helpful', $body['reactions'][0]['reactionType']);
        $this->assertEquals(1, $body['reactions'][0]['yes']);
        $this->assertEquals(0, $body['reactions'][0]['no']);
        $this->assertEquals(1, $body['reactions'][0]['total']);
    }

    /**
     * Test POST /articles when DiscussionID provided
     * to set discussion canonical link to the article created
     */
    public function testPostDiscussionCanonical() {
        $discussion = $this->api()->post(
            '/discussions',
            [
                'categoryID' => -1,
                'name' => 'test discussion',
                'body' => 'Hello world!',
                'format' => 'markdown'
            ]
        )->getBody();
        $record = $this->record();
        $record['discussionID'] = $discussion['discussionID'];
        $article = $this->api()->post($this->baseUrl, $record)->getBody();

        $discussionUpdated = $this->api()->get(
            '/discussions/' . $discussion['discussionID']
        )->getBody();
        $this->assertStringEndsWith($article['url'], $discussionUpdated['canonicalUrl']);
    }

    /**
     * Test PATCH /articles/<id>/status when knowledge base has status "deleted".
     */
    public function testPatchStatusDeleted() {
        $article = $this->prepareDeletedKnowledgeBase();

        $this->expectException(NotFoundException::class);
        $this->api()->patch(
            "{$this->baseUrl}/{$article[$this->pk]}/status",
            ['status' => 'deleted']
        );
    }

    /**
     * Prepare knowledge with status "deleted", root category and article
     *
     * @return array Record-array of "draft" or "article"
     */
    private function prepareDeletedKnowledgeBase() {
        $kb = $this->newKnowledgeBase();
        $record = $this->record();
        $record['knowledgeCategoryID'] = $kb['rootCategoryID'];
        $article = $this->testPost($record);

        $this->api()->patch(
            "/knowledge-bases/{$kb['knowledgeBaseID']}",
            ['status' => KnowledgeBaseModel::STATUS_DELETED]
        );

        return $article;
    }

    /**
     * Test GET /articles.
     */
    public function testIndex() {
        $helloWorldBody = json_encode([["insert" => "Hello World"]]);
        $knowledgeBase = $this->api()->post("knowledge-bases", [
            "name" => __FUNCTION__ . " KB #1",
            "description" => __FUNCTION__,
            "urlCode" => 'kb-1' . round(microtime(true) * 1000) . rand(1, 1000),
            "viewType" => KnowledgeBaseModel::TYPE_GUIDE,
            "sortArticles" => KnowledgeBaseModel::ORDER_MANUAL
        ])->getBody();
        // Setup the test categories.
        $primaryCategory = $this->api()->post("knowledge-categories", [
            "name" => __FUNCTION__ . " Primary",
            "parentID" => $knowledgeBase['rootCategoryID'],
        ])->getBody();
        $secondaryCategory = $this->api()->post("knowledge-categories", [
            "name" => __FUNCTION__ . " Secondary",
            "parentID" => $knowledgeBase['rootCategoryID'],
        ])->getBody();

        // Setup the test articles.
        for ($i = 1; $i <= 5; $i++) {
            $this->api()->post($this->baseUrl, [
                "knowledgeCategoryID" => $primaryCategory["knowledgeCategoryID"],
                "name" => "Primary Category Article",
                "body" => $helloWorldBody,
                "format" => "rich",
            ])->getBody();

            $this->api()->post($this->baseUrl, [
                "knowledgeCategoryID" => $secondaryCategory["knowledgeCategoryID"],
                "name" => "Secondary Category Article",
                "body" => $helloWorldBody,
                "format" => "rich",
            ])->getBody();
        }

        // Get the articles for the primary category.
        $articles = $this->api()->get(
            $this->baseUrl,
            ["knowledgeCategoryID" => $primaryCategory["knowledgeCategoryID"]]
        )->getBody();

        // Verify the result.
        $this->assertNotEmpty($articles);
        $success = true;
        foreach ($articles as $article) {
            if ($article["knowledgeCategoryID"] !== $primaryCategory["knowledgeCategoryID"]) {
                $success = false;
                break;
            }
        }
        $this->assertTrue($success, "Unable to limit index to articles in a specific category.");

        //check if articles (index) return 404 when knowledge base has status "deleted"
        $this->api()->patch(
            "/knowledge-bases/{$knowledgeBase['knowledgeBaseID']}",
            ['status' => KnowledgeBaseModel::STATUS_DELETED]
        );
        $this->expectException(NotFoundException::class);

        $r = $this->api()->get(
            $this->baseUrl,
            ["knowledgeCategoryID" => $primaryCategory["knowledgeCategoryID"]]
        );
    }


    /**
     * Test getting history of article revisions.
     */
    public function testGetRevisions() {
        $kb = $this->newKnowledgeBase();
        $record = $this->record();
        $record['knowledgeCategoryID'] = $kb['rootCategoryID'];
        $article = $this->testPost($record);
        $articleID = $article["articleID"];
        $originalResponse = $this->api()->get("{$this->baseUrl}/{$articleID}/revisions");
        $originalResponseBody = $originalResponse->getBody();

        // Baseline. We should only have one revision and it should be the current one.
        $this->assertEquals(200, $originalResponse->getStatusCode());
        $this->assertInternalType("array", $originalResponseBody);
        $this->assertCount(1, $originalResponseBody);
        $this->assertEquals(ArticleModel::STATUS_PUBLISHED, $originalResponseBody[0]["status"]);
        $this->assertEquals($article["name"], $originalResponseBody[0]["name"]);

        // Add five new revisions.
        for ($i = 1; $i <= 5; $i++) {
            $latest = ["name" => __FUNCTION__ . " {$i}"] + $article;
            $this->api()->patch("{$this->baseUrl}/{$articleID}", $latest);
        }

        // Ensure we now have six revisions.
        $response = $this->api()->get("{$this->baseUrl}/{$articleID}/revisions");
        $responseBody = $response->getBody();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInternalType("array", $responseBody);
        $this->assertCount(6, $responseBody);

        // Verify there is one, and only one, published revision and that it is the latest revision.
        $published = null;
        foreach ($responseBody as $revision) {
            if ($revision["status"] === ArticleModel::STATUS_PUBLISHED) {
                if ($published) {
                    $this->fail("Multiple published revisions detected for a single article.");
                }
                $published = $revision;
            }
        }
        $this->assertNotNull($published, "No published revisions detected for the article.");
        $this->assertEquals($latest["name"], $published["name"]);

        //check if articles/{articleID}/revisions return 404 when knowledge base has status "deleted"
        $this->api()->patch(
            '/knowledge-bases/' . $kb['knowledgeBaseID'],
            ['status' => KnowledgeBaseModel::STATUS_DELETED]
        );

        $this->expectException(NotFoundException::class);
        $this->api()->get("{$this->baseUrl}/{$articleID}/revisions");
    }

    /**
     * Test GET /articles/{ID}/translations
     */
    public function testGetArticleTranslations() {
        $record = $this->record();
        $article = $this->testPost($record);
        $articleID = $article["articleID"];

        $response = $this->api()->get("{$this->baseUrl}/{$articleID}/translations");
        $articleTranslations = $response->getBody();

        $this->assertCount(1, $articleTranslations);
        $this->assertEquals("out-of-date", $articleTranslations[0]["translationStatus"]);
        $this->assertEquals("en", $articleTranslations[0]["locale"]);
    }

    /**
     * Test posting article in a locale that is supported.
     *
     */
    public function testPostArticleInSupportedLocale() {
        $siteSectionProvider = new MockSiteSectionProvider();
        self::container()
            ->setInstance(SiteSectionProviderInterface::class, $siteSectionProvider);

        $kb = $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            ['siteSectionGroup' => 'subcommunities-group-1']
        );

        $record = $this->record();
        $record["locale"] = "ru";

        $response = $this->api()->post($this->baseUrl, $record);

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Test posting article in a locale that isn't supported.
     *
     * @expectedException Garden\Web\Exception\ClientException
     * @expectedExceptionMessage Locale xx not supported in this Knowledge-Base
     *
     */
    public function testPostArticleInNotSupportedLocale() {
        $siteSectionProvider = new MockSiteSectionProvider();
        self::container()
            ->setInstance(SiteSectionProviderInterface::class, $siteSectionProvider);

        $kb = $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            ['siteSectionGroup' => 'subcommunities-group-1']
        );

        $record = $this->record();
        $record["locale"] = "xx";

        $response = $this->api()->post($this->baseUrl, $record);
    }
}
