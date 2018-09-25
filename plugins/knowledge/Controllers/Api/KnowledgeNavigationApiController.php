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

        $data = [
            [
                "name" => "Suspendisse Gravida Turpis",
                "displayType" => null,
                "isSection" => false,
                "url" => url("/knowledge/category/suspendisse-gravida-turpis-2", true),
                "parentID" => 1,
                "recordID" => 2,
                "recordType" => "knowledgeCategory",
                "children" => [
                    [
                        "name" => "Vel Dui",
                        "displayType" => null,
                        "isSection" => false,
                        "url" => url("/knowledge/category/vel-dui-3", true),
                        "parentID" => 2,
                        "recordID" => 3,
                        "recordType" => "knowledgeCategory",
                        "children" => [],
                    ],
                    [
                        "name" => "Ultricies Curabitur",
                        "displayType" => null,
                        "isSection" => false,
                        "url" => url("/knowledge/category/ultricies-curabitur-4", true),
                        "parentID" => 2,
                        "recordID" => 4,
                        "recordType" => "knowledgeCategory",
                        "children" => [],
                    ],
                    [
                        "name" => "Eget Ante Porta",
                        "displayType" => null,
                        "isSection" => false,
                        "url" => url("/knowledge/category/eget-ante-porta-5", true),
                        "parentID" => 2,
                        "recordID" => 5,
                        "recordType" => "knowledgeCategory",
                        "children" => [],
                    ],
                ],
            ],
            [
                "name" => "Enim Varius",
                "displayType" => null,
                "isSection" => false,
                "url" => url("/knowledge/category/enim-varius-6", true),
                "parentID" => 1,
                "recordID" => 6,
                "recordType" => "knowledgeCategory",
                "children" => [],
            ],
            [
                "name" => "Ullamcorper",
                "displayType" => "guide",
                "isSection" => true,
                "url" => url("/knowledge/category/ullamcorper-7", true),
                "parentID" => 1,
                "recordID" => 7,
                "recordType" => "knowledgeCategory",
                "children" => [],
            ],
            [
                "name" => "Etiam Convallis",
                "displayType" => null,
                "isSection" => false,
                "url" => url("/knowledge/category/etiam-convallis-8", true),
                "parentID" => 1,
                "recordID" => 8,
                "recordType" => "knowledgeCategory",
                "children" => [
                    [
                        "name" => "Ligula Sed Orci ",
                        "displayType" => null,
                        "isSection" => false,
                        "url" => url("/knowledge/category/ligula-sed-orci-9", true),
                        "parentID" => 8,
                        "recordID" => 9,
                        "recordType" => "knowledgeCategory",
                        "children" => [],
                    ],
                    [
                        "name" => "Varius in Iaculis",
                        "displayType" => null,
                        "isSection" => false,
                        "url" => url("/knowledge/category/varius-in-iaculis-10", true),
                        "parentID" => 8,
                        "recordID" => 10,
                        "recordType" => "knowledgeCategory",
                        "children" => [],
                    ],
                ],
            ],
            [
                "name" => "Pellentesque Nulla",
                "displayType" => "search",
                "isSection" => true,
                "url" => url("/knowledge/category/pellentesque-nulla-11", true),
                "parentID" => 1,
                "recordID" => 11,
                "recordType" => "knowledgeCategory",
                "children" => [],
            ],
        ];

        $result = $out->validate($data);
        return $result;
    }
}
