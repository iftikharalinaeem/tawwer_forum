<?php
/**
 * @author Chris Chabilall <chris.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use VanillaTests\APIv2\AbstractAPIv2Test;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use VanillaTests\Fixtures\MockSiteSectionProvider;

/**
 * Class for testing multi-lingual knowledge-bases.
 */
class SphinxMultiLingualKnowledgeBaseTests extends AbstractAPIv2Test {

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-categories";

    /** @var string The resource route. */
    protected $kbArticlesUrl = "/articles";

    /** @var array $testData Data prepared for tests */
    protected static $testData;

    /** @var array $dockerResponse Stdout log saved when try to reindex Sphinx indexes */
    protected static $dockerResponse;

    /** @var bool */
    protected static $sphinxReindexed;

    /** @var array  */
    protected static $addons = ['vanilla', 'sphinx', 'knowledge'];

    /**
     * Call sphinx server port to trigger reindexing
     */
    public static function sphinxReindex() {
        $sphinxHost = c('Plugins.Sphinx.Server');
        exec('curl '.$sphinxHost.':9399', $dockerResponse);
        self::$dockerResponse = $dockerResponse;
        self::$sphinxReindexed = ('Sphinx reindexed.' === end($dockerResponse));
        sleep(1);
    }
    /**
     * Prepare knowledge base data for tests and reindex Sphinx indexes.
     */
    public function testData() {
        $siteSectionProvider = new MockSiteSectionProvider();
        self::container()
            ->setInstance(SiteSectionProviderInterface::class, $siteSectionProvider);

        $this->prepareData();
        self::sphinxReindex();
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!'."\n".end(self::$dockerResponse));
        }
        $this->assertTrue(true);
    }

    /**
     * @depends testData
     */
    public function testSearchByLocale() {
        $params = [
            'locale' => "fr"
        ];
        $response = $this->api()->get('/knowledge/search?'.http_build_query($params));
        $result = $response->getBody();
        $locale = array_unique(array_column($result, 'locale'));

        $this->assertEquals(5, count($result));
        $this->assertEquals("fr", $locale[0]);
    }

    /**
     * @depends testData
     */
    public function testSearchBySiteSectionGroup() {
        $params = [
            'siteSectionGroup' => "mockSiteSectionGroup-1"
        ];
        $response = $this->api()->get('/knowledge/search?'.http_build_query($params));
        $result = $response->getBody();

        $this->assertEquals(11, count($result));
    }

    /**
     * @depends testData
     */
    public function testSearchBySiteSectionGroupAndLocale() {
        $params = [
            'locale' => 'en',
            'siteSectionGroup' => "mockSiteSectionGroup-1"
        ];
        $response = $this->api()->get('/knowledge/search?'.http_build_query($params));
        $result = $response->getBody();

        $this->assertEquals(6, count($result));
    }

    /**
     * Generate test knowledge-base with articles.
     */
    protected function prepareData() {
        $helloWorldBody = json_encode([["insert" => "Hello World"]]);
        // Setup the test categories.
        $knowledgeBase = $this->api()->post('knowledge-bases', [
            "name" => __FUNCTION__ . " KB #1",
            "Description" => 'Test knowledge base description',
            "urlCode" => slugify(
                'test-Knowledge-Base-'.round(microtime(true) * 1000).rand(1, 1000)
            ),
            "viewType" => KnowledgeBaseModel::TYPE_GUIDE,
            "sortArticles" => KnowledgeBaseModel::ORDER_MANUAL,
            "sourceLocale" => "en",
            "siteSectionGroup" => "mockSiteSectionGroup-1",
        ])->getBody();

        $this->api()->post($this->kbArticlesUrl, [
            "knowledgeCategoryID" => $knowledgeBase['rootCategoryID'],
            "name" => "English_Article_Original",
            "body" => $helloWorldBody,
            "format" => "rich",
        ])->getBody();

        $this->createTranslations($knowledgeBase['rootCategoryID']);
    }

    /**
     * Create multiple articles with translations.
     *
     * @param int $rootCategoryID
     */
    protected function createTranslations($rootCategoryID) {
        $helloWorldBody = json_encode([["insert" => "Hello World"]]);

        for ($i = 0; $i <= 4; $i++) {
            $article = $this->api()->post($this->kbArticlesUrl, [
                "knowledgeCategoryID" => $rootCategoryID,
                "name" => "English_Article_" . $i,
                "body" => $helloWorldBody,
                "format" => "rich",
            ])->getBody();

            $this->api()->patch($this->kbArticlesUrl . '/' . $article['articleID'], [
                "knowledgeCategoryID" => $rootCategoryID,
                "name" => "French_Article_" . $i,
                "body" => json_encode([["insert" => "Bonjour monde"]]),
                "format" => "rich",
                "locale" => "fr",
            ]);
        }
    }
}
