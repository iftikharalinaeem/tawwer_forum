<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;

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

    /**
     * Tests if that the article-revisions/render endpoint renders a selected revision.
     */
    public function testReRender() {

        $bodyText = json_encode([["insert" => "Rich Article"]]);
        // Create an article.
        $row = [
            "body" => $bodyText,
            "format" => "rich",
            "locale" => "en",
            "knowledgeCategoryID" => 1,
            "name" => uniqid(__FUNCTION__, true),
        ];

        // Create the revision.
        $response = $this->api()->post(
            "/articles",
            $row
        );

        $this->assertEquals(201, $response->getStatusCode());

        $article = $response->getBody();
        $where = ["articleID" => $article["articleID"]];

        $articleRevisionModel = self::container()->get(ArticleRevisionModel::class);

        $revisionBefore = $articleRevisionModel->get($where);

        // set the rendered content to null, to see if the rerender works.
        $updateSuccess = $articleRevisionModel->update(["bodyRendered" => "", "plainText" => "", "excerpt" => ""], $where);
        $this->assertTrue($updateSuccess);

        $reRenderResponse = $this->api()->patch(
            "article-revisions/re-render"
        );

        $this->assertEquals(200, $reRenderResponse->getStatusCode());

        $revisionAfter = $articleRevisionModel->get($where);

        $this->assertEquals($revisionBefore[0]["bodyRendered"], $revisionAfter[0]["bodyRendered"]);
        $this->assertEquals($revisionBefore[0]["plainText"], $revisionAfter[0]["plainText"]);
        $this->assertEquals($revisionBefore[0]["excerpt"], $revisionAfter[0]["excerpt"]);
    }
}
