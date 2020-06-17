<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Schema\Schema;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Sphinx\Tests\Utils\SphinxTestTrait;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\Fixtures\MockSiteSectionProvider;
use VanillaTests\Knowledge\Utils\KbApiTestCase;

/**
 * Class unified search test
 */
class SphinxUnifiedSearchKBTest extends KbApiTestCase {

    use SphinxTestTrait;

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-bases";

    /** @var string The resource route. */
    protected $kbArticlesUrl = "/articles";


    /** @var array addons */
    protected static $addons = ['vanilla', 'translationsapi', 'sphinx', 'knowledge', 'advancedsearch'];

    /**
     * Prepare knowledge base data for tests and reindex Sphinx indexes.
     */
    public function testData() {
        saveToConfig('Plugins.Sphinx.UseDeltas', true);
        $router = self::container()->get(\Gdn_Router::class);
        $defaultSection = new DefaultSiteSection(new MockConfig(), $router);
        $siteSectionProvider = new MockSiteSectionProvider($defaultSection);
        self::container()
            ->setInstance(SiteSectionProviderInterface::class, $siteSectionProvider);

        self::sphinxReindex();
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!'."\n".end(self::$dockerResponse));
        }
        $this->assertTrue(true);
    }

    /**
     * Search with an id of an knowledge-base
     *
     * @depends testData
     */
    public function testSearchByyKnowledgeBaseID() {
        $knowledgeBase = $this->createKnowledgeBase();
        Gdn::database()->sql()->truncate('article');
        $this->articleRecord($knowledgeBase['rootCategoryID'], 'unique', 'unique article');
        $this->articleRecord($knowledgeBase['rootCategoryID'], 'another article', 'another one');
        self::sphinxReindex();
        $params = [
            'query' => 'unique',
            'knowledgeBaseID' => $knowledgeBase['knowledgeBaseID']
        ];
        $response = $this->api()->get('/search?', $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        $this->assertEquals('unique', $results[0]['name']);
    }


    /**
     * Search with an name of an article.
     *
     * @depends testData
     */
    public function testSearchByyKnowledgeBaseIDWithName() {
        $knowledgeBase = $this->createKnowledgeBase();
        Gdn::database()->sql()->truncate('article');
        $this->articleRecord($knowledgeBase['rootCategoryID'], 'not unique', 'unique article');
        $this->articleRecord($knowledgeBase['rootCategoryID'], 'not unique', 'unique article');
        $this->articleRecord($knowledgeBase['rootCategoryID'], 'another article', 'another one');
        self::sphinxReindex();
        $params = [
            'name' => 'not unique',
            'knowledgeBaseID' => $knowledgeBase['knowledgeBaseID']
        ];
        $response = $this->api()->get('/search?', $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(2, count($results));
    }

    /**
     * Search with a specific locale.
     */
    public function testSearchKnowledgeBaseWithLocale() {
        $knowledgeBase = $this->createKnowledgeBase(['siteSectionGroup' => 'mockSiteSectionGroup-1']);
        Gdn::database()->sql()->truncate('article');
        $article = $this->articleRecord(
            $knowledgeBase["rootCategoryID"],
            "Article to be translated "
        );
        $article2 = $this->articleRecord(
            $knowledgeBase["rootCategoryID"],
            "Article not translated "
        );
        $article3 = $this->articleRecord(
            $knowledgeBase["rootCategoryID"],
            "Article not translated "
        );

        $record = [
            'name' => 'Article to be translated in fr',
            'body' => json_encode([["insert" => "Hello World"]]),
            'format' => 'rich',
            'locale' => 'fr',
            'knowledgeCategoryID' =>  $knowledgeBase["rootCategoryID"],
        ];

        $this->api()->patch($this->kbArticlesUrl . '/' . $article['articleID'], $record);
        self::sphinxReindex();

        $params = [
            'query' => 'Article',
            'knowledgeBaseID' => $knowledgeBase['knowledgeBaseID'],
            'locale' => 'fr',
        ];
        $response = $this->api()->get('/search?', $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
    }


    /**
     * Provide article record.
     *
     * @param int $categoryID
     * @param string $name
     * @param string $body
     *
     * @return array
     */
    private function articleRecord(int $categoryID, string $name = 'default name', string $body = 'Hello World') {
        $helloWorldBody = json_encode([["insert" => $body]]);
        $params = [
            "knowledgeCategoryID" => $categoryID,
            "name" => $name,
            "body" => $helloWorldBody,
            "format" => "rich",
        ];
        return $this->createArticle($params);
    }
}
