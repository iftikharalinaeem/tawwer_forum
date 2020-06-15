<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Exception;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Knowledge\Models\KnowledgeNavigationModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * Test the /api/v2/knowledge-navigation endpoint.
 */
class KnowledgeNavigationTest extends AbstractAPIv2Test {

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
                    break;
                case KnowledgeNavigationModel::RECORD_TYPE_CATEGORY:
                    if ($parentID === -1) {
                        $knowledgeBase = $this->api()->post("knowledge-bases", [
                            "name" => $item["name"],
                            "description" => $item["name"],
                            "urlCode" => 'test-'.round(microtime(true) * 1000).rand(1, 1000),
                            "viewType" => KnowledgeBaseModel::TYPE_GUIDE,
                            "sortArticles" => KnowledgeBaseModel::ORDER_MANUAL
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
        Gdn::database()->sql()->truncate('article');
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
     * Test default sort order. No sort weights for anything. Categories first, articles second, otherwise name.
     */
    public function testDefaultSorting() {
        $expected = [
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
        ];
        $actual = $this->api()->get("knowledge-bases/1/navigation-tree")->getBody();

        $this->assertTreesEqual($expected, $actual);
    }

    /**
     * Test ability to move multiple items in one request.
     */
    public function testMultipleMove() {
        $this->api()->patch(
            'knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'].'/navigation-flat',
            [
                [
                    "parentID" => $this->categories["Parent Category B"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 1"]["articleID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
                [
                    "parentID" => $this->categories["Root Category"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Parent Category A"]["knowledgeCategoryID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category A"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Child Category A"]["knowledgeCategoryID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category A"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Child Category B"]["knowledgeCategoryID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category A"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Child Category C"]["knowledgeCategoryID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Root Category"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Parent Category B"]["knowledgeCategoryID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category B"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 2"]["articleID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
                [
                    "parentID" => $this->categories["Parent Category B"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 3"]["articleID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
                [
                    "parentID" => $this->categories["Parent Category B"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 4"]["articleID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
                [
                    "parentID" => $this->categories["Root Category"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Parent Category C"]["knowledgeCategoryID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category A"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Child Category D"]["knowledgeCategoryID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category A"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Child Category E"]["knowledgeCategoryID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category B"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 5"]["articleID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
            ]
        );

        $expected = [
            [
                "name" => "Root Category",
                "children" => [
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
                    [
                        "name" => "Parent Category B",
                        "children" => [
                            [
                                "name" => "Article 1",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                            ],
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
                            ],
                            [
                                "name" => "Article 5",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                            ],
                        ],
                        "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                    ],
                    [
                        "name" => "Parent Category C",
                        "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                    ],
                ],
                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
            ],
        ];
        $actual = $this->api()->get('knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'].'/navigation-tree')->getBody();

        $this->assertTreesEqual($expected, $actual);

        //extra check for Count fields updated correctly

        $expected = [
            "Root Category" => [
                'articleCount' => 0,
                'articleCountRecursive' => 5,
                'childCategoryCount' => 3 //A, B, C
            ],
            "Parent Category A" => [
                'articleCount' => 0,
                'articleCountRecursive' => 0,
                'childCategoryCount' => 5 //A, B, C, D, E
            ],
            "Child Category A" => [
                'articleCount' => 0,
                'articleCountRecursive' => 0,
                'childCategoryCount' => 0
            ],
            "Child Category B" => [
                'articleCount' => 0,
                'articleCountRecursive' => 0,
                'childCategoryCount' => 0
            ],
            "Child Category C" => [
                'articleCount' => 0,
                'articleCountRecursive' => 0,
                'childCategoryCount' => 0
            ],
            "Parent Category B" => [
                'articleCount' => 5,
                'articleCountRecursive' => 5, // 1, 2, 3, 4, 5
                'childCategoryCount' => 0
            ],
            "Parent Category C" => [
                'articleCount' => 0,
                'articleCountRecursive' => 0,
                'childCategoryCount' => 0
            ],
            "Child Category D" => [
                'articleCount' => 0,
                'articleCountRecursive' => 0,
                'childCategoryCount' => 0
            ],
            "Child Category E" => [
                'articleCount' => 0,
                'articleCountRecursive' => 0,
                'childCategoryCount' => 0
            ],
        ];
        $this->checkCountFields($expected);
    }

    /**
     * Basic sorting of articles and categories. Only the sort values are changed.
     */
    public function testSimpleSorting() {
        $this->api()->patch(
            'knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'].'/navigation-flat',
            [
                [
                    "parentID" => $this->categories["Root Category"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 1"]["articleID"],
                    "sort" => 1,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
                [
                    "parentID" => $this->categories["Root Category"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Parent Category A"]["knowledgeCategoryID"],
                    "sort" => 4,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category A"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Child Category A"]["knowledgeCategoryID"],
                    "sort" => 3,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category A"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Child Category B"]["knowledgeCategoryID"],
                    "sort" => 2,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category A"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Child Category C"]["knowledgeCategoryID"],
                    "sort" => 1,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Root Category"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Parent Category B"]["knowledgeCategoryID"],
                    "sort" => 3,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category B"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 2"]["articleID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
                [
                    "parentID" => $this->categories["Parent Category B"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 3"]["articleID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
                [
                    "parentID" => $this->categories["Parent Category B"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 4"]["articleID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
                [
                    "parentID" => $this->categories["Root Category"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Parent Category C"]["knowledgeCategoryID"],
                    "sort" => 2,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category C"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Child Category D"]["knowledgeCategoryID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category C"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Child Category E"]["knowledgeCategoryID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Parent Category C"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 5"]["articleID"],
                    "sort" => null,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
            ]
        );

        $expected = [
            [
                "name" => "Root Category",
                "children" => [
                    [
                        "name" => "Article 1",
                        "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                    ],
                    [
                        "name" => "Parent Category C",
                        "children" => [
                            [
                                "name" => "Child Category D",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                            ],
                            [
                                "name" => "Child Category E",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                            ],
                            [
                                "name" => "Article 5",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
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
                            ],
                        ],
                        "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                    ],
                    [
                        "name" => "Parent Category A",
                        "children" => [
                            [
                                "name" => "Child Category C",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                            ],
                            [
                                "name" => "Child Category B",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                            ],
                            [
                                "name" => "Child Category A",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                            ],
                        ],
                        "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                    ],
                ],
                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
            ],
        ];
        $actual = $this->api()->get('knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'].'/navigation-tree')->getBody();

        $this->assertTreesEqual($expected, $actual);

        //extra check for Count fields updated correctly

        $expected = [
            "Root Category" => [
                'articleCount' => 1,
                'articleCountRecursive' => 5,
                'childCategoryCount' => 3 //A, B, C
            ],
            "Parent Category A" => [
                'articleCount' => 0,
                'articleCountRecursive' => 0,
                'childCategoryCount' => 3 //A, B, C
            ],
            "Child Category A" => [
                'articleCount' => 0,
                'articleCountRecursive' => 0,
                'childCategoryCount' => 0
            ],
            "Child Category B" => [
                'articleCount' => 0,
                'articleCountRecursive' => 0,
                'childCategoryCount' => 0
            ],
            "Child Category C" => [
                'articleCount' => 0,
                'articleCountRecursive' => 0,
                'childCategoryCount' => 0
            ],
            "Parent Category B" => [
                'articleCount' => 3,
                'articleCountRecursive' => 3, // 2, 3, 4
                'childCategoryCount' => 0
            ],
            "Parent Category C" => [
                'articleCount' => 1,
                'articleCountRecursive' => 1, // 2, 3, 4
                'childCategoryCount' => 2 // D, E
            ],
            "Child Category D" => [
                'articleCount' => 0,
                'articleCountRecursive' => 0,
                'childCategoryCount' => 0
            ],
            "Child Category E" => [
                'articleCount' => 0,
                'articleCountRecursive' => 0,
                'childCategoryCount' => 0
            ],
        ];
        $this->checkCountFields($expected);
    }

    /**
     * Check if knowledge category "count" fields got properly updated after sorting
     *
     * @param array $expected Array of Expected values of categories "count" fields.
     */
    public function checkCountFields(array $expected) {
        $categories = $this->api()->get("knowledge-categories")->getBody();
        $countAsserts = 0;
        foreach ($categories as $actual) {
            if ($expectedCounts = ($expected[$actual['name']] ?? false)) {
                $countAsserts++;
                $this->assertEquals($expectedCounts['articleCount'], $actual['articleCount']);
                $this->assertEquals($expectedCounts['articleCountRecursive'], $actual['articleCountRecursive']);
                $this->assertEquals($expectedCounts['childCategoryCount'], $actual['childCategoryCount']);
            } else {
                $this->assertTrue(false, 'Unexpected category name : '.$actual['name']);
            }
        }
        $this->assertEquals(count($expected), $countAsserts);
    }

    /**
     * Test sorting when the full tree isn't provided. Only explicit items should be altered. Remaining items follow default sort.
     */
    public function testPartialSorting() {
        $this->api()->patch(
            'knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'].'/navigation-flat',
            [
                [
                    "parentID" => $this->categories["Root Category"]["knowledgeCategoryID"],
                    "recordID" => $this->categories["Parent Category A"]["knowledgeCategoryID"],
                    "sort" => 1,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                ],
                [
                    "parentID" => $this->categories["Root Category"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 1"]["articleID"],
                    "sort" => 2,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
                [
                    "parentID" => $this->categories["Parent Category B"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 4"]["articleID"],
                    "sort" => 1,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
                [
                    "parentID" => $this->categories["Parent Category B"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 3"]["articleID"],
                    "sort" => 2,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
                [
                    "parentID" => $this->categories["Parent Category B"]["knowledgeCategoryID"],
                    "recordID" => $this->articles["Article 2"]["articleID"],
                    "sort" => 3,
                    "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                ],
            ]
        );

        $expected = [
            [
                "name" => "Root Category",
                "children" => [
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
                        "name" => "Article 1",
                        "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                    ],
                    [
                        "name" => "Parent Category B",
                        "children" => [
                            [
                                "name" => "Article 4",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                            ],
                            [
                                "name" => "Article 3",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                            ],
                            [
                                "name" => "Article 2",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                            ],
                        ],
                        "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                    ],
                    [
                        "name" => "Parent Category C",
                        "children" => [
                            [
                                "name" => "Child Category D",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                            ],
                            [
                                "name" => "Child Category E",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                            ],
                            [
                                "name" => "Article 5",
                                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_ARTICLE,
                            ],
                        ],
                        "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
                    ],
                ],
                "recordType" => KnowledgeNavigationModel::RECORD_TYPE_CATEGORY,
            ],
        ];
        $actual = $this->api()->get('knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'].'/navigation-tree')->getBody();

        $this->assertTreesEqual($expected, $actual);
    }

    /**
     * Test GET ... 200 responses becomes 404 when knowledge base status changed to "deleted".
     */
    public function testDeletedKnowledgeBase() {

        $responseTree = $this->api()->get('knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'].'/navigation-tree');
        $initialBodyTree = $responseTree->getBody();
        $status = $responseTree->getStatus();

        $this->assertEquals('200 OK', $status);

        $responseFlat = $this->api()->get('knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'].'/navigation-flat');
        $initialBodyFlat = $responseFlat->getBody();
        $status = $responseFlat->getStatus();

        $this->assertEquals('200 OK', $status);

        //Delete knowledge base
        $status = $this->api()->patch(
            'knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'],
            ['status' => 'deleted']
        )->getStatus();

        $this->assertEquals('200 OK', $status);

        try {
            $statusTree = $this->api()->get('knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'].'/navigation-tree')->getStatus();
        } catch (NotFoundException $e) {
            $statusTree = $e->getCode();
        }

        $this->assertEquals('404', $statusTree, 'When knowledge base is "deleted" api call should bring an exception!');

        try {
            $statusFlat = $this->api()->get('knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'].'/navigation-flat')->getStatus();
            $statusFlat = 'When knowledge base is "deleted" api call should bring an exception!';
        } catch (NotFoundException $e) {
            $statusFlat = $e->getCode();
        }
        $this->assertEquals('404', $statusFlat);

        try {
            $statusPatchFlat = $this->api()->patch('knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'].'/navigation-flat', [])->getStatus();
            $statusPatchFlat = 'When knowledge base is "deleted" api call should bring an exception!';
        } catch (NotFoundException $e) {
            $statusPatchFlat = $e->getCode();
        }
        $this->assertEquals('404', $statusPatchFlat);


        // Let's check that no data had changed after knowledge base get restored back to "published" status
        $this->api()->patch(
            'knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'],
            ['status' => 'published']
        )->getStatus();

        $apiResponse = $this->api()->get('knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'].'/navigation-tree');
        $this->assertEquals('200 OK', $apiResponse->getStatus());

        $finalBodyTree = $apiResponse->getBody();
        $this->assertEquals($initialBodyTree, $finalBodyTree);

        $apiResponse = $this->api()->get('knowledge-bases/'.$this->knowledgeBase['knowledgeBaseID'].'/navigation-flat');
        $this->assertEquals('200 OK', $apiResponse->getStatus());

        $finalBodyFlat = $apiResponse->getBody();
        $this->assertEquals($initialBodyFlat, $finalBodyFlat);
    }
}
