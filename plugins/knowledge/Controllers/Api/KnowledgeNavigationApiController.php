<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;

/**
 * Endpoint for the virtual "knowledge navigation" resource.
 */
class KnowledgeNavigationApiController extends AbstractApiController {
    const RECORD_TYPE_CATEGORY = 'knowledgeCategory';
    const RECORD_TYPE_ARTICLE = 'article';

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
     * Get navigation fragment schema attributes array
     *
     * @return array
     */
    protected function getFragmentSchema(): array {
        return [
            "name" => [
                "allowNull" => true,
                "type" => "string"
            ],
            "displayType?" => [
                "allowNull" => true,
                "enum" => ["help", "guide", "search"],
                "type" => "string",
            ],
            "url?" => ["type" => "string"],
            "parentID?" => ["type" => "integer"],
            "recordID" => ["type" => "integer"],
            "sort" => [
                "allowNull" => true,
                "type" => "integer"
            ],
            "knowledgeCategoryID?" => ["type" => "integer"],
            "recordType" => [
                "enum" => [self::RECORD_TYPE_CATEGORY, self::RECORD_TYPE_ARTICLE],
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
        $categories = $this->normalizeOutput($categories, self::RECORD_TYPE_CATEGORY);

        $catIds = array_column($categories, 'knowledgeCategoryID');
        $articles = $this->articleModel->getExtended(
            [
                'a.knowledgeCategoryID' => $catIds,
                'a.status' => ArticleModel::STATUS_PUBLISHED
            ],
            [],
            ['recordType' => self::RECORD_TYPE_ARTICLE]
        );
        $articles = $this->normalizeOutput($articles, self::RECORD_TYPE_ARTICLE);
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
            if (isset($list[$l['knowledgeCategoryID']]) && $l['recordType'] === self::RECORD_TYPE_CATEGORY) {
                $l['children'] = $this->createTree($list, $list[$l['knowledgeCategoryID']]);
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
            $schema = Schema::parse($this->getFragmentSchema())->setID('navigationTreeSchema');

            $schema->merge(Schema::parse([
                'children:a?' => Schema::parse($this->getFragmentSchema())->merge(
                    Schema::parse([
                        'children:a?'
                    ])
                )
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
     * @param srting $recordType Record type: RECORD_TYPE_CATEGORY || RECORD_TYPE_ARTICLE
     * @return array
     * @throws \Exception If $row is not a valid knowledge category.
     */
    private function normalizeOutput(array $rows, string $recordType = self::RECORD_TYPE_CATEGORY): array {
        foreach ($rows as &$row) {
            if ($recordType === self::RECORD_TYPE_CATEGORY) {
                $row["recordID"] = $row["knowledgeCategoryID"];
                $row["recordType"] = self::RECORD_TYPE_CATEGORY;
                $row["url"] = $this->knowledgeCategoryModel->url($row);
            } elseif ($recordType === self::RECORD_TYPE_ARTICLE && $row["recordType"] == self::RECORD_TYPE_ARTICLE) {
                $row["recordID"] = $row["articleID"];
                $row["url"] = $this->articleModel->url($row);
            }
            if (!empty($row["children"])) {
                $row["children"] = $this->normalizeOutput($row["children"]);
            }
        }

        return $rows;
    }
}
