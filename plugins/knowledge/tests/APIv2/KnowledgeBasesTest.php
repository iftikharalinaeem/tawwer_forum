<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Knowledge\Models\KnowledgeCategoryModel;

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

    /** @var array Fields to be checked with get/<id>/edit */
    protected $editFields = [
        'name',
        'description',
        'viewType',
        'icon',
        'sortArticles',
        'sourceLocale',
    ];

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass() {
        self::$addons = ["vanilla", "knowledge"];
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
            'sortArticles' => 'manual',
            'sourceLocale' => '',
            'urlCode' => strtolower(preg_replace("/[^\w\d\-_]+/", "-", $name . $knowledgeBaseID))
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
     * Test adding a category to a knowledge base that has not met or exceeded the article limits.
     */
    public function testAddCategoryToLimit() {
        $knowledgeBase = $this->api()->post(
            $this->baseUrl,
            $this->record(__FUNCTION__)
        );

        // Try to add more categories than should be allowed.
        for ($i = 1; $i <= (KnowledgeCategoryModel::ROOT_LIMIT_CATEGORIES_RECURSIVE + 10); $i++) {
            try {
                $this->api()->post(
                    "knowledge-categories",
                    [
                        "name" => (__FUNCTION__ . " {$i}"),
                        "parentID" => $knowledgeBase["rootCategoryID"],
                        "knowledgeBaseID" => $knowledgeBase["knowledgeBaseID"],
                    ]
                );
            } catch (\Garden\Web\Exception\ClientException $e) {
                // That error should mean we've hit our limit.
                break;
            }
        }

        /** @var KnowledgeCategoryModel */
        $knowledgeCategory = $this->api()->get("knowledge-categories/{$knowledgeBase["rootCategoryID"]}");
        $this->assertEquals($knowledgeCategory["childCategoryCount"], KnowledgeCategoryModel::ROOT_LIMIT_CATEGORIES_RECURSIVE);
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
}
