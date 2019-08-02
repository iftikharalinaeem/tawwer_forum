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
     * Tests if that the article-revisions/reRender endpoint renders a selected revision.
     */
    public function testReRender() {
        $article = $this->createArticle();

        $where = ["articleID" => $article["articleID"]];

        $articleRevisionModel = self::container()->get(ArticleRevisionModel::class);
        
        // Change body text and set the rendered content to null, to see if the rerender works.
        $newBody = json_encode([["insert" => "Some brand new text"]]);

        $updateSuccess = $articleRevisionModel->update(["body" => $newBody], $where);
        $this->assertTrue($updateSuccess);

        $updateSuccess = $articleRevisionModel->update(["bodyRendered" => "", "plainText" => "", "excerpt" => ""], $where);
        $this->assertTrue($updateSuccess);

        $reRenderResponse = $this->api()->patch(
            "article-revisions/re-render",
            $where
        );

        $this->assertEquals(200, $reRenderResponse->getStatusCode());

        $reRenderContent = $articleRevisionModel->selectSingle($where);

        $this->assertEquals("<p>Some brand new text</p>", $reRenderContent["bodyRendered"]);
        $this->assertEquals("Some brand new text", $reRenderContent["plainText"]);
        $this->assertEquals("Some brand new text", $reRenderContent["excerpt"]);
    }

    /**
     * Tests if the limit option on the article-revisions/reRender endpoint.
     *
     * @expectedException Garden\Web\Exception\ClientException
     * @expectedExceptionCode 422
     * @expectedExceptionMessage limit is greater than 1000.
     */
    public function testReRenderLimit() {
        $options = ['limit' => 1001];
        $this->api()->patch(
            "article-revisions/re-render",
            $options
        );
    }

    /**
     * Tests if the limit option on the article-revisions/reRender endpoint.
     */
    public function testReRenderOffSet() {
        $articleRevisionModel = self::container()->get(ArticleRevisionModel::class);
        $articles = [];

        // create 10 articles
        for ($i = 0; $i < 10; $i++) {
            $articles[] = $this->createArticle();
        }

        $allRevisions = $articleRevisionModel->get();
        $numberOfArticles = count($allRevisions);

        // There are 12 articles, 2 from existing from previous tests.
        $this->assertEquals(12, $numberOfArticles);

        $options = ['offset' => 5];

        $response = $this->api()->patch(
            "article-revisions/re-render",
            $options
        );

        $response = $response->getBody();

        $this->assertEquals(7, $response['processed']);
        $this->assertEquals(6, $response['firstArticleRevisionID']);
        $this->assertEquals(12, $response['lastArticleRevisionID']);
    }

    /**
     * Creates an article for testing.
     *
     * @return array
     */
    protected function createArticle(): array {
        $bodyText = json_encode([["insert" => uniqid("Rich Article")]]);

        $row = [
            "body" => $bodyText,
            "format" => "rich",
            "locale" => "en",
            "knowledgeCategoryID" => 1,
            "name" => uniqid(__FUNCTION__, true),
        ];

        $response = $this->api()->post(
            "/articles",
            $row
        );

        $this->assertEquals(201, $response->getStatusCode());

        $article = $response->getBody();

        return $article;
    }
}
