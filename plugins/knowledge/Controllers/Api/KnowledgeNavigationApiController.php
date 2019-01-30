<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Exception;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Exception\PermissionException;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Knowledge\Models\Navigation;

/**
 * Endpoint for the virtual "knowledge navigation" resource.
 */
class KnowledgeNavigationApiController extends AbstractApiController {

    /** Filter value for limiting results to only knowledge categories. */
    const FILTER_RECORD_TYPE_CATEGORY = "knowledgeCategory";

    /** Filter value for allowing all record types. */
    const FILTER_RECORD_TYPE_ALL = "all";

    /** Default limit on the number of articles returned for a help center category. */
    const HELP_CENTER_DEFAULT_ARTICLES_LIMIT = 5;

    /** @var ArticleModel */
    private $articleModel;

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var Schema */
    private $categoryNavigationFragment;

    /** @var Schema */
    private $navigationTreeSchema;

    /**
     * KnowledgeNavigationApiController constructor.
     *
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     * @param ArticleModel $articleModel
     * @param KnowledgeBaseModel $knowledgeBaseModel
     */
    public function __construct(
        KnowledgeCategoryModel $knowledgeCategoryModel,
        ArticleModel $articleModel,
        KnowledgeBaseModel $knowledgeBaseModel
    ) {
        $this->articleModel = $articleModel;
        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
    }

    /**
     * Get a schema with limited fields for representing a knowledge category row.
     *
     * @return Schema
     */
    public function categoryNavigationFragment(): Schema {
        if ($this->categoryNavigationFragment === null) {
            $schema = Schema::parse($this->getFragmentSchema());

            $this->categoryNavigationFragment = $this->schema($schema, "CategoryNavigationFragment");
        }
        return $this->categoryNavigationFragment;
    }

    /**
     * Get navigation fragment schema attributes array
     *
     * @return array
     */
    protected function getFragmentSchema(): array {
        return [
            "name" => [
                "allowNull" => true,
                "description" => "Name of the item.",
                "type" => "string",
            ],
            "url?" => [
                "description" => "Full URL to the record.",
                "type" => "string"
            ],
            "parentID?" => [
                "description" => "Unique ID of the category this record belongs to.",
                "type" => "integer",
            ],
            "recordID" => [
                "description" => "Unique ID of the record represented by the navigation item.",
                "type" => "integer"
            ],
            "sort" => [
                "allowNull" => true,
                "description" => "Sort weight.",
                "type" => "integer",
            ],
            "knowledgeBaseID" => [
                "type" => "integer",
                "description" => "ID of the knowledge base the record belongs to.",
            ],
            "recordType" => [
                "description" => "Type of record represented by the navigation item.",
                "enum" => [Navigation::RECORD_TYPE_CATEGORY, Navigation::RECORD_TYPE_ARTICLE],
                "type" => "string",
            ]
        ];
    }

