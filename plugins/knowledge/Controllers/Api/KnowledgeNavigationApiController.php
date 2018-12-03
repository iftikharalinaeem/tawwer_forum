<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Exception;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Vanilla\Exception\PermissionException;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Knowledge\Models\Navigation;

/**
 * Endpoint for the virtual "knowledge navigation" resource.
 */
class KnowledgeNavigationApiController extends AbstractApiController {

    /** @var ArticleModel */
    private $articleModel;

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
     */
    public function __construct(
        KnowledgeCategoryModel $knowledgeCategoryModel,
        ArticleModel $articleModel
    ) {
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
        $this->articleModel = $articleModel;
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
     * Given two navigation items, compare them and determine their sort order.
     *
     * @param array $a
     * @param array $b
     * @return int A value of -1, 0 or 1, depending on if $a is less than, equal to, or greater than $b.
     */
    private function compareItems(array $a, array $b): int {
        $sortA = $a["sort"] ?? null;
        $sortB = $b["sort"] ?? null;
        if ($sortA === $sortB) {
            // Same sort weight? We must go deeper.
            $typeA = $a["recordType"] ?? null;
            $typeB = $b["recordType"] ?? null;
            if ($typeA === $typeB) {
                // Same record type? Sort by name.
                $nameA = $a["name"] ?? null;
                $nameB = $b["name"] ?? null;
                return $nameA <=> $nameB;
            } elseif ($typeA === Navigation::RECORD_TYPE_ARTICLE) {
                // If the types differ, and A is an article, then B must be a category. Categories rank higher.
                return 1;
            } else {
                // If they're not the same type, and A isn't an article, it must be a category. A should rank higher.
                return -1;
            }
        } elseif ($sortA === null) {
            // If they're not the same, and A is null, then B must not be null. B should rank higher.
            return 1;
        } elseif ($sortB === null) {
            // If they're not the same, and B is null, then A must not be null. A should rank higher.
            return -1;
        } else {
            // We have two non-null, non-equal sort weights. Compare them using the combined-comparison operator.
            return $sortA <=> $sortB;
        }
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
            "knowledgeCategoryID?" => ["type" => "integer"],
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
            ->setDescription("Get a navigation-friendly category hierarchy flat mode.");
        $out = $this->schema([":a" => $this->categoryNavigationFragment()], "out");

        //$query = $in->validate($query);

        $tree = $this->getNavigation();
        $result = $out->validate($tree);
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
            ->setDescription("Get a navigation-friendly category hierarchy tree mode.");
        $out = $this->schema([":a" => $this->schemaWithChildren()], "out");

        //$query = $in->validate($query);

        $tree = $this->getNavigation(false);
        $result = $out->validate($tree);
        return $result;
    }

    /**
     * Return navigation array of a section
     *
     * @param bool $flatMode Mode: flat or tree
     * @return array
     */
    private function getNavigation(bool $flatMode = true): array {

        $categories = $this->knowledgeCategoryModel->get();
        $categories = $this->normalizeOutput($categories, Navigation::RECORD_TYPE_CATEGORY);

        $catIds = array_column($categories, 'knowledgeCategoryID');
        $articles = $this->articleModel->getExtended(
            [
                'a.knowledgeCategoryID' => $catIds,
                'a.status' => ArticleModel::STATUS_PUBLISHED
            ],
            [],
            ['recordType' => Navigation::RECORD_TYPE_ARTICLE]
        );
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
                $l['children'] = $this->createTree($list, $list[$l['knowledgeCategoryID']]);
                usort($l["children"], [$this, "compareItems"]);
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
//            "knowledgeBaseID:i?" => "Unique ID of a knowledge base. Results will be relative to this value.",
//            "knowledgeCategoryID:i?" => "Unique ID of a knowledge category to get navigation for. Results will be relative to this value.",
//            "maxDepth:i" => [
//                "default" => 2,
//                "description" => "The maximum depth results should be, relative to the target knowledge base or category."
//            ]
        ];
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
            } elseif ($recordType === Navigation::RECORD_TYPE_ARTICLE && $row["recordType"] == Navigation::RECORD_TYPE_ARTICLE) {
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
     * Get a navigation-friendly record hierarchy of categories and articles in flat mode.
     *
     * @param array $body Request body.
     * @return array Navigation items, arranged in a one-dimensional array.
     * @throws Exception If no session is available.
     * @throws HttpException If a relevant ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have adequate permission(s).
     * @throws ValidationException Throws an exception when input does not validate against the input schema.
     * @throws ValidationException Throws an exception when output does not validate against the output schema.
     * @todo Add support for multiple knowledge bases.
     */
    public function patch_flat(array $body = []): array {
        $this->permission("knowledge.kb.view");

        $patchSchema = Schema::parse([
            ":a" => Schema::parse([
                "recordType",
                "recordID",
                "parentID",
                "sort",
            ])->add(Schema::parse($this->getFragmentSchema()))
        ]);
        $in = $this->schema($patchSchema, "in")->setDescription("Get a navigation-friendly category hierarchy flat mode.");
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
        $categories = $this->knowledgeCategoryModel->get();
        Navigation::updateAlteredRows(
            $categories,
            $navigation,
            Navigation::RECORD_TYPE_CATEGORY,
            "knowledgeCategoryID",
            "parentID",
            $this->knowledgeCategoryModel
        );

        // Update articles.
        Navigation::updateAlteredRows(
            $this->articleModel->get(),
            $navigation,
            Navigation::RECORD_TYPE_ARTICLE,
            "articleID",
            "knowledgeCategoryID",
            $this->articleModel
        );

        foreach ($categories as $category) {
            $parentID = $category["parentID"] ?? null;
            if ($parentID === -1) {
                $this->knowledgeCategoryModel->updateCounts($category["knowledgeCategoryID"]);
                break;
            }
        }

        // Grab the new tree.
        $tree = $this->getNavigation();
        $result = $out->validate($tree);
        return $result;
    }
}
