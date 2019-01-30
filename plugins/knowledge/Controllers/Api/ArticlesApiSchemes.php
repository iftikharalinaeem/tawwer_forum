<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\Schema\Schema;
use Vanilla\Knowledge\Models\ArticleDraft;
use Vanilla\Knowledge\Models\ArticleModel;

/**
 * ArticlesApiController schemes
 */
trait ArticlesApiSchemes {

    /** @var Schema */
    private $articleFragmentSchema;

    /** @var Schema */
    private $articlePostSchema;

    /** @var Schema */
    private $articleSchema;

    /** @var Schema */
    private $articleSimpleSchema;

    /** @var Schema */
    private $idParamSchema;

    /** @var Schema */
    private $articleBodySchema;

    /** @var Schema */
    private $articleDraftBodySchema;


    /**
     * Get an article schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function articlePostSchema(string $type = ""): Schema {
        if ($this->articlePostSchema === null) {
            $this->articlePostSchema = $this->schema(
                Schema::parse([
                    "knowledgeCategoryID",
                    "body?",
                    "format?",
                    "name?",
                    "locale?",
                    "sort?",
                    "draftID?" => [
                        "type" => "integer",
                        "description" => "Unique ID of a draft to remove upon updating an article.",
                    ]
                ])->add($this->fullSchema()),
                "ArticlePost"
            );
        }

        return $this->schema($this->articlePostSchema, $type);
    }

    /**
     * Get an article schema with minimal fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function articleFragmentSchema(string $type = ""): Schema {
        if ($this->articleFragmentSchema === null) {
            $this->articleFragmentSchema = $this->schema(
                Schema::parse([
                    "articleID",
                    "knowledgeCategoryID",
                    "sort",
                    "url",
                    "name",
                    "excerpt",
                    "insertUser",
                    "dateInserted",
                    "updateUser",
                    "dateUpdated",
                ])->add($this->fullSchema()),
                "ArticleFragment"
            );
        }

        return $this->schema($this->articleFragmentSchema, $type);
    }

    /**
     * Get the full schema for an article. This includes current revision fields.
     *
     * @param string $type
     * @return Schema
     */
    public function articleSchema(string $type = ""): Schema {
        if ($this->articleSchema === null) {
            $this->articleSchema = $this->schema(Schema::parse([
                "articleID",
                "knowledgeCategoryID",
                "knowledgeBaseID",
                "name",
                "body",
                "outline",
                "excerpt",
                "seoName",
                "seoDescription",
                "slug",
                "sort",
                "score",
                "views",
                "url",
                "insertUserID",
                "dateInserted",
                "updateUserID",
                "dateUpdated",
                "insertUser?",
                "updateUser?",
                "status",
                "locale",
            ])->add($this->fullSchema()), "Article");
        }
        return $this->schema($this->articleSchema, $type);
    }

    /**
     * Get a slimmed down article schema. No body is included, but an excerpt is optional.
     *
     * @param string $type
     * @return Schema
     */
    public function articleSimpleSchema(string $type = ""): Schema {
        if ($this->articleSimpleSchema === null) {
            $this->articleSimpleSchema = $this->schema(Schema::parse([
                "articleID",
                "knowledgeCategoryID",
                "knowledgeBaseID",
                "name",
                "excerpt?",
                "seoName",
                "seoDescription",
                "slug",
                "sort",
                "score",
                "views",
                "url",
                "insertUserID",
                "dateInserted",
                "updateUserID",
                "dateUpdated",
                "insertUser?",
                "updateUser?",
                "status",
            ])->add($this->fullSchema()), "ArticleSimple");
        }
        return $this->schema($this->articleSimpleSchema, $type);
    }

    /**
     * Get post/patch fields for a draft.
     *
     * @return array
     */
    private function draftPostSchema(): Schema {
        $result = Schema::parse([
            "recordID?",
            "parentRecordID?",
            "attributes",
            "body",
            "format"
        ])->add($this->fullDraftSchema());
        return $result;
    }

