<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Exception;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Knowledge\Models\KnowledgeNavigationModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use VanillaTests\Fixtures\MockSiteSectionProvider;

/**
 * Test the /api/v2/knowledge-navigation endpoint.
 */
class KnowledgeNavigationLocaleTest extends AbstractAPIv2Test {

    /** @var array */
    protected static $addons = ["vanilla", "sphinx", "knowledge"];

    /** @var array */
    private $articles;

    /** @var ArticleModel */
    private $articleModel;

    /** @var array */
    private $categories;

    /** @var array */
    private $knowledgeBase;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var SiteSectionProviderInterface */
    private $siteSectionProvider;

    /**
     * Assert two navigation trees are identical.
     *
     * @param array $expected
     * @param array $actual Navigation API response, represented as a tree.
     */
    private function assertTreesEqual(array $expected, array $actual) {
        // Filter a navigation tree down to its relevant components.
        $filter = function (array $nav) use (&$filter) {
            foreach ($nav as &$item) {
                if (is_array($item)) {
                    $item = $filter($item);
                }
            }

            if (!isset($nav[0])) {
                $nav = array_intersect_key($nav, [
                    "children" => null,
                    "name" => null,
                    "recordType" => null,
                ]);
            }
            return $nav;
        };

        $actualFiltered = $filter($actual);
        $this->assertEquals($expected, $actualFiltered);
    }

    /**
     * Add navigation items.
     *
     * @param array $items
     * @param int $parentID
     */
    private function insertNavigation(array $items, int $parentID = -1) {
        foreach ($items as $item) {
            $type = $item["recordType"] ?? null;
            switch ($type) {
                case KnowledgeNavigationModel::RECORD_TYPE_ARTICLE:
                    $response = $this->api()->post("articles", [
                        "name" => $item["name"],
                        "body" => '[{"insert": "Hello World"},{"insert":"\n"}]',
                        "format" => "rich",
                        "knowledgeCategoryID" => $parentID,
                    ]);
                    $this->articles[$item["name"]] = $response->getBody();
                    
                    if (isset($item['locale'])) {
                        $this->api()->patch("articles/".$this->articles[$item["name"]]['articleID'], [
                            "name" => $item["locale"].' - '.$item["name"],
                            "format" => "rich",
                            "body" => '[{"insert": "Hello World"},{"insert":"\n"}]',
                            "knowledgeCategoryID" => $parentID,
                            "locale" => $item["locale"]
                        ]);
                    }
                    
                    break;
                case KnowledgeNavigationModel::RECORD_TYPE_CATEGORY:
                    if ($parentID === -1) {
                        $knowledgeBase = $this->api()->post("knowledge-bases", [
                            "name" => $item["name"],
                            "description" => $item["name"],
                            "urlCode" => 'test-'.round(microtime(true) * 1000).rand(1, 1000),
                            "viewType" => KnowledgeBaseModel::TYPE_GUIDE,
                            "sortArticles" => KnowledgeBaseModel::ORDER_MANUAL,
                            "siteSectionGroup" => "mockSiteSectionGroup-1",
                        ]);
                        $parentID = $knowledgeBase['rootCategoryID'];
                        $this->knowledgeBase = $knowledgeBase;
                        $response = $this->api()->get("knowledge-categories/".$parentID);
                    } else {
                        $response = $this->api()->post("knowledge-categories", [
                            "name" => $item["name"],
                            "parentID" => $parentID,
                        ]);
                    }

                    $category = $response->getBody();
                    $this->categories[$item["name"]] = $category;
                    if (!empty($item["children"])) {
                        $this->insertNavigation($item["children"], $category["knowledgeCategoryID"]);
                    }
                    break;
            }
        }
    }

    /**
     * Reset the navigation tree.
     *
     * @throws Exception If an error occurred while performing the necessary database queries.
     */
    private function resetNavigation() {
        $this->articleModel->delete(["articleID >" => 0]);
        $this->articles = [];

        $this->knowledgeCategoryModel->delete(["knowledgeCategoryID >" => 0]);
        $this->categories = [];

        /**
         * PHPUnit can't properly initialize with this tree as a property, due to Navigation not being loadable when
         * this test class is initially loaded.
         */
        $this->insertNavigation([
            [
                "name" => "Root Category",
                "children" => [
                    [
                        "name" => "Article 1",
                        "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                    ],
                    [
                        "name" => "Parent Category A",
                        "children" => [
                            [
                                "name" => "Child Category A",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                            ],
                            [
                                "name" => "Child Category B",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                            ],
                            [
                                "name" => "Child Category C",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                            ],
                        ],
                        "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                    ],
                    [
                        "name" => "Parent Category B",
                        "children" => [
                            [
                                "name" => "Article 2",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                            ],
                            [
                                "name" => "Article 3",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                            ],
                            [
                                "name" => "Article 4",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                                "locale" => "fr",
                            ],
                        ],
                        "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                    ],
                    [
                        "name" => "Parent Category C",
                        "children" => [
                            [
                                "name" => "Article 5",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                                "locale" => "fr",

                            ],
                            [
                                "name" => "Child Category D",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                            ],
                            [
                                "name" => "Child Category E",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                            ],
                        ],
                        "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                    ],
                ],
                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
            ],
        ]);
    }

   /**
     * Setup routine for the test. Called before test execution.
     *
     * @throws \Garden\Container\ContainerException If there was an error while retrieving an item from the container.
     * @throws \Garden\Container\NotFoundException If no entry was found for the specified item in the container.
     */
    public function setUp(): void {
        parent::setUp();
        $this->articleModel = $this->container()->get(ArticleModel::class);
        $this->knowledgeCategoryModel = $this->container()->get(KnowledgeCategoryModel::class);

        $this->resetNavigation();
    }

    /**
     * Test GET navigation-flat with locale = sourceLocale
     *
     * @param string $locale
     * @param int $count
     * @param bool $onlyTranslated
     * @dataProvider validCounts
     */
    public function testNavigationFlat(string $locale, int $count, ?bool $onlyTranslated) {
        $query = [
            'locale' => $locale,
        ];
        if ($onlyTranslated) {
            $query['only-translated'] = $onlyTranslated;
        }
        $kbID = $this->knowledgeBase['knowledgeBaseID'];
        $response = $this->api()->get("/knowledge-bases/$kbID/navigation-flat", $query);
        $status = $response->getStatus();
        $this->assertEquals('200 OK', $status);

        $navigation = $response->getBody();

        $this->assertEquals($count, count($navigation));
    }

    /**
     * @return array Data with expected correct Count values
     */
    public function validCounts(): array {
        return [
            'Source locale (en)' => [
                'en', 14, null
            ],
            'Unknown locale (ua)' => [
                'ua', 14, null
            ],
            'Unknown locale (ua) only-translated (true)' => [
                'ua', 9, true
            ],
            'Translated locale (fr)' => [
                'fr', 14, null
            ],
            'Translated locale (fr)  only-translated (true)' => [
                'fr', 11, true
            ],
        ];
    }
}
