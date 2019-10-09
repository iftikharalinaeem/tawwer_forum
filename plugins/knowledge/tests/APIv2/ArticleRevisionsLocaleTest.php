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
 * Test the /api/v2/articles/{id}/revisions endpoint.
 */
class ArticleRevisionsLocaleTest extends AbstractAPIv2Test {

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
    }

    /**
     * Creates an article and revisions for testing.
     */
    public function testPrepareData() {
        $bodyText = json_encode([["insert" => uniqid("Rich Article")]]);

        $row = [
            "body" => $bodyText,
            "format" => "rich",
            "locale" => "en",
            "knowledgeCategoryID" => self::$knowledgeCategoryID,
            "name" => uniqid('EN ', true),
        ];

        // Create the revision with locale EN.
        $response = $this->api()->post(
            "/articles",
            $row
        );

        $article = $response->getBody();
        self::$articleID = $article['articleID'];

        // Create the revision with locale FR.
        $this->api()->patch(
            "articles/" . self::$articleID,
            [
                "body" => __FUNCTION__,
                "format" => "text",
                "locale" => "fr",
                "knowledgeCategoryID" => self::$knowledgeCategoryID,
                "name" => uniqid('FR ', true),
            ]
        );

        // Create the revision with locale RU
        $this->api()->patch(
            "articles/" . self::$articleID,
            [
                "body" => __FUNCTION__,
                "format" => "text",
                "locale" => "ru",
                "knowledgeCategoryID" => self::$knowledgeCategoryID,
                "name" => uniqid('RU ', true),
            ]
        );

        $this->assertTrue(true)
;    }

    /**
     * Test article/revisions resource .
     * @depends testPrepareData
     */
    public function testGetArticleRevisions() {

        // Use its unique name to pull the ID from the list of revisions.
        $revisionID = null;
        $revisions = $this->api()->get("articles/" . self::$articleID . "/revisions")->getBody();

        $this->assertEquals(3, count($revisions));
    }

    /**
     * Test  articles/revisions resource can be filtered by locales.
     * @depends testPrepareData
     */
    public function testGetRevisionsByLocale() {

        $revisions = $this->api()
            ->get("articles/" . self::$articleID . "/revisions?locale=en")
            ->getBody();

        $this->assertEquals(1, count($revisions));

        $revisions = $this->api()
            ->get("articles/" . self::$articleID . "/revisions?locale=fr")
            ->getBody();

        $this->assertEquals(1, count($revisions));

        $revisions = $this->api()
            ->get("articles/" . self::$articleID . "/revisions?locale=ru")
            ->getBody();

        $this->assertEquals(1, count($revisions));

        $revisions = $this->api()
            ->get("articles/" . self::$articleID . "/revisions?locale=es")
            ->getBody();

        $this->assertEquals(0, count($revisions));
    }
}