    /**
     * Get all available fields for a draft.
     *
     * @return Schema
     */
    private function fullDraftSchema(): Schema {
        $result = Schema::parse([
            "draftID" => [
                "description" => "The unique ID of the draft.",
                "type" => "integer",
            ],
            "recordType" => [
                "description" => "The type of record associated with this draft.",
                "type" => "string",
            ],
            "recordID" => [
                "allowNull" => true,
                "description" => "Unique ID of an existing record to associate with this draft.",
                "type" => "integer",
            ],
            "parentRecordID" => [
                "allowNull" => true,
                "description" => "The unique ID of the intended parent to this record.",
                "type" => "integer",
            ],
            "attributes:o" => "A free-form object containing all custom data for this draft.",
            "insertUserID" => [
                "description" => "Unique ID of the user who originally created the draft.",
                "type" => "integer",
            ],
            "dateInserted" => [
                "description" => "When the draft was created.",
                "type" => "datetime",
            ],
            "updateUserID" => [
                "description" => "Unique ID of the last user to update the draft.",
                "type" => "integer",
            ],
            "dateUpdated" => [
                "description" => "When the draft was last updated",
                "type" => "datetime",
            ],
            "body" => [
                "description" => "Content of article",
                "type" => "string",
            ],
            "format" => [
                "description" => 'Body content format: rich, text, html.',
                "default" => ArticleDraft::BODY_TYPE_RICH,
                "type" => "string",
                "enum" => [
                    ArticleDraft::BODY_TYPE_RICH,
                    ArticleDraft::BODY_TYPE_HTML,
                    ArticleDraft::BODY_TYPE_TEXT,
                    ArticleDraft::BODY_TYPE_MD,
                ]
            ],
            "excerpt" => [
                "description" => "Excerpt of article",
                "type" => "string",
            ],
        ]);
        return $result;
    }

    /**
     * Get a schema representing the combined available fields from articles and revisions.
     *
     * @return Schema
     */
    private function fullSchema(): Schema {
        return $this->fullArticleSchema()
            ->merge(Schema::parse([
                "status",
                "name",
                "format",
                "body",
                "bodyRendered",
                "locale",
            ])->add($this->fullRevisionSchema()));
    }

    /**
     * Get a schema representing an article.
     *
     * @return Schema
     */
    private function fullArticleSchema(): Schema {
        return Schema::parse([
            "articleID:i" => "Unique article ID.",
            "knowledgeCategoryID:i" => [
                "allowNull" => true,
                "Category the article belongs in.",
            ],
            "knowledgeBaseID:i?" => "Knowledge Base the article belongs to.",
            "seoName:s" => [
                "allowNull" => true,
                "description" => "SEO-optimized name for the article.",
            ],
            "seoDescription:s" => [
                "allowNull" => true,
                "description" => "SEO-optimized description of the article content.",
            ],
            "slug:s" => [
                "allowNull" => true,
                "description" => "URL slug",
            ],
            "sort:i" => [
                "allowNull" => true,
                "description" => "Manual sort order of the article.",
            ],
            "score:i" => "Score of the article.",
            "views:i" => "How many times the article has been viewed.",
            "url:s" => "Full URL to the article.",
            "insertUserID:i" => "Unique ID of the user who originally created the article.",
            "dateInserted:dt" => "When the article was created.",
            "updateUserID:i" => "Unique ID of the last user to update the article.",
            "dateUpdated:dt" => "When the article was last updated.",
            "insertUser?" => $this->getUserFragmentSchema(),
            "updateUser?" => $this->getUserFragmentSchema(),
            "status:s" => [
                'description' => "Article status.",
                'enum' => ArticleModel::getAllStatuses(),
            ],
            "excerpt:s?" => [
                "allowNull" => true,
                "description" => "Plain-text excerpt of the current article body.",
            ],
            "outline:a?" => Schema::parse([
                'ref:s' => 'Heading blot reference id. Ex: #title',
                'level:i' => 'Heading level',
                'text:s' => 'Heading text line',
            ]),
        ]);
    }

    /**
     * Get a schema representing an article revision.
     *
     * @return Schema
     */
    private function fullRevisionSchema(): Schema {
        return Schema::parse([
            "articleRevisionID:i" => "Unique article revision ID.",
            "articleID:i" => "Associated article ID.",
            "status:s" => [
                "allowNull" => true,
                "description" => "",
                "enum" => ["published"],
            ],
            "name:s" => [
                "allowNull" => true,
                "description" => "Title of the article.",
                "minLength" => 0,
            ],
            "format:s" => [
                "allowNull" => true,
                "enum" => ["text", "textex", "markdown", "wysiwyg", "html", "bbcode", "rich"],
                "description" => "Format of the raw body content.",
            ],
            "body:s" => [
                "allowNull" => true,
                "description" => "Body contents.",
                "minLength" => 0,
            ],
            "bodyRendered:s" => [
                "allowNull" => true,
                "description" => "Rendered body contents.",
            ],
            "locale:s" => [
                "allowNull" => true,
                "description" => "Locale the article was written in.",
            ],
            "insertUserID:i" => "Unique ID of the user who originally created the article.",
            "dateInserted:dt" => "When the article was created.",
            "insertUser?" => $this->getUserFragmentSchema(),
            "updateUser?" => $this->getUserFragmentSchema(),
        ]);
    }

    /**
     * Get an ID-only article schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema(string $type = "in"): Schema {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = Schema::parse(["id:i" => "The article ID."]);
        }
        return $this->schema($this->idParamSchema, $type);
    }
}
