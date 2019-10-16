<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Vanilla\Knowledge\Models\Navigation;
use Vanilla\Knowledge\Controllers\Api\KnowledgeNavigationApiController;

trait KnowledgeNavigationApiSchemes {
    /** @var Schema */
    private $categoryNavigationFragment;

    /** @var Schema */
    private $navigationTreeSchema;

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
    public function getFragmentSchema(): array {
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
    public function defaultSchema() {
        return [
            "knowledgeCategoryID?" => [
                "description" => "Unique ID of a knowledge category to get navigation for. Only direct children of this category will be included.",
                "type" => "integer",
            ],
            "recordType?" => [
                "default" => KnowledgeNavigationApiController::FILTER_RECORD_TYPE_ALL,
                "description" => "The type of record to limit navigation results to.",
                "enum" => [
                    KnowledgeNavigationApiController::FILTER_RECORD_TYPE_CATEGORY,
                    KnowledgeNavigationApiController::FILTER_RECORD_TYPE_ALL
                ],
                "type" => "string",
            ],
            "locale?" => [
                "description" => "Locale to represent content in.",
                "type" => "string",
            ],
            "only-translated?" => [
                "description" => "If transalted revisions does not exist don not return related article.",
                "type" => "boolean",
                "default" => false
            ],
        ];
    }
}
