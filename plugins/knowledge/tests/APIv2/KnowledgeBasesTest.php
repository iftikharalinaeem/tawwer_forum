<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Site\DefaultSiteSection;

/**
 * Test the /api/v2/knowledge-bases endpoint.
 */
class KnowledgeBasesTest extends AbstractResourceTest {

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-bases";

    /** @var string The name of the primary key of the resource. */
    protected $pk = "knowledgeBaseID";

    /** @var bool Whether to check if paging works or not in the index. */
    protected $testPagingOnIndex = false;

    protected static $addons = ['vanilla', 'sphinx', 'knowledge'];

    /** @var array Fields to be checked with get/<id>/edit */
    protected $editFields = [
        'name',
        'description',
        'viewType',
        'icon',
        'sortArticles',
        'sourceLocale',
        'urlCode',
        'siteSectionGroup',
    ];

    /**
     * Generate a unique URL code for a knowledge base
     *
     * @return string
     */
    public static function getUniqueUrlCode(): string {
        static $inc = 0;
        return 'kb-url-code' . $inc++;
    }

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
    }

    /**
     * Grab values for inserting a new knowledge base.
     *
     * @param string $name Name of the knowledge base.
     * @return array
     */
    public function record(string $name = 'Test Knowledge Base'): array {
        static $knowledgeBaseID = 1;

        $record = [
            'name' => $name,
            'description' => 'Test Knowledge Base DESCRIPTION',
            'viewType' => 'guide',
            'icon' => '',
            'bannerImage' => '',
            'sortArticles' => 'manual',
            'sourceLocale' => 'en',
            'urlCode' => self::getUniqueUrlCode(),
            'siteSectionGroup' => DefaultSiteSection::DEFAULT_SECTION_GROUP
        ];
        return $record;
    }

    /**
     * Test POST /knowledge-base
     * if it automatically generates rootCategory
     */
    public function testRootCategory() {
        $result = $this->api()->post(
            $this->baseUrl,
            $this->record(__FUNCTION__)
        );

        $this->assertEquals(201, $result->getStatusCode());
        $body = $result->getBody();
        // not ready yet - that is why "== 0" should be "> 0" in future!!!
        $this->assertTrue($body['rootCategoryID'] > 0);

        $rootCat = $this->api()->get(
            'knowledge-categories/'.$body['rootCategoryID']
        )->getBody();
        $this->assertEquals($body['knowledgeBaseID'], $rootCat['knowledgeBaseID']);
    }

    /**
     * Test PATCH 'name' /knowledge-base
     * if it automatically updates rootCategory name
     */
    public function testPatchName() {
        $result = $this->api()->post(
            $this->baseUrl,
            $this->record(__FUNCTION__)
        );

        $body = $result->getBody();
        // not ready yet - that is why "== 0" should be "> 0" in future!!!
        $this->assertTrue($body['rootCategoryID'] > 0);

        $rootCat = $this->api()->get(
            'knowledge-categories/'.$body['rootCategoryID']
        )->getBody();
        $this->assertEquals(__FUNCTION__, $rootCat['name']);

        $newName = 'New Patched Name for KnowledgeBase';
        $updateKB = $this->api()->patch(
            $this->baseUrl.'/'.$body['knowledgeBaseID'],
            ['name' => $newName]
        );

        $rootCat = $this->api()->get(
            'knowledge-categories/'.$body['rootCategoryID']
        )->getBody();
        $this->assertEquals($newName, $rootCat['name']);
    }

    /**
     * Test adding a category to a knowledge base that has met or exceeded the category limit.
     */
    public function testAddCategoryOverLimit() {
        $this->expectException(\Garden\Web\Exception\ClientException::class);
        $this->expectExceptionMessage("The category maximum has been reached for this knowledge base.");

        $knowledgeBase = $this->api()->post(
            $this->baseUrl,
            $this->record(__FUNCTION__)
        );

        /** @var KnowledgeBaseModel */
        $knowledgeCategoryModel = self::container()->get(KnowledgeBaseModel::class);
        // Instead of making X categories, we're going to manually set the count used for the limit hint.
        $knowledgeCategoryModel->update(
            ["countCategories" => (KnowledgeCategoryModel::ROOT_LIMIT_CATEGORIES_RECURSIVE)],
            ["knowledgeBaseID" => $knowledgeBase["knowledgeBaseID"]]
        );

        $this->api()->post(
            "knowledge-categories",
            [
                "name" => __FUNCTION__,
                "parentID" => $knowledgeBase["rootCategoryID"],
                "knowledgeBaseID" => $knowledgeBase["knowledgeBaseID"],
            ]
        );
    }

    /**
     * Test adding a category to a knowledge base that has not met or exceeded the category limit.
     */
    public function testAddCategoryUnderLimit() {
        $knowledgeBase = $this->api()->post(
            $this->baseUrl,
            $this->record(__FUNCTION__)
        );

        /** @var KnowledgeBaseModel */
        $knowledgeCategoryModel = self::container()->get(KnowledgeBaseModel::class);
        // Instead of making X categories, we're going to manually set the count used for the limit hint.
        $knowledgeCategoryModel->update(
            ["countCategories" => (KnowledgeCategoryModel::ROOT_LIMIT_CATEGORIES_RECURSIVE - 1)],
            ["knowledgeBaseID" => $knowledgeBase["knowledgeBaseID"]]
        );

        $knowledgeCategory = $this->api()->post(
            "knowledge-categories",
            [
                "name" => __FUNCTION__,
                "parentID" => $knowledgeBase["rootCategoryID"],
                "knowledgeBaseID" => $knowledgeBase["knowledgeBaseID"],
            ]
        );

        $this->assertEquals($knowledgeBase["knowledgeBaseID"], $knowledgeCategory["knowledgeBaseID"]);
    }

    /**
     * Test validation of siteSectionGroup field on POST endpoint
     */
    public function testPostSiteSectionGroupValidation() {
        $record = $this->record();
        $knowledgeBase = $this->api()->post($this->baseUrl, $record)->getBody();
        $this->assertEquals($record['siteSectionGroup'], $knowledgeBase['siteSectionGroup']);

        $record['siteSectionGroup'] = 'random-group-name-to-fail-validation';
        $this->expectException(ClientException::class);
        $knowledgeBase = $this->api()->post($this->baseUrl, $record)->getBody();
    }

    /**
     * Test validation of siteSectionGroup field on PATCH endpoint
     */
    public function testPatchSiteSectionGroupValidation() {
        $record = $this->record();
        $knowledgeBase = $this->api()->post($this->baseUrl, $record)->getBody();
        $this->assertEquals($record['siteSectionGroup'], $knowledgeBase['siteSectionGroup']);

        $record['siteSectionGroup'] = 'mockSiteSectionGroup-1';
        $knowledgeBase = $this->api()->patch($this->baseUrl.'/'.$knowledgeBase['knowledgeBaseID'], $record)->getBody();
        $this->assertEquals($record['siteSectionGroup'], $knowledgeBase['siteSectionGroup']);

        $record['siteSectionGroup'] = 'random-group-name-to-fail-validation';
        $this->expectException(ClientException::class);
        $knowledgeBase = $this->api()->patch($this->baseUrl.'/'.$knowledgeBase['knowledgeBaseID'], $record)->getBody();
    }

    /**
     * Test adding an article to a guide that has not met or exceeded the article limits.
     */
    public function testAddGuideArticleOverLimit() {
        $this->expectException(\Garden\Web\Exception\ClientException::class);
        $this->expectExceptionMessage("The article maximum has been reached for this knowledge base.");

        $knowledgeBase = $this->api()->post(
            $this->baseUrl,
            $this->record()
        );

        /** @var KnowledgeCategoryModel */
        $knowledgeCategoryModel = self::container()->get(KnowledgeCategoryModel::class);
        // Instead of making X articles, we're going to manually set the count used for the limit hint.
        $knowledgeCategoryModel->update(
            ["articleCountRecursive" => (KnowledgeCategoryModel::ROOT_LIMIT_GUIDE_ARTICLES_RECURSIVE)],
            ["knowledgeCategoryID" => $knowledgeBase["rootCategoryID"]]
        );

        $article = $this->api()->post(
            "articles",
            [
                "body" => "Hello world.",
                "format" => "markdown",
                "name" => "Hello World.",
                "knowledgeCategoryID" => $knowledgeBase["rootCategoryID"],
            ]
        );
    }

    /**
     * Test adding an article to a guide that has not met or exceeded the article limits.
     */
    public function testAddGuideArticleUnderLimit() {
        $knowledgeBase = $this->api()->post(
            $this->baseUrl,
            $this->record(__FUNCTION__)
        );

        /** @var KnowledgeCategoryModel */
        $knowledgeCategoryModel = self::container()->get(KnowledgeCategoryModel::class);
        // Instead of making X articles, we're going to manually set the count used for the limit hint.
        $knowledgeCategoryModel->update(
            ["articleCountRecursive" => (KnowledgeCategoryModel::ROOT_LIMIT_GUIDE_ARTICLES_RECURSIVE - 1)],
            ["knowledgeCategoryID" => $knowledgeBase["rootCategoryID"]]
        );

        $article = $this->api()->post(
            "articles",
            [
                "body" => "Hello world.",
                "format" => "markdown",
                "name" => "Hello World.",
                "knowledgeCategoryID" => $knowledgeBase["rootCategoryID"],
            ]
        );

        $this->assertEquals($knowledgeBase["rootCategoryID"], $article["knowledgeCategoryID"]);
    }

    /**
     * Test adding an article to a help center that has met or exceeded the article limits enforced on guides.
     */
    public function testAddHelpCenterArticleOverLimit() {
        $record = $this->record(__FUNCTION__);
        $record["viewType"] = "help";
        $record["sortArticles"] = "dateInsertedDesc";
        $knowledgeBase = $this->api()->post(
            $this->baseUrl,
            $record
        );

        /** @var KnowledgeCategoryModel */
        $knowledgeCategoryModel = self::container()->get(KnowledgeCategoryModel::class);
        // Instead of making X articles, we're going to manually set the count used for the limit hint.
        $knowledgeCategoryModel->update(
            ["articleCountRecursive" => (KnowledgeCategoryModel::ROOT_LIMIT_GUIDE_ARTICLES_RECURSIVE)],
            ["knowledgeCategoryID" => $knowledgeBase["rootCategoryID"]]
        );

        $article = $this->api()->post(
            "articles",
            [
                "body" => "Hello world.",
                "format" => "markdown",
                "name" => "Hello World.",
                "knowledgeCategoryID" => $knowledgeBase["rootCategoryID"],
            ]
        );

        $this->assertEquals($knowledgeBase["rootCategoryID"], $article["knowledgeCategoryID"]);
    }

    /**
     * Test /knowledge-base with site-section filters.
     *
     * @param string $query Query string for API call.
     * @param int $expected Expected number of rows returned.
     *
     * @dataProvider filteringSiteSectionProvider
     */
    public function testFilteringBySiteSection($query, $expected) {
        /** @var KnowledgeBaseModel */
        $knowledgeModel = self::container()->get(KnowledgeBaseModel::class);
        $knowledgeBases = $knowledgeModel->get();

        for ($i = 0; $i <= 2; $i++) {
            $siteSectionGroup = ($i !== 2) ? 'mockSiteSectionGroup-1' : 'mockSiteSectionGroup-2';
            $this->api()->patch(
                $this->baseUrl.'/'.$knowledgeBases[$i]['knowledgeBaseID'],
                ['siteSectionGroup' => $siteSectionGroup]
            );
        }

        $results = $this->api()->get(
            $this->baseUrl,
            ['siteSectionGroup' => $query]
        );
        $results = $results->getBody();
        $this->assertCount($expected, $results);
    }


    /**
     * Data provider for filteringBySiteSections test.
     *
     * @return array
     */
    public function filteringSiteSectionProvider() {
        return [
            ['mockSiteSectionGroup-1', 3],
            ['mockSiteSectionGroup-2', 1],
            ['mockSiteSectionGroup-3', 0],
            ['all', 9],
            [null, 9],
        ];
    }
}
