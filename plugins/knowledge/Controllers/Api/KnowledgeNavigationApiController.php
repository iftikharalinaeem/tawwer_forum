<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;

/**
 * Endpoint for the virtual "knowledge navigation" resource.
 */
class KnowledgeNavigationApiController extends AbstractApiController {

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
            "knowledgeCategoryID:i?" => "Unique ID of a knowledge category to get navigation for. Results will be relative to this value.",
            "maxDepth:i" => [
                "default" => 2,
                "description" => "The maximum depth results should be, relative to the target knowledge base or category."
            ],
        ], "in")
            ->requireOneOf(["knowledgeBaseID", "knowledgeCategoryID"])
            ->setDescription("Get a navigation-friendly category hierarchy.");
        $out = $this->schema([":a" => [
            "items" => ["type" => "object"]
        ]], "out");

        $query = $in->validate($query);

        $data2 = [
            "name" => "Predator Urine",
            "displayType" => null,
            "isSection" => false,
            "url" => url("/knowledge/category/predator-urine-2", true),
            "parentID" => 1,
            "recordID" => 2,
            "recordType" => "knowledgeCategory",
            "children" => [
                [
                    "name" => "Coyote Urine",
                    "displayType" => null,
                    "isSection" => false,
                    "url" => url("/knowledge/category/coyote-urine-3", true),
                    "parentID" => 2,
                    "recordID" => 3,
                    "recordType" => "knowledgeCategory",
                    "children" => [],
                ],
                [
                    "name" => "Fox Urine",
                    "displayType" => null,
                    "isSection" => false,
                    "url" => url("/knowledge/category/fox-urine-4", true),
                    "parentID" => 2,
                    "recordID" => 4,
                    "recordType" => "knowledgeCategory",
                    "children" => [],
                ],
                [
                    "name" => "Bobcat Urine",
                    "displayType" => null,
                    "isSection" => false,
                    "url" => url("/knowledge/category/bobcat-urine-5", true),
                    "parentID" => 2,
                    "recordID" => 5,
                    "recordType" => "knowledgeCategory",
                    "children" => [],
                ],
            ],
        ];

        $data8 = [
            "name" => "Prey Animals",
            "displayType" => null,
            "isSection" => false,
            "url" => url("/knowledge/category/prey-animals-8", true),
            "parentID" => 1,
            "recordID" => 8,
            "recordType" => "knowledgeCategory",
            "children" => [
                [
                    "name" => "Armadillos ",
                    "displayType" => null,
                    "isSection" => false,
                    "url" => url("/knowledge/category/armadillos-9", true),
                    "parentID" => 8,
                    "recordID" => 9,
                    "recordType" => "knowledgeCategory",
                    "children" => [],
                ],
                [
                    "name" => "Chipmunks",
                    "displayType" => null,
                    "isSection" => false,
                    "url" => url("/knowledge/category/chipmunks-10", true),
                    "parentID" => 8,
                    "recordID" => 10,
                    "recordType" => "knowledgeCategory",
                    "children" => [],
                ],
            ],
        ];

        $data1 = [
            "name" => "Top Level category",
            "displayType" => null,
            "isSection" => false,
            "url" => url("/knowledge/category/chipmunks-1", true),
            "parentID" => -1,
            "recordID" => 1,
            "recordType" => "knowledgeCategory",
            "children" => [
                $data2,
                [
                    "name" => "P-Gel",
                    "displayType" => null,
                    "isSection" => false,
                    "url" => url("/knowledge/category/p-gel-6", true),
                    "parentID" => 1,
                    "recordID" => 6,
                    "recordType" => "knowledgeCategory",
                    "children" => [],
                ],
                [
                    "name" => "P-Cover Granules",
                    "displayType" => "guide",
                    "isSection" => true,
                    "url" => url("/knowledge/category/p-cover-granules-7", true),
                    "parentID" => 1,
                    "recordID" => 7,
                    "recordType" => "knowledgeCategory",
                    "children" => [],
                ],
                $data8,
                [
                    "name" => "Dispensers",
                    "displayType" => "search",
                    "isSection" => true,
                    "url" => url("/knowledge/category/dispensers-11", true),
                    "parentID" => 1,
                    "recordID" => 11,
                    "recordType" => "knowledgeCategory",
                    "children" => [],
                ],
            ],
        ];

        if ($query['knowledgeCategoryID'] === 2) {
            $dataset = $data2;
        } elseif ($query['knowledgeCategoryID'] === 8) {
            $dataset = $data8;
        } else {
            $dataset = $data1;
        }

        $result = $out->validate($dataset['children']);
        return $result;
    }
}
