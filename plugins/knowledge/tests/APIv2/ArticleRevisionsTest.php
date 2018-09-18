<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Knowledge\Controllers\Api\ArticleRevisionsApiController;

/**
 * Test the /api/v2/article-revisions endpoint.
 */
class ArticleRevisionsTest extends AbstractResourceTest {

    /** @var string The resource route. */
    protected $baseUrl = "/article-revisions";

    /** @var array Fields to be checked with get/<id>/edit */
    protected $editFields = [];

    /** @var string The name of the primary key of the resource. */
    protected $pk = "articleRevisionID";

    /** @var string The singular name of the resource. */
    protected $singular = "articleRevision";

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass() {
        self::$addons = ["vanilla", "knowledge"];
        parent::setupBeforeClass();
    }

    /**
     * Grab values for inserting a new article revision.
     *
     * @return array
     */
    public function record() {
        $record = [
            "articleID" => 1,
            "name" => "Article Revision",
            "locale" => "en",
            "body" => "Hello world.",
            "format" => "markdown",
        ];
        return $record;
    }

    /**
     * Test DELETE /article-revisions/<id>.
     */
    public function testDelete() {
        if (!method_exists(ArticleRevisionsApiController::class, "delete")) {
            $this->markTestSkipped("Deleting an article revision is not implemented.");
        } else {
            $this->fail("Missing test for DELETE.");
        }
    }

    /**
     * Test GET /article-revisions/<id>/edit.
     *
     * @param array|null $record An article revision to use for comparison.
     */
    public function testGetEdit($record = null) {
        if (!method_exists(ArticleRevisionsApiController::class, "get_edit")) {
            $this->markTestSkipped("Getting editable fields of an article revision is not implemented.");
        } else {
            $this->fail("Missing test for GET (edit).");
        }
    }

    /**
     * The GET /article-revisions/<id>/edit endpoint should have the same fields as patch fields.
     */
    public function testGetEditFields() {
        if (!method_exists(ArticleRevisionsApiController::class, "get_edit")) {
            $this->markTestSkipped("Getting editable fields of an article revision is not implemented.");
        } else {
            $this->fail("Missing test for GET (edit).");
        }
    }

    /**
     * Test GET /article-revisions.
     */
    public function testIndex() {
        if (!method_exists(ArticleRevisionsApiController::class, "index")) {
            $this->markTestSkipped("Getting a list of article revisions is not implemented.");
        } else {
            $this->fail("Missing test for GET /article-revisions.");
        }
    }

    /**
     * Test PATCH /article-revisions/<id> with a full record overwrite.
     */
    public function testPatchFull() {
        if (!method_exists(ArticleRevisionsApiController::class, "patch")) {
            $this->markTestSkipped("Editing an article revision is not implemented.");
        } else {
            $this->fail("Missing test for PATCH (full).");
        }
    }

    /**
     * Test PATCH /article-revisions/<id> with a a single field update.
     *
     * @param string $field The name of the field to patch.
     */
    public function testPatchSparse($field = null) {
        if (!method_exists(ArticleRevisionsApiController::class, "patch")) {
            $this->markTestSkipped("Editing an article revision is not implemented.");
        } else {
            $this->fail("Missing test for PATCH (sparse).");
        }
    }
}
