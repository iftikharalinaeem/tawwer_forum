<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\Schema\Schema;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Utility\InstanceValidatorSchema;

trait KnowledgeCategoriesApiSchemes {
    /** @var Schema $knowledgeCategoryPostSchema */
    private $knowledgeCategoryPostSchema;

    /** @var Schema $idParamSchema */
    private $idParamSchema;

    /**
     * Get a schema representing all available fields for a knowledge category.
     *
     * @return Schema
     */
    private function fullSchema(): Schema {
        return Schema::parse([
            "knowledgeCategoryID" => [
                "description" => "Unique knowledge category ID.",
                "type" => "integer",
            ],
            "breadcrumbs:a?" => new InstanceValidatorSchema(Breadcrumb::class),
            "name" => [
                "description" => "Name for the category.",
                "length" => 255,
                "type" => "string",
            ],
            "parentID" => [
                "allowNull" => true,
                "description" => "Unique ID of the parent for a category.",
                "type" => "integer",
            ],
            "knowledgeBaseID" => [
                "allowNull" => true,
                "description" => "Knowledge base ID for a category.",
                "type" => "integer",
            ],
            "sortChildren" => [
                "allowNull" => true,
                "description" => "Sort order for contents of the category.",
                "enum" => ["name", "dateInserted", "dateInsertedDesc", "manual"],
                "type" => "string",
            ],
            "sort" => [
                "allowNull" => true,
                "description" => "Sort weight of the category. Used when sorting the parent category's contents.",
                "type" => "integer",
            ],
            "insertUserID" => [
                "description" => "Unique ID of the user who originally created the knowledge category.",
                "type" => "integer",
            ],
            "dateInserted:dt" => [
                "description" => "When the knowledge category was created.",
                "type" => "datetime",
            ],
            "updateUserID:i" => [
                "description" => "Unique ID of the last user to update the knowledge category.",
                "type" => "integer",
            ],
            "dateUpdated:dt" => [
                "description" => "When the knowledge category was last updated.",
                "type" => "datetime",
            ],
            "lastUpdatedArticleID" => [
                "allowNull" => true,
                "description" => "Unique ID of the last article to be updated in the category.",
                "type" => "integer",
            ],
            "lastUpdatedUserID" => [
                "allowNull" => true,
                "description" => "Unique ID of the last user to update an article in the category.",
                "type" => "integer",
            ],
            "articleCount" => [
                "description" => "Total articles in the category.",
                "type" => "integer",
            ],
            "articleCountRecursive" => [
                "description" => "Aggregate total of all articles in the category and its children.",
                "type" => "integer",
            ],
            "childCategoryCount" => [
                "description" => "Total child categories.",
                "type" => "integer",
            ],
            "url" => [
                "description" => "Full URL to the knowledge category.",
                "type" => "string",
            ],
            "foreignID?" => [
                "description" => "Foreign ID to some external resource.",
                "type" => "string",
                "maxLength" => 32,
                "allowNull" => true,
            ],
        ]);
    }


    /**
     * Get an ID-only knowledge category schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema(string $type = "in"): Schema {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse([
                    "id:i" => "Knowledge category ID.",
                    "locale" => [
                        "description" => "Locale to represent content in.",
                        "type" => "string",
                        "default" => "en"
                    ],
                ]),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * Get a knowledge category schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function knowledgeCategoryPostSchema(string $type = "in"): Schema {
        if ($this->knowledgeCategoryPostSchema === null) {
            $this->knowledgeCategoryPostSchema = $this->schema(
                Schema::parse([
                    "name",
                    "parentID",
                    "knowledgeBaseID",
                    "sort?",
                    "sortChildren?",
                    "foreignID?"
                ])->add($this->fullSchema()),
                "KnowledgeCategoryPost"
            );
        }

        return $this->schema($this->knowledgeCategoryPostSchema, $type);
    }

    /**
     * Get a knowledge root category schema with minimal patch fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function knowledgeRootCategoryPatchSchema(string $type = "in"): Schema {
        $schema = $this->schema(
            Schema::parse([
                "foreignID"
            ])->add($this->fullSchema()),
            "KnowledgeRootCategoryPatch"
        );
        return $this->schema($schema, $type);
    }
}
