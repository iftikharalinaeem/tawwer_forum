<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\Schema\Schema;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * KnowledgeBasesApiController schemes
 */
trait KnowledgeBasesApiSchemes {

    /** @var Schema */
    private $knowledgeBasePostSchema;

    /** @var Schema */
    private $idParamSchema;

    /**
     * Get a schema representing all available fields for a knowledge base.
     *
     * @return Schema
     */
    private function fullSchema(): Schema {
        return Schema::parse([
            "knowledgeBaseID" => [
                "description" => "Unique knowledge base ID.",
                "type" => "integer",
            ],
            "name" => [
                "description" => "Name for the knowledge base.",
                "minLength" => 1,
                "type" => "string",
            ],
            "description" => [
                "description" => "Description for the knowledge base.",
                "minLength" => 1,
                "maxLength" => 300,
                "type" => "string",
            ],
            "sortArticles" => [
                "allowNull" => true,
                "description" => "Sort order for articles of the knowledge base.",
                "enum" => KnowledgeBaseModel::getAllSorts(),
                "type" => "string",
            ],
            "insertUserID" => [
                "description" => "Unique ID of the user who originally created the knowledge base.",
                "type" => "integer",
            ],
            "dateInserted:dt" => [
                "description" => "When the knowledge base was created.",
                "type" => "datetime",
            ],
            "updateUserID:i" => [
                "description" => "Unique ID of the last user to update the knowledge base.",
                "type" => "integer",
            ],
            "dateUpdated:dt" => [
                "description" => "When the knowledge base was last updated.",
                "type" => "datetime",
            ],
            "countArticles" => [
                "description" => "Total articles in the knowledge base.",
                "type" => "integer",
            ],
            "countCategories" => [
                "description" => "Total categories in the knowledge base.",
                "type" => "integer",
            ],
            "urlCode" => [
                "description" => "URL code to the knowledge base.",
                "minLength" => 1,
                "type" => "string",
            ],
            "url" => [
                "description" => "Full URL to the knowledge base.",
                "type" => "string",
            ],
            "icon" => [
                "description" => "Full URL to the icon of knowledge base.",
                "type" => "string",
            ],
            "sourceLocale" => [
                "description" => "sourceLocale of knowledge base.",
                "type" => "string",
            ],
            "viewType" => [
                "allowNull" => true,
                "description" => "Sort order for articles of the knowledge base.",
                "enum" => KnowledgeBaseModel::getAllTypes(),
                "default" => KnowledgeBaseModel::TYPE_GUIDE,
                "type" => "string",
            ],
            "rootCategoryID:i" => [
                "description" => "Root knowledge category ID of knowledge base.",
                "type" => "integer",
            ],
            "defaultArticleID:i" => [
                "description" => "Default article ID of knowledge base.",
                "type" => "integer",
                "allowNull" => true
            ],
        ]);
    }

    /**
     * Get a knowledge base POST schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function knowledgeBasePostSchema(string $type = "in"): Schema {
        if ($this->knowledgeBasePostSchema === null) {
            $this->knowledgeBasePostSchema = $this->schema(
                Schema::parse([
                    "name",
                    "description",
                    "icon?",
                    "sourceLocale?",
                    "viewType?",
                    "sortArticles?",
                    "urlCode",
                ])->add($this->fullSchema()),
                "KnowledgeBasePost"
            );
        }

        return $this->schema($this->knowledgeBasePostSchema, $type);
    }

    /**
     * Get an ID-only knowledge base schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema(string $type = "in"): Schema {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(["id:i" => "Knowledge base ID."]),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }
}
