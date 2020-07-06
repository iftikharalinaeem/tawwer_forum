<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Knowledge\APIv2\Articles;

use Vanilla\Database\Operation;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleReactionModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ClientException;
use Vanilla\Models\ReactionModel;
use Vanilla\ReCaptchaVerification;
use VanillaTests\APIv2\AbstractResourceTest;
use VanillaTests\APIv2\TestCrawlTrait;
use VanillaTests\APIv2\TestSortingTrait;
use VanillaTests\Knowledge\Utils\KbApiTestTrait;

/**
 * Test the /api/v2/articles endpoint.
 */
class ArticlesTest extends AbstractResourceTest {
    use KbApiTestTrait, TestSortingTrait, TestCrawlTrait;

    /** @var int */
    private static $knowledgeBaseID;

    /** @var int */
    private static $knowledgeCategoryID;

    /** @var string The resource route. */
    protected $baseUrl = "/articles";

    /**
     * @var string
     */
    protected $resourceName = 'article';

    /** @var array Fields to be checked with get/<id>/edit */
    protected $editFields = [
        "body",
        "format",
        "knowledgeCategoryID",
        "locale",
        "name",
        "sort",
        'foreignID',
        'status',
        'featured',
    ];

    /** @var string The name of the primary key of the resource. */
    protected $pk = "articleID";

    /** @var string The singular name of the resource. */
    protected $singular = "article";

    private $defaultKB = [];

    /**
     * {@inheritDoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);
        $this->sortFields = ['sort', 'dateInserted', 'dateUpdated', 'score', 'articleID'];
    }

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
            "siteSectionGroup" => 'mockSiteSectionGroup-1',
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
     * @inheritDoc
     */
    protected function sortUrl(): string {
        return $this->baseUrl.'?articleID=1..';
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
        return [
            [ArticleModel::STATUS_DELETED], // ArticleModel::STATUS_DELETED
            [ArticleModel::STATUS_PUBLISHED], // ArticleModel::STATUS_PUBLISHED
            [ArticleModel::STATUS_UNDELETED], // ArticleModel::STATUS_UNDELETED
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
            "featured" => false,
            "status" => 'published'
        ];
        return $record;
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
        $kb = $this->createKnowledgeBase();
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

        $revisionChangeCount = 3;
        // Add five new revisions.
        for ($i = 1; $i <= $revisionChangeCount; $i++) {
            $latest = ["name" => __FUNCTION__ . " {$i}"] + $article;
            $this->api()->patch("{$this->baseUrl}/{$articleID}", $latest);
        }

        // Ensure we now have six revisions.
        $response = $this->api()->get("{$this->baseUrl}/{$articleID}/revisions");
        $responseBody = $response->getBody();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($responseBody);
        $this->assertCount($revisionChangeCount + 1, $responseBody);

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
     * Test PUT /articles/<id>/react as Guest
     */
    public function testPutReactHelpfulAsGuest() {
        $article = $this->testPost();

        $this->setPermissionsForGuest();
        $this->api()->setUserID(0);

        $body = $this->api()->put(
            "{$this->baseUrl}/{$article[$this->pk]}/react",
            ['helpful' => 'no']
        )->getBody();

        $this->assertEquals($article['name'], $body['name']);
        $this->assertEquals('helpful', $body['reactions'][0]['reactionType']);
        $this->assertEquals(0, $body['reactions'][0]['yes']);
        $this->assertEquals(1, $body['reactions'][0]['no']);
        $this->assertEquals(1, $body['reactions'][0]['total']);
    }

    /**
     * Test PUT  /articles/<id>/react with ReCaptchaV3 failing.
     */
    public function testPutReactHelpfulWithReCaptchaFail() {

        $article = $this->testPost();

        $this->setPermissionsForGuest();
        $this->api()->setUserID(0);

        $actualReCaptchaVerification = static::container()->get(ReCaptchaVerification::class);
        $mockReCaptchaVerification = $this->createMock(ReCaptchaVerification::class);
        $mockReCaptchaVerification->method('siteVerifyV3')
            ->willReturn(false);
        static::container()
            ->setInstance(ReCaptchaVerification::class, $mockReCaptchaVerification);


        $body = $this->api()->put(
            "{$this->baseUrl}/{$article[$this->pk]}/react",
            ['helpful' => 'yes']
        )->getBody();

        $this->assertEquals(1, $body['reactions'][0]['yes']);
        $this->assertEquals(0, $body['reactions'][0]['no']);
        $this->assertEquals(1, $body['reactions'][0]['total']);

        /** @var ArticleReactionModel $reactionModel */
        $reactionModel= static::container()->get(ArticleReactionModel::class);

        $reactionCount = $reactionModel->getReactionCount($article[$this->pk]);

        $this->assertEquals(0, $reactionCount['positiveCount']);
        $this->assertEquals(0, $reactionCount['neutralCount']);
        $this->assertEquals(0, $reactionCount['allCount']);

        // Restore back the actual format compat service.
        static::container()
            ->setInstance(ReCaptchaVerification::class, $actualReCaptchaVerification);
    }

    /**
     * Test PUT  /articles/<id>/react with ReCaptchaV3.
     */
    public function testPutReactHelpfulWithReCaptcha() {

        $article = $this->testPost();

        $this->setPermissionsForGuest();
        $this->api()->setUserID(0);

        /** @var ReCaptchaVerification $actualReCaptchaVerification */
        $actualReCaptchaVerification = static::container()->get(ReCaptchaVerification::class);
        $mockReCaptchaVerification = $this->createMock(ReCaptchaVerification::class);
        $mockReCaptchaVerification->method('siteVerifyV3')
            ->willReturn(true);
        static::container()
            ->setInstance(ReCaptchaVerification::class, $mockReCaptchaVerification);


        $body = $this->api()->put(
            "{$this->baseUrl}/{$article[$this->pk]}/react",
            ['helpful' => 'no']
        )->getBody();

        $this->assertEquals(0, $body['reactions'][0]['yes']);
        $this->assertEquals(1, $body['reactions'][0]['no']);
        $this->assertEquals(1, $body['reactions'][0]['total']);

        /** @var ArticleReactionModel $reactionModel */
        $reactionModel= static::container()->get(ArticleReactionModel::class);

        $reactionCount = $reactionModel->getReactionCount($article[$this->pk]);

        $this->assertEquals(0, $reactionCount['positiveCount']);
        $this->assertEquals(1, $reactionCount['neutralCount']);
        $this->assertEquals(1, $reactionCount['allCount']);

        // Restore back the actual format compat service.
        static::container()
            ->setInstance(ReCaptchaVerification::class, $actualReCaptchaVerification);
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

    /**
     * Set Permissions for Guest Role.
     */
    private function setPermissionsForGuest(): void {
        $this->api()->patch(
            '/roles/2',
            [
                'name' => 'Guest',
                'permissions' => [
                    [
                        'type' => 'global',
                        'permissions' => [
                            'kb.view' => true,
                            'articles.add' => false
                        ]
                    ]
                ]
            ]
        );
    }
}
