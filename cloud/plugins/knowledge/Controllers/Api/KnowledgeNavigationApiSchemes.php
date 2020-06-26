<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\Schema\Schema;
use Vanilla\Knowledge\Models\KnowledgeNavigationModel;

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
            $this->categoryNavigationFragment = Schema::parse($this->getNavFragmentSchema());
        }
        return $this->categoryNavigationFragment;
    }

    /**
     * Get navigation fragment schema attributes array
     *
     * @return array
     */
    public function getNavFragmentSchema(): array {
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
                "enum" => [KnowledgeNavigationModel::RECORD_TYPE_CATEGORY, KnowledgeNavigationModel::RECORD_TYPE_ARTICLE],
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
            $schema = Schema::parse($this->getNavFragmentSchema())
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
     * @return Schema
     */
    private function navInputSchema(): Schema {
        return Schema::parse([
            "knowledgeBaseID?" => [
                "description" => "Unique ID of a knowledge base to get navigation for. Only items from this knowledge base will be included.",
                "type" => "integer",
            ],
            "locale?" => [
                "description" => "Locale to represent content in.",
                "type" => "string",
            ],
            "only-translated?" => [
                "description" => "If set, un-translated items will not be returned.",
                "type" => "boolean",
                "default" => false
            ],
        ]);
    }
}
