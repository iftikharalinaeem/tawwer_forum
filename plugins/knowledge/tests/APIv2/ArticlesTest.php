<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;
use Vanilla\Knowledge\Models\ArticleModel;

/**
 * Test the /api/v2/articles endpoint.
 */
class ArticlesTest extends AbstractResourceTest {

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
    public function modifyRow(array $row): array {
        $row = $row + $this->record();

        $row["body"] = md5($row["body"]);
        $row["format"] = $row["body"] === "markdown" ? "text" : "markdown";
        $row["knowledgeCategoryID"]++;
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
            "knowledgeCategoryID" => 1,
            "locale" => "en",
            "name" => "Example Article",
            "sort" => 1,
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
     * Test GET /articles.
     */
    public function testIndex() {
        // Setup the test categories.
        $primaryCategory = $this->api()->post("knowledge-categories", [
            "name" => __FUNCTION__ . " Primary",
            "parentID" => -1,
            "isSection" => false,
        ])->getBody();
        $secondaryCategory = $this->api()->post("knowledge-categories", [
            "name" => __FUNCTION__ . " Secondary",
            "parentID" => -1,
            "isSection" => false,
        ])->getBody();

        // Setup the test articles.
        for ($i = 1; $i <= 5; $i++) {
            $this->api()->post($this->baseUrl, [
                "knowledgeCategoryID" => $primaryCategory["knowledgeCategoryID"],
                "name" => "Primary Category Article",
                "body" => "Hello world.",
                "format" => "markdown",
            ])->getBody();

            $this->api()->post($this->baseUrl, [
                "knowledgeCategoryID" => $secondaryCategory["knowledgeCategoryID"],
                "name" => "Secondary Category Article",
                "body" => "Hello world.",
                "format" => "markdown",
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
    }

    /**
     * Test getting history of article revisions.
     */
    public function testGetRevisions() {
        $article = $this->testPost();
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
    }
}
