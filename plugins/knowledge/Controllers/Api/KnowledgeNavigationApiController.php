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

    public function categoryNavigationFragment(): Schema {
        if ($this->categoryNavigationFragment === null) {
            $this->categoryNavigationFragment = $this->schema([
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
                "children:a" => [
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
            ], "CategoryNavigationFragment");
        }
        return $this->categoryNavigationFragment;
    }

    /**
     * Get a navigation-friendly record hierarchy of categories and articles.
     *
     * @param array $query Request query.
     * @return array Navigation items, arranged hierarchically.
     * @throws \Garden\Schema\ValidationException If input or output fails to validate against the schema.
     * @throws \Garden\Web\Exception\HttpException If a relevant permission ban is on the user's session.
     * @throws \Vanilla\Exception\PermissionException If the user does not have permission to access this resource.
     */
    public function index(array $query = []): array {
        $this->permission("knowledge.kb.view");

        $in = $this->schema([
            "knowledgeBaseID:i?" => "Unique ID of a knowledge base. Results will be relative to this value.",
            "knowledgeCategoryID:i" => "Unique ID of a knowledge category to get navigation for. Results will be relative to this value.",
            "maxDepth:i" => [
                "default" => 2,
                "description" => "The maximum depth results should be, relative to the target knowledge base or category."
            ],
        ], "in")
            //->requireOneOf(["knowledgeBaseID", "knowledgeCategoryID"])
            ->setDescription("Get a navigation-friendly category hierarchy.");
        $out = $this->schema([":a" => $this->categoryNavigationFragment()], "out");

        $query = $in->validate($query);

        $tree = $this->knowledgeCategoryModel->sectionChildren($query["knowledgeCategoryID"]);
        foreach ($tree as &$row) {
            $row = $this->normalizeOutput($row);
        }

        $result = $out->validate($tree);
        return $result;
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
