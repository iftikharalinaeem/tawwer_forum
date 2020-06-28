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
    public function testSearchByKnowledgeBaseID() {
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
    public function testSearchByKnowledgeBaseIDWithName() {
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
     * Search for deleted article.
     *
     * @depends testData
     */
    public function testSearchByStatusDeleted() {
        $knowledgeBase = $this->createKnowledgeBase();
        Gdn::database()->sql()->truncate('article');
        $article = $this->articleRecord($knowledgeBase['rootCategoryID'], 'article to be deleted', 'article to be deleted');
        $article2 = $this->articleRecord($knowledgeBase['rootCategoryID'], 'article to be deleted', 'article to be deleted');

        $this->api()->patch('/articles/'.$article['articleID'].'/status', ["status" => "deleted" ]);

        self::sphinxReindex();
        $params = [
            'query' => 'article to be deleted',
            'statuses' => ['deleted'],
        ];
        $response = $this->api()->get('/search?', $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
    }

    /**
     * Search for article status.
     *
     * @depends testData
     */
    public function testSearchByStatuses() {
        $knowledgeBase = $this->createKnowledgeBase();
        Gdn::database()->sql()->truncate('article');
        $article = $this->articleRecord($knowledgeBase['rootCategoryID'], 'article to be deleted', 'article to be deleted');
        $article2 = $this->articleRecord($knowledgeBase['rootCategoryID'], 'article to be published', 'article to be published');

        $this->api()->patch('/articles/'.$article['articleID'].'/status', ["status" => "deleted" ]);

        self::sphinxReindex();
        $params = [
            'query' => 'article to be',
            'statuses' => ['published','deleted'],
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
     * Search by featured article.
     *
     * @depends testData
     */
    public function testSearchByFeaturedArticle() {
        $knowledgeBase = $this->createKnowledgeBase();
        Gdn::database()->sql()->truncate('article');

        $article = $this->articleRecord($knowledgeBase['rootCategoryID'], 'featured article', 'featured article');
        $article2 = $this->articleRecord($knowledgeBase['rootCategoryID'], 'not featured 1', 'not featured');
        $article3 = $this->articleRecord($knowledgeBase['rootCategoryID'], 'not featured 2', 'not featured');

        $this->api()->put(
            $this->kbArticlesUrl . '/'. $article['articleID'] . '/featured',
            ['featured' => true]
        );

        self::sphinxReindex();
        $params = [
            'query' => 'featured',
            'featured' => true,
        ];
        $response = $this->api()->get('/search?', $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
    }

    /**
     * Search with a specific locale.
     */
    public function testSearchBySiteSectionGroup() {
        $knowledgeBase1 = $this->createKnowledgeBase(['siteSectionGroup' => 'mockSiteSectionGroup-1']);
        $knowledgeBase2 = $this->createKnowledgeBase(['siteSectionGroup' => 'mockSiteSectionGroup-2']);

        Gdn::database()->sql()->truncate('article');
        $article = $this->articleRecord(
            $knowledgeBase1["rootCategoryID"],
            "Article in KB1"
        );
        $article2 = $this->articleRecord(
            $knowledgeBase1["rootCategoryID"],
            "Article in KB1"
        );
        $article3 = $this->articleRecord(
            $knowledgeBase2["rootCategoryID"],
            "Article not translated "
        );
        $article4 = $this->articleRecord(
            $knowledgeBase2["rootCategoryID"],
            "Article not translated "
        );

        $record = [
            'name' => '"Article in KB1 translated in fr',
            'body' => json_encode([["insert" => "Hello World"]]),
            'format' => 'rich',
            'locale' => 'fr',
            'knowledgeCategoryID' =>  $knowledgeBase1["rootCategoryID"],
        ];

        $this->api()->patch($this->kbArticlesUrl . '/' . $article['articleID'], $record);
        self::sphinxReindex();

        $params = [
            'query' => 'Article',
            'siteSectionGroup' => 'mockSiteSectionGroup-1'
        ];
        $response = $this->api()->get('/search?', $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(3, count($results));
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
    private function articleRecord(
        int $categoryID,
        string $name = 'default name',
        string $body = 'Hello World'
    ) {
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
