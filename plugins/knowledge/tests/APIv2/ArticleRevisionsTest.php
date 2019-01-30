<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;

/**
 * Test the /api/v2/article-revisions endpoint.
 */
class ArticleRevisionsTest extends AbstractAPIv2Test {

    /** @var int */
    private static $articleID;

    /** @var int */
    private static $knowledgeBaseID;

    /** @var int */
    private static $knowledgeCategoryID;

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

        /** @var ArticleModel $articleModel */
        $articleModel = self::container()->get(ArticleModel::class);
        self::$articleID = $articleModel->insert([
            "knowledgeCategoryID" => self::$knowledgeCategoryID,
            "sort" => 1,
        ]);
    }

    /**
     * Test if a revision posted to the articles resource can be retrieved from the article-revisions endpoint.
     */
    public function testGetRevision() {
        $row = [
            "body" => __FUNCTION__,
            "format" => "text",
            "locale" => "en",
            "knowledgeCategoryID" => 1,
            "name" => uniqid(__FUNCTION__, true),
        ];

        // Create the revision.
        $this->api()->patch(
            "articles/" . self::$articleID,
            $row
        );

        // Use its unique name to pull the ID from the list of revisions.
        $revisionID = null;
        $revisions = $this->api()->get("articles/" . self::$articleID . "/revisions");

        foreach ($revisions->getBody() as $currentRevision) {
            if ($currentRevision["name"] === $row["name"]) {
                $revisionID = $currentRevision["articleRevisionID"];
                break;
            }
        }
        $this->assertNotNull($revisionID, "Unable to locate revision.");

        $response = $this->api()->get("article-revisions/{$revisionID}");
        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();
        unset($row['knowledgeCategoryID']);
        $this->assertRowsEqual($row, $body);
    }
}
