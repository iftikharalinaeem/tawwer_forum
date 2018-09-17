<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Knowledge\Controllers\Api\ArticlesApiController;

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
     * Test GET /articles.
     */
    public function testIndex() {
        if (!method_exists(ArticlesApiController::class, "index")) {
            $this->markTestSkipped("Getting a list of articles is not implemented.");
        } else {
            $this->fail("Missing test for retrieving a list of articles.");
        }
    }
}
