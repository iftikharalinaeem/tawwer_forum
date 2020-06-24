<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use VanillaTests\Knowledge\Utils\KbApiTestCase;

/**
 * Tests for dates on the articles endpoint.
 */
class ArticleDatesTest extends KbApiTestCase {

    const DATE_2016 = "2016-04-01T14:00:30+00:00";
    const DATE_2018 = "2018-04-01T14:00:30+00:00";
    const DATE_2020 = "2020-04-01T14:00:30+00:00";

    /**
     * Test that article dates are correct for each locale.
     */
    public function testCorrectDatesForLocales() {
        $this->createKnowledgeBase([
            'siteSectionGroup' => 'mockSiteSectionGroup-1', // Ensure locales are available.
        ]);
        $this->createCategory();
        $article = $this->createArticle([
            'name' => 'hello en',
            'dateInserted' => self::DATE_2016,
            'dateUpdated' => self::DATE_2016,
            'locale' => 'en',
        ]);
        $articleID = $article['articleID'];

        $patchUrl = "/articles/$articleID";

        $this->assertEquals(self::DATE_2016, $article['dateUpdated']);
        $this->assertEquals(self::DATE_2016, $article['dateInserted']);

        // Add a french version
        $article = $this->api()->patch($patchUrl, [
            'dateInserted' => self::DATE_2018,
            'locale' => 'fr',
            'name' => 'hello fr',
            'body' => 'hello fr',
            'format' => 'text',
        ]);

        $this->assertEquals(self::DATE_2018, $article['dateUpdated']);

        // Add a spanish version
        $article = $this->api()->patch($patchUrl, [
            'dateInserted' => self::DATE_2020,
            'locale' => 'es',
            'name' => 'hello es',
            'body' => 'hello es',
            'format' => 'text',
        ]);

        $this->assertEquals(self::DATE_2020, $article['dateUpdated']);

        // Make sure all previous dates were preserved.
        $this->assertArticleHasDateUpdated($articleID, 'en', self::DATE_2016);
        $this->assertArticleHasDateUpdated($articleID, 'fr', self::DATE_2018);
        $this->assertArticleHasDateUpdated($articleID, 'es', self::DATE_2020);

        // Make sure the translations endpoint is correct too.
        $articleTranslations = $this->api()->get("/articles/$articleID/translations")->getBody();
        $result = array_column($articleTranslations, "dateUpdated", "locale");
        $this->assertSame(
            [
                "en" => self::DATE_2016,
                "fr" => self::DATE_2018,
                "es" => self::DATE_2020,
                "ru" => self::DATE_2016, // Was never set so falls back to the default.
            ],
            $result
        );
    }

    /**
     * Assert that an article has a particular update date.
     *
     * @param int $articleID
     * @param string $locale
     * @param string $expectedDate
     */
    private function assertArticleHasDateUpdated(int $articleID, string $locale, string $expectedDate) {
        $article = $this->api()->get("/articles/$articleID", ['locale' => $locale])->getBody();
        $this->assertEquals($expectedDate, $article['dateUpdated']);
    }
}