    /**
     * Get a navigation-friendly record hierarchy of categories and articles in flat mode.
     *
     * @param array $query Request query.
     * @return array Navigation items, arranged hierarchically.
     */
    public function get_flat(array $query = []): array {
        $this->permission("knowledge.kb.view");

        $in = $this->schema($this->defaultSchema(), "in")
            ->requireOneOf(["knowledgeBaseID", "knowledgeCategoryID"])
            ->setDescription("Get a navigation-friendly category hierarchy flat mode.");
        $out = $this->schema([":a" => $this->categoryNavigationFragment()], "out");

        $query = $in->validate($query);

        if (array_key_exists("knowledgeCategoryID", $query)) {
            $rows = $this->categoryNavigation($query["knowledgeCategoryID"], true, $query["recordType"]);
        } else {
            $rows = $this->knowledgeBaseNavigation($query["knowledgeBaseID"], true, $query["recordType"]);
        }

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Get a navigation-friendly record hierarchy of categories and articles in tree mode.
     *
     * @param array $query Request query.
     * @return array Navigation items, arranged hierarchically.
     */
    public function get_tree(array $query = []): array {
        $this->permission("knowledge.kb.view");

        $in = $this->schema($this->defaultSchema(), "in")
            ->requireOneOf(["knowledgeBaseID", "knowledgeCategoryID"])
            ->setDescription("Get a navigation-friendly category hierarchy tree mode.");
        $out = $this->schema([":a" => $this->schemaWithChildren()], "out");

        $query = $in->validate($query);

        if (array_key_exists("knowledgeCategoryID", $query)) {
            $tree = $this->categoryNavigation($query["knowledgeCategoryID"], false, $query["recordType"]);
        } else {
            $tree = $this->knowledgeBaseNavigation($query["knowledgeBaseID"], false, $query["recordType"]);
        }

        $result = $out->validate($tree);
        return $result;
    }

    /**
     * Get navigation items for a particular category.
     *
     * @param integer $knowledgeCategoryID
     * @param boolean $flat
     * @param string $recordType
     */
    private function categoryNavigation(int $knowledgeCategoryID, bool $flat, string $recordType = self::FILTER_RECORD_TYPE_ALL) {
        $categories = $this->knowledgeCategoryModel->get(["parentID" => $knowledgeCategoryID]);

        if ($recordType === self::FILTER_RECORD_TYPE_ALL) {
            $articles = $this->articleModel->getExtended(
                [
                    'a.knowledgeCategoryID' => $knowledgeCategoryID,
                    'a.status' => ArticleModel::STATUS_PUBLISHED
                ],
                ["limit" => false],
                ['recordType' => Navigation::RECORD_TYPE_ARTICLE]
            );
        } else {
            $articles = [];
        }

        $result = $this->getNavigation($categories, $articles, $flat, $recordType);
        return $result;
    }

    /**
     * Get navigation items for a knowledge base.
     *
     * @param integer $knowledgeBaseID
     * @param boolean $flat
     * @param string $recordType
     */
    private function knowledgeBaseNavigation(int $knowledgeBaseID, bool $flat, string $recordType = self::FILTER_RECORD_TYPE_ALL) {
        try {
            $knowledgeBase = $this->knowledgeBaseModel->selectSingle(["knowledgeBaseID" => $knowledgeBaseID]);
        } catch (\Vanilla\Exception\Database\NoResultsException $e) {
            throw new \Garden\Web\Exception\NotFoundException('Knowledge Base with ID: '.$knowledgeBaseID.' not found!');
        }

        $categories = $this->knowledgeCategoryModel->get(["knowledgeBaseID" => $knowledgeBaseID]);

        if ($recordType === self::FILTER_RECORD_TYPE_ALL) {
            $catIds = array_column($categories, 'knowledgeCategoryID');
            if ($knowledgeBase["viewType"] === KnowledgeBaseModel::TYPE_GUIDE) {
                $articles = $this->articleModel->getExtended(
                    [
                        'a.knowledgeCategoryID' => $catIds,
                        'a.status' => ArticleModel::STATUS_PUBLISHED
                    ],
                    ["limit" => false],
                    ['recordType' => Navigation::RECORD_TYPE_ARTICLE]
                );
            } else {
                list($orderField, $orderDirection) = $this->knowledgeBaseModel->articleSortConfig($knowledgeBase["sortArticles"]);
                $articles = $this->articleModel->getTopPerCategory(
                    $catIds,
                    $orderField,
                    $orderDirection,
                    self::HELP_CENTER_DEFAULT_ARTICLES_LIMIT
                );
            }
        } else {
            $articles = [];
        }

        $result = $this->getNavigation($categories, $articles, $flat, $recordType);
        return $result;
    }

    /**
     * Return navigation array of a section
     *
     * @param array $categories
     * @param array $articles
     * @param bool $flatMode Mode: flat or tree
     * @param string $recordType
     * @return array
     */
    private function getNavigation(
        array $categories,
        array $articles,
        bool $flatMode = true,
        string $recordType = self::FILTER_RECORD_TYPE_ALL
    ): array {
        $categories = $this->normalizeOutput($categories, Navigation::RECORD_TYPE_CATEGORY);
        $articles = $this->normalizeOutput($articles, Navigation::RECORD_TYPE_ARTICLE);

        if ($flatMode) {
            return array_merge($categories, $articles);
        } else {
            return $this->makeNavigationTree(KnowledgeCategoryModel::ROOT_ID, $categories, $articles);
        }
    }

    /**
     * Transform flat array into tree array when tree mode is required
     *
     * @param int $parentID Top level category ID
     * @param array $categories List of categories in section
     * @param array $articles List of articles in section
     * @return array
     */
    private function makeNavigationTree(int $parentID, array $categories, array $articles): array {
        $parentsIndex = [];
        foreach ($categories as $c) {
            $parentsIndex[$c['parentID']][] = $c;
        }
        foreach ($articles as $a) {
            $parentsIndex[$a['knowledgeCategoryID']][] = $a;
        }
        $result = $this->createTree($parentsIndex, $parentsIndex[$parentID]);

        return $result;
    }

    /**
     * Transforms flat array into tree array
     *
     * @param array $list Initial array to be transformed
     * @param array $parent Parent element (root)
     *
     * @return array Transformed tree array
     */
    private function createTree(array $list, array $parent): array {
        $tree = [];
        foreach ($parent as $k => $l) {
            if (isset($list[$l['knowledgeCategoryID']]) && $l['recordType'] === Navigation::RECORD_TYPE_CATEGORY) {
                $l['children'] = $this->createTree($list, $list[$l['recordID']]);
                usort($l["children"], [Navigation::class, "compareItems"]);
            }
            $tree[] = $l;
        }
        return $tree;
    }

    /**
     * Get a category schema with an additional field for an array of children.
     *
     * @return Schema
     */
    public function schemaWithChildren() {
        if ($this->navigationTreeSchema === null) {
            $schema = Schema::parse($this->getFragmentSchema())
                ->setID('navigationTreeSchema');
            $schema->merge(Schema::parse([
                'children:a?' =>  $schema
            ]));
            $this->navigationTreeSchema = $schema;
        }
        return $this->navigationTreeSchema;
    }

    /**
     * Prepare default schema array for "in" schema
     *
     * @return array
     */
    protected function defaultSchema() {
        return [
            "knowledgeBaseID?" => [
                "description" => "Unique ID of a knowledge base. Only results in this knowledge base will be included.",
                "type" => "integer",
            ],
            "knowledgeCategoryID?" => [
                "description" => "Unique ID of a knowledge category to get navigation for. Only direct children of this category will be included.",
                "type" => "integer",
            ],
            "recordType?" => [
                "default" => self::FILTER_RECORD_TYPE_ALL,
                "description" => "The type of record to limit navigation results to.",
                "enum" => [
                    self::FILTER_RECORD_TYPE_CATEGORY,
                    self::FILTER_RECORD_TYPE_ALL
                ],
                "type" => "string",
            ],
        ];
    }

    /**
     * Get a single knowledge base by its ID.
     *
     * @param int $knowledgeBaseID
     * @return array
     * @throws NotFoundException If the knowledge base could not be found.
     */
    public function knowledgeBaseByID(int $knowledgeBaseID): array {
        try {
            $result = $this->knowledgeBaseModel->selectSingle(["knowledgeBaseID" => $knowledgeBaseID]);
        } catch (\Vanilla\Exception\Database\NoResultsException $e) {
            throw new NotFoundException('Knowledge Base with ID: ' . $knowledgeBaseID . ' not found!');
        }

        return $result;
    }

    /**
     * Massage tree data for useful API output.
     *
     * @param array $rows
     * @param string $recordType Record type: RECORD_TYPE_CATEGORY || RECORD_TYPE_ARTICLE
     * @return array
     * @throws \Exception If $row is not a valid knowledge category.
     */
    private function normalizeOutput(array $rows, string $recordType = Navigation::RECORD_TYPE_CATEGORY): array {
        foreach ($rows as &$row) {
            if ($recordType === Navigation::RECORD_TYPE_CATEGORY) {
                $row["recordID"] = $row["knowledgeCategoryID"];
                $row["recordType"] = Navigation::RECORD_TYPE_CATEGORY;
                $row["url"] = $this->knowledgeCategoryModel->url($row);
            } elseif ($recordType === Navigation::RECORD_TYPE_ARTICLE) {
                $row["recordType"] = Navigation::RECORD_TYPE_ARTICLE;
                $row["parentID"] = $row["knowledgeCategoryID"];
                $row["recordID"] = $row["articleID"];
                $row["url"] = $this->articleModel->url($row);
            }
            if (!empty($row["children"])) {
                $row["children"] = $this->normalizeOutput($row["children"]);
            }
        }

        return $rows;
    }

    /**
     * Update the navigation structure of a knowledge base, using the flat format.
     *
     * @param int $id The knowledge base ID
     * @param array $body Request body.
     * @return array Navigation items, arranged in a one-dimensional array.
     * @throws Exception If no session is available.
     * @throws HttpException If a relevant ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have adequate permission(s).
     * @throws ValidationException Throws an exception when input does not validate against the input schema.
     * @throws ValidationException Throws an exception when output does not validate against the output schema.
     */
    public function patch_flat(int $id, array $body = []): array {
        $this->permission("Garden.Settings.Manage");

        $knowledgeBase = $this->knowledgeBaseByID($id);

        $patchSchema = Schema::parse([
            ":a" => Schema::parse([
                "recordType",
                "recordID",
                "parentID",
                "sort",
            ])->add(Schema::parse($this->getFragmentSchema()))
        ]);
        $in = $this->schema($patchSchema, "in")->setDescription("Update the navigation structure of a knowledge base, using the flat format.");
        $out = $this->schema([":a" => $this->categoryNavigationFragment()], "out");

        // Prep the input.
        $body = $in->validate($body);

        // Add a basic index.
        $navigation = [];
        foreach ($body as $item) {
            $key = $item["recordType"]."-".$item["recordID"];
            $navigation[$key] = $item;
        }

        // Update categories.
        $categories = $this->knowledgeCategoryModel->get(["knowledgeBaseID" => $id]);
        Navigation::updateAlteredRows(
            $categories,
            $navigation,
            Navigation::RECORD_TYPE_CATEGORY,
            "knowledgeCategoryID",
            "parentID",
            $this->knowledgeCategoryModel
        );

        // Update articles.
        $articles = $this->articleModel->get(
            ["knowledgeCategoryID" => array_column($categories, "knowledgeCategoryID")],
            ["limit" => false]
        );
        Navigation::updateAlteredRows(
            $articles,
            $navigation,
            Navigation::RECORD_TYPE_ARTICLE,
            "articleID",
            "knowledgeCategoryID",
            $this->articleModel
        );

        // Dumb update all categories until we get knowledge base support to locate the proper top-level category.
        foreach ($categories as $category) {
            $parentID = $category["parentID"] ?? null;
            if ($parentID === -1) {
                $this->knowledgeCategoryModel->resetAllCounts($category["knowledgeBaseID"]);
                break;
            }
        }

        // Grab the new navigation state.
        $navigation = $this->knowledgeBaseNavigation($id, true);
        $result = $out->validate($navigation);
        return $result;
    }

    /**
     * Get default article id of knowledge base.
     * Note: Should be called only when knowledge base is GUIDE.
     *
     * @param int $knowledgeBaseID
     * @return int|null Returns int articleID when knowledge base is in GUIDE mode and has any article.
     *                  In any other cases returns null
     */
    public function getDefaultArticleID(int $knowledgeBaseID) {
        $tree = $this->knowledgeBaseNavigation($knowledgeBaseID, false, self::FILTER_RECORD_TYPE_ALL);
        return $this->getArticleID($tree);
    }

    /**
     * Recursive scan of the navigation tree to find first RECORD_TYPE_ARTICLE
     *
     * @param array $tree
     * @return int|null
     */
    public function getArticleID(array $tree) {
        foreach ($tree as $branch) {
            if ($branch['recordType'] === Navigation::RECORD_TYPE_ARTICLE) {
                return $branch['recordID'];
            } elseif (isset($branch['children']) && !empty($branch['children'])) {
                $articleID = $this->getArticleID($branch['children']);
                if (null !== $articleID) {
                    return $articleID;
                }
            }
        }
        return null;
    }
}
