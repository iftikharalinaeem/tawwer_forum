<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Knowledge\APIv2\Articles;

use Garden\Web\Exception\ClientException;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use VanillaTests\Knowledge\Utils\KbApiTestCase;

/**
 * Test the /api/v2/article-revisions endpoint.
 */
class ArticleRevisionsTest extends KbApiTestCase {

    /**
     * Test if a revision posted to the articles resource can be retrieved from the article-revisions endpoint.
     */
    public function testGetRevision() {
        $this->createKnowledgeBase();
        $initialArticle = $this->createArticle();
        $articleID = $initialArticle['articleID'];
        $row = [
            "body" => __FUNCTION__,
            "format" => "text",
            "locale" => "en",
            "knowledgeCategoryID" => 1,
            "name" => uniqid(__FUNCTION__, true),
        ];

        // Create the revision.
        $this->api()->patch(
            "articles/" . $articleID,
            $row
        );

        // Use its unique name to pull the ID from the list of revisions.
        $revisionID = null;
        $revisions = $this->api()->get("articles/" . $articleID . "/revisions");

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
        $this->createKnowledgeBase();
        $article = $this->createArticle([
            'format' => RichFormat::FORMAT_KEY,
            'body' => '[{ "insert": "initial\n" }]'
        ]);

        $where = ["articleID" => $article["articleID"]];

        /** @var ArticleRevisionModel $articleRevisionModel */
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

        $this->createKnowledgeBase();

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

        $this->createKnowledgeBase();

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
}
