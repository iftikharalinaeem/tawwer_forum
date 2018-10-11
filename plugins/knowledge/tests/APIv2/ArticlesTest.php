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
    protected $editFields = ["knowledgeCategoryID", "sort"];

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
    public function modifyRow(array $row) {
        $row["knowledgeCategoryID"] = $row["knowledgeCategoryID"] ?? 0;
        $row["sort"] = $row["sort"] ?? 0;

        $row["knowledgeCategoryID"]++;
        $row["sort"]++;
        return $row;
    }

    /**
     * Grab values for inserting a new article.
     *
     * @return array
     */
    public function record() {
        $record = [
            "knowledgeCategoryID" => 1,
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
     * Test PATCH /articles/<id>/delete.
     */
    public function testPatchDelete() {
        $row = $this->testGetEdit();

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}/delete"
        );
        $this->assertEquals(200, $r->getStatusCode());
        $this->assertEquals('', $r->getBody());

        $r = $this->api()->get(
            "{$this->baseUrl}/{$row[$this->pk]}"
        );
        $body = $r->getBody();
        $this->assertEquals(ArticleModel::STATUS_DELETED, $body['status'], 'Status deleted has not been updated!');
    }

    /**
     * Test PATCH /articles/<id>/undelete.
     */
    public function testPatchUndelete() {
        $row = $this->testGetEdit();

        $r = $this->api()->patch(
            "{$this->baseUrl}/{$row[$this->pk]}/undelete"
        );
        $this->assertEquals(200, $r->getStatusCode());
        $this->assertEquals('', $r->getBody());

        $r = $this->api()->get(
            "{$this->baseUrl}/{$row[$this->pk]}"
        );
        $body = $r->getBody();
        $this->assertEquals(ArticleModel::STATUS_UNDELETED, $body['status'], 'Status undeleted has not been updated!');
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
            $primaryArticle = $this->api()->post($this->baseUrl, [
                "knowledgeCategoryID" => $primaryCategory["knowledgeCategoryID"]
            ])->getBody();
            $this->api()->post("article-revisions", [
                "articleID" => $primaryArticle["articleID"],
                "name" => "Primary Category Article",
                "body" => "Hello world.",
                "format" => "markdown",
            ])->getBody();

            $secondaryArticle = $this->api()->post($this->baseUrl, [
                "knowledgeCategoryID" => $secondaryCategory["knowledgeCategoryID"]
            ])->getBody();
            $this->api()->post("article-revisions", [
                "articleID" => $secondaryArticle["articleID"],
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
}
