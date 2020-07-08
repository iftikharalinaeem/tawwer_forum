<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Knowledge\APIv2\Articles;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Web\Pagination\FlatApiPaginationIterator;
use VanillaTests\Knowledge\Utils\KbApiTestCase;

/**
 * Test deleted articles from the articles API.
 */
class ArticleLocaleTest extends KbApiTestCase {

    /** @var int */
    private static $knowledgeBaseID;

    /** @var int */
    private static $knowledgeCategoryID;

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass(): void {
        self::$addons = ["vanilla", "sphinx", "knowledge"];
        parent::setupBeforeClass();
    }

    /**
     * Test GET /articles/{ID}/translations
     */
    public function testGetArticleTranslations() {
        $this->createKnowledgeBase([
            'siteSectionGroup' => 'mockSiteSectionGroup-1',
        ]);

        $article = $this->createArticle();
        $articleID = $article["articleID"];

        $response = $this->api()->get("/articles/{$articleID}/translations");
        $articleTranslations = $response->getBody();

        $this->assertCount(4, $articleTranslations);
        $this->assertEquals("up-to-date", $articleTranslations[0]["translationStatus"]);
        $this->assertEquals("en", $articleTranslations[0]["locale"]);
    }

    /**
     * Test posting article in a locale that is supported.
     *
     */
    public function testPostArticleInSupportedLocale() {
        $kb = $this->createKnowledgeBase();
        $article = $this->createArticle([
            'locale' => 'en',
        ]);
        $this->assertEquals(201, $this->lastResponse->getStatusCode());
        $this->assertEquals($kb["sourceLocale"], $article["locale"]);
    }

    /**
     * Test posting article in a locale that isn't supported.
     */
    public function testPostArticleInNotSupportedLocale() {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Articles must be created in en locale.');

        $kb = $this->createKnowledgeBase();

        $this->createArticle([
            'locale' => 'ru',
        ]);
    }

    /**
     * Test posting article in a locale that is supported.
     */
    public function testPatchArticleInSupportedLocale() {
        $kb = $this->createKnowledgeBase(['siteSectionGroup' => 'mockSiteSectionGroup-1']);

        $article = $this->createArticle([], ['ru']);
        $articleID = $article['articleID'];

        $response = $this->api()->get("/articles/$articleID/revisions");
        $revisions =  $response->getBody();
        $locales = array_column($revisions, "locale");
        $status = array_column($revisions, "status");

        $this->assertEquals(2, count($revisions));
        $this->assertContainsEquals("en", $locales);
        $this->assertContainsEquals("ru", $locales);
        $this->assertEquals(["published", "published"], $status);
    }

    /**
     * Test posting article in a locale that is supported.
     */
    public function testPatchArticleInNotSupportedLocale() {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Locale xx not supported in this Knowledge-Base");

        $kb = $this->createKnowledgeBase(['siteSectionGroup' => 'mockSiteSectionGroup-1']);

        $article = $this->createArticle();

        $record = [
            "body" => "Translated article body",
            "format" => "markdown",
            "locale" => "xx",
            "name" => "Translated Example Article",
        ];
        $this->api()->patch("/articles/".$article["articleID"], $record);
    }

    /**
     * Test GET /articles when filtering with locale without fallback articles.
     */
    public function testGetArticlesFilterByLocaleOnlyTranslated() {
        $kb = $this->createKnowledgeBase(['siteSectionGroup' => 'mockSiteSectionGroup-1']);

        $this->createArticle([], ['fr']);
        $this->createArticle([], ['fr']);
        $this->createArticle([]);

        $response = $this->api()->get(
            '/articles',
            [
                "knowledgeCategoryID" => $kb['rootCategoryID'],
                "locale" => "fr",
                "only-translated" => true,
            ]
        );
        $articles = $response->getBody();
        $this->assertEquals(2, count($articles));

        $response = $this->api()->get('/articles', ["knowledgeCategoryID" => $kb['rootCategoryID'], "locale" => "en"]);
        $articles = $response->getBody();
        $this->assertEquals(3, count($articles));
    }

    /**
     * Test GET /articles when filtering locale with fallback articles.
     */
    public function testGetArticlesFilterByLocale() {
        $kb = $this->createKnowledgeBase(['siteSectionGroup' => 'mockSiteSectionGroup-1']);

        $this->createArticle();
        $this->createArticle();
        $this->createArticle();
        $this->createArticle([], ["ru"]);
        $this->createArticle([], ["ru"]);

        $response = $this->api()->get(
            '/articles',
            [
                "knowledgeCategoryID" => $kb['rootCategoryID'],
                "locale" => "ru"
            ]
        );
        $article = $response->getBody();
        $locales = array_count_values(array_column($article, "locale"));

        $this->assertEquals(5, count($article));
        $this->assertEquals(2, $locales["ru"]);
        $this->assertEquals(3, $locales["en"]);
    }

