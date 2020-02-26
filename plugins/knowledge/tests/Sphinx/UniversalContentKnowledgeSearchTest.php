<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Schema\Schema;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Site\DefaultSiteSection;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\Fixtures\MockSiteSectionProvider;
use VanillaTests\Knowledge\Utils\KbApiTestCase;

/**
 * Class UniversalContentKnowledgeSearchTest
 */
class UniversalContentKnowledgeSearchTest extends KbApiTestCase {

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-bases";

    /** @var string The resource route. */
    protected $kbArticlesUrl = "/articles";

    /** @var array $targetKBs prepared for tests */
    protected static $targetKBs;

    /** @var array $targetKBIDs prepared for tests */
    protected static $targetKBIDs;

    /** @var array $articleNames prepared for tests */
    protected static $articleNames;

    /** @var array $testData Data prepared for tests */
    protected static $sourceKBs;

    /** @var array $dockerResponse Stdout log saved when try to reindex Sphinx indexes */
    protected static $dockerResponse;

    /** @var Schema */
    protected static $searchResultSchema;

   /** @var bool */
    protected static $sphinxReindexed;

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
    public function testSearchByEmptyKnowledgeBase() {
        $params = [
            'knowledgeBaseID' => self::$targetKBIDs[0]
        ];
        $response = $this->api()->get('/knowledge/search?', $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $articleNames = array_column($results, "name");
        $this->assertEquals(3, count($results));
        $this->assertEquals(self::$articleNames, $articleNames);
    }

    /**
     * Search with an id of an knowledge-base that has content.
     * knowledge-base content and Universal Content should appear.
     *
     * @depends testData
     */
    public function testSearchByKnowledgeBaseWithContent() {
        $article = $this->articleRecord(
            self::$targetKBs[1]["rootCategoryID"],
            "Article in non Universal category"
        );

        self::sphinxReindex();

        $params = [
            'knowledgeBaseID' => self::$targetKBIDs[1]
        ];

        $response = $this->api()->get('/knowledge/search?', $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(4, count($results));

        $articleNames = array_column($results, "name");
        self::$articleNames[] = $article["name"];
        $this->assertEquals(self::$articleNames, $articleNames);
    }

    /**
     * Search with universal content across multiple site-sections.
     * Universal content and target content should appear.
     *
     * @depends testData
     */
    public function testSearchKnowledgeBaseWithDifferentSiteSection() {
        $this->api()->patch(
            $this->baseUrl . '/' .self::$sourceKBs[2]["knowledgeBaseID"],
            ["siteSectionGroup" =>  "mockSiteSectionGroup-1" ]
        );
        $params = [
            'knowledgeBaseID' => self::$targetKBIDs[1]
        ];
        $response = $this->api()->get('/knowledge/search?', $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $articleNames = array_column($results, "name");
        $this->assertEquals(4, count($results));
        $this->assertEquals(self::$articleNames, $articleNames);
    }

    /**
     * Search with different locale.
     * Only Univeral content should in locale should appear
     *
     * @depends testData
     */
    public function testSearchKnowledgeBaseWithLocale() {
        $article = $this->articleRecord(
            self::$sourceKBs[2]["rootCategoryID"],
            "Article to be translated in Universal"
        );
        $record = [
            'name' => 'Article to be translated in Universal KB fr',
            'body' => json_encode([["insert" => "Hello World"]]),
            'format' => 'rich',
            'locale' => 'fr',
            'knowledgeCategoryID' => self::$sourceKBs[2]["rootCategoryID"],
        ];


        $this->api()->patch($this->kbArticlesUrl . '/' . $article['articleID'], $record);
        self::sphinxReindex();

        $params = [
            'knowledgeBaseID' => self::$targetKBIDs[1],
            'locale' => 'fr',
        ];
        $response = $this->api()->get('/knowledge/search?', $params);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $articleNames = array_column($results, "name");
        $this->assertEquals(1, count($results));
        $this->assertEquals(["Article to be translated in Universal KB fr"], $articleNames);
    }

    /**
     * Provide knowledge-base record.
     *
     * @param bool $isUniversalSource
     * @param array $targetIDs
     * @return array
     */
    private function knowledgeBaseRecord(bool $isUniversalSource = false, array $targetIDs = []) {
        $params = [
            "siteSectionGroup" => "vanilla",
            "isUniversalSource" => $isUniversalSource,
            "universalTargetIDs" => $targetIDs,
            ];

        return $this->createKnowledgeBase($params);
    }

    /**
     * Provide article record.
     *
     * @param int $categoryID
     * @param string $name
     *
     * @return array
     */
    private function articleRecord(int $categoryID, string $name = 'default name') {
        $helloWorldBody = json_encode([["insert" => "Hello World"]]);
        $params = [
            "knowledgeCategoryID" => $categoryID,
            "name" => $name,
            "body" => $helloWorldBody,
            "format" => "rich",
        ];
        return $this->createArticle($params);
    }

    /**
     * Prepare some universal-knowledge-bases/target-knowledge-bases and content.
     */
    protected function prepareData() {
        // Create some target kb's
        for ($i = 0; $i < 4; $i++) {
            $knowledgeBase = $this->knowledgeBaseRecord(false);
            self::$targetKBs[] = $knowledgeBase;
            self::$targetKBIDs[] =  $knowledgeBase["knowledgeBaseID"];
        }

        // Create some target kb's
        for ($i = 0; $i <= 2; $i++) {
            $knowledgeBase = $this->knowledgeBaseRecord(true, self::$targetKBIDs);
            $name = "Universal content article " . $i;
            $article = $this->articleRecord($knowledgeBase["rootCategoryID"], $name);
            self::$articleNames[] = $article["name"];
            self::$sourceKBs[] =  $knowledgeBase;
        }
    }
}
