<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\SphinxTrait;

/**
 * Endpoint for the virtual "knowledge" resource.
 */
class KnowledgeApiController extends AbstractApiController {
    use SphinxTrait;

    /** @var Schema */
    private $searchResultItemSchema;

    /**
     * Get a schema with limited fields for representing a knowledge category row.
     *
     * @return Schema
     */
    public function categoryNavigationFragment(): Schema {
        if ($this->searchResultItemSchema === null) {
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
                ]
            ];

            $this->searchResultItemSchema = $this->schema($schema, "searchResultItemSchema");
        }
        return $this->searchResultItemSchema;
    }

    public function get_search(array $query = []): array {
        $this->permission("knowledge.kb.view");


        $in = $this->schema($this->defaultSchema(), "in")
            ->setDescription("Get a navigation-friendly category hierarchy flat mode.");
        $out = $this->schema([":a" => $this->categoryNavigationFragment()], "out");

        $query = $in->validate($query);


        $sphinx = $this->sphinxClient();

        if (isset($query['knowledgeCategoryID'])) {
            $sphinx->setFilter('knowledgeCategoryID', $query['knowledgeCategoryID']);
        }

        echo(__CLASS__.':'.__METHOD__.':'.__LINE__."\n");
        $result = $this->sphinxSearch($sphinx, '',['vanilla_dev2_KnowledgeArticle']);

        //$searchResults = ;

        $result = $out->validate($result);
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
            "titleKeywords:s?" => "Keywords to search against article title.",
            "bodyKeywords:s?" => "Keywords to search against article body.",
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
