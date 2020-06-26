<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use VanillaTests\Fixtures\MockSiteSectionProvider;

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

    protected static $addons = ['vanilla', 'sphinx', 'knowledge'];

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass(): void {
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
        $this->api()->patch(
            '/knowledge-bases/' . self::$knowledgeCategoryID,
            ['siteSectionGroup' => 'mockSiteSectionGroup-1']
        );

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

        $this->assertTrue(true);
    }

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
     *
     * @param string $locale
     * @param int $count
     *
     * @depends testPrepareData
     * @dataProvider provideLocaleCounts
     */
    public function testGetRevisionsByLocale(string $locale, int $count) {


        $revisions = $this->api()
            ->get("articles/" . self::$articleID . "/revisions?locale=".$locale)
            ->getBody();

        $this->assertEquals($count, count($revisions));
    }

    /**
     * @return array Data with expected correct Count values
     */
    public function provideLocaleCounts(): array {
        return [
            'Test locale EN' => ['en', 1],
            'Test locale FR' => ['fr', 1],
            'Test locale RU' => ['ru', 1],
            'Test locale ES' => ['es', 0],
        ];
    }
}
