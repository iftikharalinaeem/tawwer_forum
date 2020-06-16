<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
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
 * Class UniversalContentKnowledgeSearchTest
 */
class SphinxUnifiedSearchKBTest extends KbApiTestCase {

    use SphinxTestTrait;

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-bases";

    /** @var string The resource route. */
    protected $kbArticlesUrl = "/articles";

    /** @var array $targetKBs prepared for tests */
    protected static $knowledgeBases;

    /** @var array addons */
    protected static $addons = ['vanilla', 'translationsapi', 'sphinx', 'knowledge', 'advancedsearch'];

    /**
     * Prepare knowledge base data for tests and reindex Sphinx indexes.
     */
    public function testData() {
        $router = self::container()->get(\Gdn_Router::class);
        $defaultSection = new DefaultSiteSection(new MockConfig(), $router);
        $siteSectionProvider = new MockSiteSectionProvider($defaultSection);
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
     * Search with an id of an knowledge-base that has no content.
     * Only Universal content should appear.
     *
     * @depends testData
     */
    public function testSearchByyKnowledgeBaseID() {
        $this->articleRecord(self::$knowledgeBases[0]['rootCategoryID'], 'unqiue', 'unique article');
        $this->articleRecord(self::$knowledgeBases[0]['rootCategoryID'], 'another article', 'another one');
        self::sphinxReindex();
        $params = [
            'query' => 'unique',
            'knowledgeBaseID' => self::$knowledgeBases[0]['knowledgeBaseID']
        ];
        $response = $this->api()->get('/search?', $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        $this->assertEquals('unqiue', $results[0]['name']);
    }


    /**
     * Search with an id of an knowledge-base that has no content.
     * Only Universal content should appear.
     *
     * @depends testData
     */
    public function testSearchByyKnowledgeBaseIDWithName() {
        $this->articleRecord(self::$knowledgeBases[0]['rootCategoryID'], 'not unique', 'unique article');
        $this->articleRecord(self::$knowledgeBases[0]['rootCategoryID'], 'not unique', 'unique article');
        $this->articleRecord(self::$knowledgeBases[0]['rootCategoryID'], 'another article', 'another one');
        self::sphinxReindex();
        $params = [
            'name' => 'not unique',
            'knowledgeBaseID' => self::$knowledgeBases[0]['knowledgeBaseID']
        ];
        $response = $this->api()->get('/search?', $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(2, count($results));
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

    /**
     * Prepare some knowledgeBases for test.
     */
    protected function prepareData() {
        for ($i = 0; $i < 4; $i++) {
            $knowledgeBase = $this->createKnowledgeBase();
            self::$knowledgeBases[] = $knowledgeBase;
        }
    }
}
