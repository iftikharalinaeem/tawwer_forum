<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
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
    public static function setupBeforeClass(): void {
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
     * Tests the offset parameter on the article-revisions/reRender endpoint.
     */
    public function testReRenderOffSet() {
        $articles = [];

        // create 10 articles
        for ($i = 0; $i < 10; $i++) {
            $articles[] = $this->createArticle();
        }

        $options = ['offset' => 0];
        $response1 = $this->api()->patch(
            "article-revisions/re-render",
            $options
        );
        $response1 = $response1->getBody();

        $options = ['offset' => 5];
        $response2 = $this->api()->patch(
            "article-revisions/re-render",
            $options
        );
        $response2 = $response2->getBody();

        $difference1 = $response1['processed'] - $response2['processed'];
        $difference2 = $response2['firstArticleRevisionID'] - $response1['firstArticleRevisionID'];

        $this->assertEquals(5, $difference1);
        $this->assertEquals(5, $difference2);
    }

    /**
     * Tests the limit parameter on the article-revisions/reRender endpoint.
     */
    public function testReRenderLimitPass() {
        $articles = [];

        // create 10 articles
        for ($i = 0; $i < 10; $i++) {
            $articles[] = $this->createArticle();
        }

        $options = ['limit' => 10];
        $response1 = $this->api()->patch(
            "article-revisions/re-render",
            $options
        );
        $response1 = $response1->getBody();

        $options = ['limit' => 5];
        $response2 = $this->api()->patch(
            "article-revisions/re-render",
            $options
        );
        $response2 = $response2->getBody();

        $difference = $response1['processed'] - $response2['processed'];

        $this->assertEquals(5, $difference);
    }

    /**
     * Tests that an exception is thrown if the limit parameter is higher than the maximum
     * on the article-revisions/reRender endpoint.
     */
    public function testReRenderLimitFail() {
        $this->expectException(ClientException::class);
        $this->expectExceptionCode(422);
        $this->expectExceptionMessage('limit is greater than 1000');

        $options = ['limit' => 1001];
        $this->api()->patch(
            "article-revisions/re-render",
            $options
        );
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

        $article = $response->getBody();
       
        return $article;
    }
}
