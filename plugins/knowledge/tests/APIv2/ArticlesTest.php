<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Database\Operation;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Garden\Web\Exception\ClientException;
use Vanilla\Models\ReactionModel;

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

    /** @var SiteSectionProviderInterface*/
    private static $siteSectionProvider;

    /** @var array Fields to be checked with get/<id>/edit */
    protected $editFields = [
        "body",
        "format",
        "knowledgeCategoryID",
        "locale",
        "name",
        "sort",
        'foreignID',
        'status'
    ];

    /** @var string The name of the primary key of the resource. */
    protected $pk = "articleID";

    /** @var string The singular name of the resource. */
    protected $singular = "article";

    private $defaultKB = [];

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass(): void {
        self::$addons = ["vanilla", "sphinx", "knowledge"];
        parent::setupBeforeClass();

        /** @var KnowledgeBaseModel $knowledgeBaseModel */
        $knowledgeBaseModel = self::container()->get(KnowledgeBaseModel::class);
        self::$knowledgeBaseID = $knowledgeBaseModel->insert([
            "name" => __CLASS__,
            "description" => "Basic knowledge base for testing.",
            "urlCode" => strtolower(substr(strrchr(__CLASS__, "\\"), 1)),
            "sourceLocale" => "en",
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
            "foreignID" => 'test-id-001',
            "status" => 'published'
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
            "siteSectionGroup" => "mockSiteSectionGroup-1",
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
                "sortArticles" => KnowledgeBaseModel::ORDER_MANUAL,
                "sourceLocale" => "en",
                "format" => "markdown",
                "siteSectionGroup" => "mockSiteSectionGroup-1",
            ])->getBody();
        }

        $row = $this->testGetEdit();

        $patchRow = $this->modifyRow($row);

        // Patching an article's locale won't work unless there is an existing revision for that locale.
        $locale = null;
        if ($field === "locale") {
            $this->api()->patch(
                '/knowledge-bases/' . self::$knowledgeCategoryID,
                ['siteSectionGroup' => 'mockSiteSectionGroup-1']
            );
            $locale = "fr";
            $this->createFirstRevisionInLocale($row, $locale);
        }

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            [
                'knowledgeCategoryID' => $patchRow['knowledgeCategoryID'],
                $field => $patchRow[$field]
            ]
        );

        $this->assertEquals(200, $r->getStatusCode());

        $newRow = $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}/edit", ["locale" => $locale]);
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
                "sortArticles" => KnowledgeBaseModel::ORDER_MANUAL,
                'siteSectionGroup' => 'mockSiteSectionGroup-1'
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
     * @inheritdoc
     */
    public function testGetEdit($record = null) {
        if ($record === null) {
            $record = $this->record();
            $row = $this->testPost($record);
        } else {
            $row = $record;
        }

        $r = $this->api()->get(
            "{$this->baseUrl}/{$row[$this->pk]}/edit"
        );

        $this->assertEquals(200, $r->getStatusCode());
        $expected = arrayTranslate($record, $this->editFields);
        $actual = $record = $r->getBody();
        unset($expected['dateUpdated']);
        unset($actual['dateUpdated']);
        $this->assertRowsEqual($expected, $actual);
        $this->assertCamelCase($record);

        return $record;
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
     * Test PUT /articles/<id>/featured.
     */
    public function testPutFeatured() {
        $article = $this->testPost();
        $this->assertEmpty($article['dateFeatured']);

        $body = $this->api()->put(
            "{$this->baseUrl}/{$article[$this->pk]}/featured",
            ['featured' => true]
        )->getBody();

        $this->assertTrue($body['featured']);
        $this->assertNotEmpty($body['dateFeatured']);

        sleep(1);
        $unFeatured = $this->api()->put(
            "{$this->baseUrl}/{$article[$this->pk]}/featured",
            ['featured' => false]
        )->getBody();

        $this->assertFalse($unFeatured['featured']);
        $this->assertGreaterThan($body['dateFeatured'], $unFeatured['dateFeatured']);
    }

    /**
     * Test POST /articles when DiscussionID provided
     * to set discussion canonical link to the article created
     */
    public function testPostDiscussionCanonical() {
        $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            ['siteSectionGroup' => 'vanilla']
        );
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
        $this->assertIsArray($originalResponseBody);
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
        $this->assertIsArray($responseBody);
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
        $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            ['siteSectionGroup' => 'mockSiteSectionGroup-1']
        );

        $record = $this->record();
        $article = $this->testPost($record);
        $articleID = $article["articleID"];

        $response = $this->api()->get("{$this->baseUrl}/{$articleID}/translations");
        $articleTranslations = $response->getBody();

        $this->assertCount(4, $articleTranslations);
        $this->assertEquals("up-to-date", $articleTranslations[0]["translationStatus"]);
        $this->assertEquals("en", $articleTranslations[0]["locale"]);
    }

    /**
     * Test posting article in a locale that is supported.
     *
     */
    public function testPostArticleInSupportedLocale() {
        /** @var KnowledgeBaseModel $knowledgeBaseModel */
        $knowledgeBaseModel = self::container()->get(KnowledgeBaseModel::class);
        $kb = $knowledgeBaseModel->get(["knowledgeBaseID" => self::$knowledgeBaseID]);
        $kb = reset($kb);

        $record = $this->record();
        $record["locale"] = "en";

        $response = $this->api()->post($this->baseUrl, $record);
        $article = $response->getBody();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($kb["sourceLocale"], $article["locale"]);
    }

    /**
     * Test posting article in a locale that isn't supported.
     */
    public function testPostArticleInNotSupportedLocale() {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Articles must be created in en locale.');

        /** @var KnowledgeBaseModel $knowledgeBaseModel */
        $knowledgeBaseModel = self::container()->get(KnowledgeBaseModel::class);
        $kb = $knowledgeBaseModel->get(["knowledgeBaseID" => self::$knowledgeBaseID]);
        $kb = reset($kb);
        $this->assertEquals("en", $kb["sourceLocale"]);

        $record = $this->record();
        $record["locale"] = "ru";

        $this->api()->post($this->baseUrl, $record);
    }

    /**
     * Test posting article in a locale that is supported.
     */
    public function testPatchArticleInSupportedLocale() {
        $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            ['siteSectionGroup' => 'mockSiteSectionGroup-1']
        );

        $record = $this->record();
        $record["locale"] = "en";

        $response = $this->api()->post($this->baseUrl, $record);
        $article = $response->getBody();

        $record = [
            "body" => "Translated article body",
            "format" => "markdown",
            "knowledgeCategoryID" => self::$knowledgeCategoryID,
            "locale" => "ru",
            "name" => "Translated Example Article",
            "sort" => 1,
        ];

        $response = $this->api()->patch($this->baseUrl."/".$article["articleID"], $record);
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->api()->get($this->baseUrl."/".$article["articleID"]."/revisions");
        $revisions =  $response->getBody();
        $locales = array_column($revisions, "locale");
        $status = array_column($revisions, "status");

        $this->assertEquals(2, count($revisions));
        $this->assertContains("en", $locales);
        $this->assertContains("ru", $locales);
        $this->assertEquals(["published","published"], $status);
    }

    /**
     * Test posting article in a locale that is supported.
     *
     */
    public function testPatchArticleInNotSupportedLocale() {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Locale xx not supported in this Knowledge-Base");

        $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            ['siteSectionGroup' => 'mockSiteSectionGroup-1']
        );

        $record = $this->record();
        $record["locale"] = "en";

        $response = $this->api()->post($this->baseUrl, $record);
        $article = $response->getBody();

        $record = [
            "body" => "Translated article body",
            "format" => "markdown",
            "knowledgeCategoryID" => self::$knowledgeCategoryID,
            "locale" => "xx",
            "name" => "Translated Example Article",
            "sort" => 1,
        ];
        $this->api()->patch($this->baseUrl."/".$article["articleID"], $record);
    }

    /**
     * Test GET /articles when filtering with locale without fallback articles.
     */
    public function testGetArticlesFilterByLocaleOnlyTranslated() {
        $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            ['siteSectionGroup' => 'mockSiteSectionGroup-1']
        );

        $this->createMultipleArticles();
        $response = $this->api()->get(
            $this->baseUrl,
            [
                "knowledgeCategoryID" => self::$knowledgeCategoryID,
                "locale" => "fr",
                "only-translated" => true,
            ]
        );
        $articles = $response->getBody();
        $this->assertEquals(6, count($articles));

        $response = $this->api()->get($this->baseUrl, ["knowledgeCategoryID" => self::$knowledgeCategoryID, "locale" => "en"]);
        $articles = $response->getBody();
        $this->assertEquals(23, count($articles));
    }

    /**
     * Test GET /articles when filtering locale with fallback articles.
     */
    public function testGetArticlesFilterByLocale() {
        $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            ['siteSectionGroup' => 'mockSiteSectionGroup-1']
        );

        $this->createArticleWithRevisions(["ru"]);

        $response = $this->api()->get(
            $this->baseUrl,
            [
                "knowledgeCategoryID" => self::$knowledgeCategoryID,
                "locale" => "ru"
            ]
        );
        $article = $response->getBody();
        $locales = array_count_values(array_column($article, "locale"));

        $this->assertEquals(24, count($article));
        $this->assertEquals(2, $locales["ru"]);
        $this->assertEquals(22, $locales["en"]);
    }

    /**
     * @depends testGetArticlesFilterByLocale
     *
     * Test GET /articles when filtering with locale providing only translated articles.
     */
    public function testGetArticleFilterByLocaleOnlyTranslated() {
        $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            ['siteSectionGroup' => 'mockSiteSectionGroup-1']
        );

        $response = $this->api()->get(
            $this->baseUrl,
            [
                "knowledgeCategoryID" => self::$knowledgeCategoryID,
                "locale" => "ru",
                "only-translated" => true,
            ]
        );

        $article = $response->getBody();
        $this->assertEquals(2, count($article));
    }
    /**
     * Test Get /articles/{ID} filtered by locale providing fallback article.
     */
    public function testGetArticleFilterByIDAndLocaleFallback() {
        $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            [
                'siteSectionGroup' => 'mockSiteSectionGroup-1'
            ]
        );

        $articleID = $this->createArticleWithRevisions(["es"]);

        $response = $this->api()->get(
            $this->baseUrl.'/'.$articleID,
            [
                "knowledgeCategoryID" => self::$knowledgeCategoryID,
                "locale" => "fr",
                "only-translated" => false,
            ]
        );

        $article = $response->getBody();
        $this->assertEquals("en", $article["locale"]);
        $this->assertEquals($articleID, $article["articleID"]);
    }

    /**
     * Test Get /articles/{ID} filtered by locale providing translated article.
     */
    public function testGetArticleFilterByIDAndLocaleTranslated() {
        $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            [
                'siteSectionGroup' => 'mockSiteSectionGroup-1'
            ]
        );

        $articleID = $this->createArticleWithRevisions(["es"]);

        $response = $this->api()->get(
            $this->baseUrl.'/'.$articleID,
            [
                "knowledgeCategoryID" => self::$knowledgeCategoryID,
                "locale" => "es",
                "only-translated" => true,
            ]
        );

        $article = $response->getBody();
        $this->assertEquals("es", $article["locale"]);
        $this->assertEquals($articleID, $article["articleID"]);
    }

    /**
     * Test Get /articles/{ID} filtered by locale with no translated article found.
     */
    public function testGetArticleFilterByIDAndLocaleNoTranslation() {
        $this->expectException(ClientException::class);
        $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            [
                'siteSectionGroup' => 'mockSiteSectionGroup-1'
            ]
        );

        $articleID = $this->createArticleWithRevisions(["es"]);

        $response = $this->api()->get(
            $this->baseUrl.'/'.$articleID,
            [
                "knowledgeCategoryID" => self::$knowledgeCategoryID,
                "locale" => "fr",
                "only-translated" => true,
            ]
        );
    }

    /**
     * Test translations-statuses are set correctly from POST & PATCH /articles.
     */
    public function testTranslationsStatuses() {
        $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            ['siteSectionGroup' => 'mockSiteSectionGroup-1']
        );

        $articleID = $this->createArticleWithRevisions(["es","ru"]);
        $response = $this->api()->get($this->baseUrl."/".$articleID."/revisions");
        $revisions = $response->getBody();
        $translationsStatuses = array_column($revisions, "translationStatus");
        $translationsStatuses = array_unique($translationsStatuses);
        
        $this->assertEquals(3, count($revisions));
        $this->assertEquals(1, count($translationsStatuses));
        $this->assertEquals("up-to-date", $translationsStatuses[0]);
    }

    /**
     * Test PUT /articles/{ID}/invalidate-translations.
     */
    public function testInvalidatingTranslations() {
        $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            ['siteSectionGroup' => 'mockSiteSectionGroup-1']
        );

        $articleID = $this->createArticleWithRevisions(["es","ru"]);
        $response = $this->api()->put($this->baseUrl."/".$articleID."/invalidate-translations");

        $revisions = $response->getBody();
        $translationStatuses = array_column($revisions, "translationStatus", "locale");
        
        $this->assertEquals("up-to-date", $translationStatuses["en"]);
        $this->assertEquals("out-of-date", $translationStatuses["es"]);
        $this->assertEquals("out-of-date", $translationStatuses["ru"]);
    }

    /**
     * Create multiple articles with some a revision in a different locale.
     */
    private function createMultipleArticles() {
        for ($i = 1; $i <= 5; $i++) {
            $record = $this->record();
            $record["locale"] = "en";
            $response = $this->api()->post($this->baseUrl, $record);
            $article = $response->getBody();
            $record["locale"] = "fr";
            $this->api()->patch($this->baseUrl . "/" . $article["articleID"], $record);
        }
    }

    /**
     * Create a revision in a locale.
     *
     * @param array $row
     * @param string $locale
     */
    private function createFirstRevisionInLocale(array $row, string $locale) {
        $record = $this->record();
        $record["locale"] = $locale;
        $this->api()->patch(
            "{$this->baseUrl}/{$row["articleID"]}",
            $record
        );
    }

    /**
     * Create an article with a revision.
     *
     * @param array $locales
     * @return int
     */
    private function createArticleWithRevisions(array $locales = ["fr"]): int {
        $record = $this->record();
        $record["locale"] = "en";
        $response = $this->api()->post($this->baseUrl, $record);
        $article = $response->getBody();

        foreach ($locales as $locale) {
            $record["locale"] = $locale;
            $this->api()->patch($this->baseUrl . "/" . $article["articleID"], $record);
        }

        return  $article["articleID"];
    }

    /**
     * @inheritdoc
     */
    public function testPatchFull() {
        $row = $this->testGetEdit();
        $newRow = $this->modifyRow($row);

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}",
            $newRow
        );

        $this->assertEquals(200, $r->getStatusCode());
        $expected = $newRow;
        $actual = $record = $r->getBody();
        unset($expected['dateUpdated']);
        unset($actual['dateUpdated']);
        $this->assertRowsEqual($expected, $actual);

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function testGetEditFields() {
        $row = $this->testGetEdit();

        unset($row[$this->pk]);
        unset($row['dateUpdated']);
        $rowFields = array_keys($row);
        sort($rowFields);

        $patchFields = $this->patchFields;
        sort($patchFields);
        unset($patchFields['dateUpdated']);
        $this->assertEquals($patchFields, $rowFields);
    }

    /**
     * Test /articles/{id}/react with articles.manage
     */
    public function testPutReactHelpfulWithForeignIDPass() {
        $user = $this->setupUserWithKBPermissions(Operation::MODE_IMPORT);
        $this->api()->setUserID($user['userID']);

        $article = $article = $this->testPost();
        $body = $this->api()->put(
            "articles/{$article['articleID']}/react",
            [
                'helpful' => 'yes',
                'insertUserID' => $user['userID'],
                'foreignID' => 'vanilla-test'
            ]
        )->getBody();

        /** @var ReactionModel $reactionModel */
        $reactionModel = self::container()->get(ReactionModel::class);
        $reactionsWithForeignID = $reactionModel->get(['foreignID' => 'vanilla-test']);

        $this->assertEquals(1, count($reactionsWithForeignID));

        $this->assertEquals($article['name'], $body['name']);
        $this->assertEquals('helpful', $body['reactions'][0]['reactionType']);
        $this->assertEquals(1, $body['reactions'][0]['yes']);
        $this->assertEquals(1, $body['reactions'][0]['total']);
    }

    /**
     * Test /articles/{id}/react without articles.manage
     */
    public function testPutReactHelpfulWithForeignIDFailed() {
        $user = $this->setupUserWithKBPermissions('contentViewer');
        $this->api()->setUserID($user['userID']);

        $article = $article = $this->testPost();
        $body = $this->api()->put(
            "articles/{$article['articleID']}/react",
            [
                'helpful' => 'yes',
                'insertUserID' => $user['userID'],
                'foreignID' => 'vanilla-test-2'
            ]
        )->getBody();

        /** @var ReactionModel $reactionModel */
        $reactionModel = self::container()->get(ReactionModel::class);
        $reactionsWithForeignID = $reactionModel->get(['foreignID' => 'vanilla-test-2']);

        $this->assertEquals(0, count($reactionsWithForeignID));

        $this->assertEquals($article['name'], $body['name']);
        $this->assertEquals('helpful', $body['reactions'][0]['reactionType']);
        $this->assertEquals(1, $body['reactions'][0]['yes']);
        $this->assertEquals(1, $body['reactions'][0]['total']);
    }

    /**
     * Setup user with kb permissions.
     *
     * @param string $roleType
     * @return array
     */
    protected function setupUserWithKBPermissions(string $roleType): array {
        $newRole = [
            'name' => $roleType,
            'permissions' => [
                'kb.view' => true,
                'articles.add' =>  true,
                'articles.manage' => ($roleType === Operation::MODE_IMPORT) ? true : false
            ],
        ];
        $role = $this->createUserRole($newRole);
        $user = $this->api()->post(
            '/users',
            [
                'name' => $role['name'] . 'user',
                'email' => $role['name'].'@example.com',
                'emailConfirmed' => true,
                'password' => 'vanilla',
                'roleID' => [$role['roleID']]
            ]
        )->getBody();

        return $user;
    }

    /**
     * Create a new role.
     *
     * @param array $newRole
     * @return array
     */
    protected function createUserRole(array $newRole): array {
        $role = $this->api()->post(
            '/roles',
            [
                'name' => $newRole['name'],
                'description' => 'Role '.$newRole['name'],
                'type' => 'member',
                'deletable' => true,
                'canSession' => true,
                'personalInfo' => false,
                'permissions' => [
                    [
                        'type' => 'global',
                        'permissions' => $newRole['permissions']
                    ],
                    [
                        'type' => 'global',
                        'permissions' => [
                            'signIn.allow' => true,
                            ]
                    ]
                ]
            ]
        )->getBody();

        return $role;
    }
}
