<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;

/**
 * Endpoint for the virtual "knowledge navigation" resource.
 */
class KnowledgeNavigationApiController extends AbstractApiController {

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var Schema */
    private $categoryNavigationFragment;

    /**
     * KnowledgeNavigationApiController constructor.
     *
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     */
    public function __construct(KnowledgeCategoryModel $knowledgeCategoryModel) {
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
    }

    /**
     * Get a schema with limited fields for representing a knowledge category row.
     *
     * @return Schema
     */
    public function categoryNavigationFragment(): Schema {
        if ($this->categoryNavigationFragment === null) {
            $schema = [
                "name" => ["type" => "string"],
                "displayType" => [
                    "allowNull" => true,
                    "enum" => ["help", "guide", "search"],
                    "type" => "string",
                ],
                "isSection" => ["type" => "boolean"],
                "url" => ["type" => "string"],
                "parentID" => ["type" => "integer"],
                "recordID" => ["type" => "integer"],
                "recordType" => [
                    "enum" => ["article", "knowledgeCategory"],
                    "type" => "string",
                ],
                "children:a?" => [
                    "name" => ["type" => "string"],
                    "displayType" => [
                        "allowNull" => true,
                        "enum" => ["help", "guide", "search"],
                        "type" => "string",
                    ],
                    "isSection" => ["type" => "boolean"],
                    "url" => ["type" => "string"],
                    "parentID" => ["type" => "integer"],
                    "recordID" => ["type" => "integer"],
                    "recordType" => [
                        "enum" => ["article", "knowledgeCategory"],
                        "type" => "string",
                    ],
                ]
            ];

            $this->categoryNavigationFragment = $this->schema($schema, "CategoryNavigationFragment");
        }
        return $this->categoryNavigationFragment;
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

        $query = $in->validate($query);

        $tree = $this->knowledgeCategoryModel->sectionChildren($query["knowledgeCategoryID"], true, true);
        foreach ($tree as &$row) {
            $row = $this->normalizeOutput($row);
        }

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
        $out = $this->schema([":a" => $this->categoryNavigationFragment()], "out");

        $query = $in->validate($query);

        $tree = $this->knowledgeCategoryModel->sectionChildren($query["knowledgeCategoryID"], true, false);
        foreach ($tree as &$row) {
            $row = $this->normalizeOutput($row);
        }

        $result = $out->validate($tree);
        return $result;
    }

    /**
     * Prepare default schema array for "in" schema
     *
     * @return array
     */
    protected function defaultSchema() {
        return [
            "knowledgeBaseID:i?" => "Unique ID of a knowledge base. Results will be relative to this value.",
            "knowledgeCategoryID:i" => "Unique ID of a knowledge category to get navigation for. Results will be relative to this value.",
            "maxDepth:i" => [
                "default" => 2,
                "description" => "The maximum depth results should be, relative to the target knowledge base or category."
            ]
        ];
    }

    /**
     * Massage tree data for useful API output.
     *
     * @param array $row
     * @return array
     * @throws \Exception If $row is not a valid knowledge category.
     */
    private function normalizeOutput(array $row): array {
        $row["recordID"] = $row["knowledgeCategoryID"];
        $row["recordType"] = "knowledgeCategory";
        $row["url"] = $this->knowledgeCategoryModel->url($row);
        if (!empty($row["children"])) {
            foreach ($row["children"] as &$child) {
                $child = $this->normalizeOutput($child);
            }
        }
        return $row;
    }
}
