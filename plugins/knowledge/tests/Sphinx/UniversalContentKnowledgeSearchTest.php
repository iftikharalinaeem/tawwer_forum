<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Schema\Schema;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Site\DefaultSiteSection;
use VanillaTests\APIv2\AbstractAPIv2Test;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Garden\Web\Exception\ServerException;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\Fixtures\MockSiteSectionProvider;

/**
 * Class UniversalContentKnowledgeSearchTest
 */
class UniversalContentKnowledgeSearchTest extends AbstractAPIv2Test {

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
        $article = $this->api()->post(
            $this->kbArticlesUrl,
            $this->articleRecord(self::$targetKBs[1]["rootCategoryID"], "Article in non Universal category")
        )->getBody();

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
        $this->api()->patch($this->baseUrl . '/' .self::$sourceKBs[2]["knowledgeBaseID"], ["siteSectionGroup" =>  "mockSiteSectionGroup-1" ]);
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
        $article = $this->api()->post(
            $this->kbArticlesUrl,
            $this->articleRecord(self::$sourceKBs[2]["rootCategoryID"], "Article to be translated in Universal")
        )->getBody();

        $record = $this->articleRecord(self::$targetKBs[2]["rootCategoryID"], "Universal content article french article");
        $record["locale"] = "fr";

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
        $this->assertEquals(["Universal content article french article"], $articleNames);
    }

    /**
     * Provide knowledge-base record.
     *
     * @param bool $isUniversalSource
     * @param array $targetIDs
     * @return array
     */
    private function knowledgeBaseRecord(bool $isUniversalSource = false, array $targetIDs = []) {
        $salt = '-' . round(microtime(true) * 1000) . rand(1, 1000);
        return $record = [
            'name' => 'Test Knowledge Base',
            'description' => 'Test Knowledge Base ' . $salt,
            'viewType' => 'guide',
            'icon' => '',
            'bannerImage' => '',
            'sortArticles' => 'manual',
            'sourceLocale' => 'en',
            'urlCode' => 'test-knowledge-base' . $salt,
            "siteSectionGroup" => "vanilla",
            "isUniversalSource" => $isUniversalSource,
            "universalTargetIDs" => $targetIDs
        ];
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
        return $record =  [
            "knowledgeCategoryID" => $categoryID,
            "name" => $name,
            "body" => $helloWorldBody,
            "format" => "rich",
        ];
    }

    /**
     * Prepare some universal-knowledge-bases/target-knowledge-bases and content.
     */
    protected function prepareData() {
        // Create some target kb's
        for ($i = 0; $i < 4; $i++) {
            $knowledgeBase = $this->api()->post(
                $this->baseUrl,
                $this->knowledgeBaseRecord(false)
            )->getBody();

            self::$targetKBs[] = $knowledgeBase;
            self::$targetKBIDs[] =  $knowledgeBase["knowledgeBaseID"];
        }

        // Create some target kb's
        for ($i = 0; $i <= 2; $i++) {
            $knowledgeBase = $this->api()->post('knowledge-bases',
                $this->knowledgeBaseRecord(true, self::$targetKBIDs)
            )->getBody();

            $name = "Universal content article " . $i;
            $article = $this->api()->post(
                $this->kbArticlesUrl,
                $this->articleRecord($knowledgeBase["rootCategoryID"], $name)
            )->getBody();

            self::$articleNames[] = $article["name"];
            self::$sourceKBs[] =  $knowledgeBase;
        }
    }
}