    /**
     * Test Get /articles/{ID} filtered by locale providing fallback article.
     */
    public function testGetArticleFilterByIDAndLocaleFallback() {
        $kb = $this->createKnowledgeBase(['siteSectionGroup' => 'mockSiteSectionGroup-1']);

        $article = $this->createArticle([], ["es"]);
        $articleID = $article['articleID'];

        $response = $this->api()->get(
            '/articles'.'/'.$articleID,
            [
                "knowledgeCategoryID" => $kb['rootCategoryID'],
                "locale" => "fr",
                "only-translated" => false,
            ]
        );

        $article = $response->getBody();
        $this->assertEquals("en", $article["locale"]);
        $this->assertEquals($articleID, $article["articleID"]);
    }

    /**
     * Test Get /articles/{ID} filtered by locale providing translated article.
     */
    public function testGetArticleFilterByIDAndLocaleTranslated() {
        $kb = $this->createKnowledgeBase(['siteSectionGroup' => 'mockSiteSectionGroup-1']);

        $article = $this->createArticle([], ["es"]);
        $articleID = $article['articleID'];

        $response = $this->api()->get(
            '/articles'.'/'.$articleID,
            [
                "knowledgeCategoryID" => $kb['rootCategoryID'],
                "locale" => "es",
                "only-translated" => true,
            ]
        );

        $article = $response->getBody();
        $this->assertEquals("es", $article["locale"]);
        $this->assertEquals($articleID, $article["articleID"]);
    }

    /**
     * Test Get /articles/{ID} filtered by locale with no translated article found.
     */
    public function testGetArticleFilterByIDAndLocaleNoTranslation() {
        $this->expectException(NotFoundException::class);
        $kb = $this->createKnowledgeBase(['siteSectionGroup' => 'mockSiteSectionGroup-1']);

        $article = $this->createArticle();
        $articleID = $article['articleID'];

        $response = $this->api()->get(
            '/articles'.'/'.$articleID,
            [
                "knowledgeCategoryID" => $kb['rootCategoryID'],
                "locale" => "fr",
                "only-translated" => true,
            ]
        );
    }

    /**
     * Test translations-statuses are set correctly from POST & PATCH /articles.
     */
    public function testTranslationsStatuses() {
        $kb = $this->createKnowledgeBase(['siteSectionGroup' => 'mockSiteSectionGroup-1']);

        $article = $this->createArticle([], ["es", "ru"]);
        $articleID = $article['articleID'];
        $response = $this->api()->get("/articles/".$articleID."/revisions");
        $revisions = $response->getBody();
        $translationsStatuses = array_column($revisions, "translationStatus");
        $translationsStatuses = array_unique($translationsStatuses);

        $this->assertEquals(3, count($revisions));
        $this->assertEquals(1, count($translationsStatuses));
        $this->assertEquals("up-to-date", $translationsStatuses[0]);
    }

    /**
     * Test PUT /articles/{ID}/invalidate-translations.
     */
    public function testInvalidatingTranslations() {
        $kb = $this->createKnowledgeBase(['siteSectionGroup' => 'mockSiteSectionGroup-1']);

        $article = $this->createArticle([], ["es", "ru"]);
        $articleID = $article['articleID'];
        $response = $this->api()->put("/articles/".$articleID."/invalidate-translations");

        $revisions = $response->getBody();
        $translationStatuses = array_column($revisions, "translationStatus", "locale");

        $this->assertEquals("up-to-date", $translationStatuses["en"]);
        $this->assertEquals("out-of-date", $translationStatuses["es"]);
        $this->assertEquals("out-of-date", $translationStatuses["ru"]);
    }

    /**
     * Generate two test articles in three locales each.
     *
     * @return array
     */
    protected function generateTestArticles(): array {
        $kb = $this->createKnowledgeBase(['siteSectionGroup' => 'mockSiteSectionGroup-1']);
        $articles[] = $this->createArticle(['name' => __FUNCTION__.' 1'], ['es', 'ru']);
        $articles[] = $this->createArticle(['name' => __FUNCTION__.' 2'], ['es']);

        return $articles;
    }

    /**
     * Article translations should be selectable via API.
     */
    public function testIndexMultiLocale(): void {
        $articles = $this->generateTestArticles();

        $url = '/articles?'.http_build_query([
            'articleID' => implode(',', array_column($articles, 'articleID')),
            'limit' => 4
        ]);

        $actual = iterator_to_array(new FlatApiPaginationIterator($this->api(), $url), false);
        $this->assertCount(5, $actual);

        $counts = ['en' => 2, 'ru' => 1, 'es' => 2, $articles[0]['articleID'] =>  3, $articles[1]['articleID'] =>  2];
        foreach ($actual as $article) {
            $counts[$article['locale']]--;
            $counts[$article['articleID']]--;
        }
        $this->assertEmpty(array_filter($counts, function ($v) {
            return $v !== 0;
        }));
    }

    /**
     * I should still be able to filter articles by locale.
     */
    public function testIndexLocaleFilter(): void {
        $articles = $this->generateTestArticles();
        $actual = $this->api()->get('/articles', ['locale' => 'es', 'articleID' => implode(',', array_column($articles, 'articleID'))]);

        $this->assertCount(2, array_filter($actual->getBody(), function ($row) {
            return $row['locale'] === 'es';
        }));
    }
}
