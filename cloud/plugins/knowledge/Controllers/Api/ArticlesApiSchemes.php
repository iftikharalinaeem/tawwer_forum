<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\Schema\Schema;
use Vanilla\Formatting\Formats\BBCodeFormat;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\Formats\MarkdownFormat;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Formats\TextExFormat;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Formatting\Formats\WysiwygFormat;
use Vanilla\Knowledge\Models\ArticleDraft;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleReactionModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\SchemaFactory;
use Vanilla\Utility\InstanceValidatorSchema;

/**
 * ArticlesApiController schemes
 */
trait ArticlesApiSchemes {

    /** @var Schema */
    private $articleFragmentSchema;

    /** @var Schema */
    private $articlePostSchema;

    /** @var Schema */
    private $articlePatchSchema;

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

    /** @var Schema */
    private $discussionArticleSchema;

    /** @var Schema */
    private $articleAliasesSchema;

    /** @var Schema */
    private $firstArticleRevisionPatchSchema;
    /**
     * Get a schema representing a discussion in an easy-to-consume format for creating an article.
     *
     * @param string $type
     * @return Schema
     */
    public function discussionArticleSchema(string $type = ""): Schema {
        if ($this->discussionArticleSchema === null) {
            $this->discussionArticleSchema = $this->schema(
                Schema::parse([
                    "name" => [
                        "description" => "Discussion title.",
                        "type" => "string",
                    ],
                    "body" => [
                        "description" => "Full discussion body contents.",
                        "type" => ["array", "string"],
                    ],
                    "format" => [
                        "description" => "Post format.",
                        "type" => "string",
                    ],
                    "url" => [
                        "description" => "Full URL to the discussion.",
                        "type" => "string",
                    ],
                    "acceptedAnswers?" => [
                        "items" => [
                            "type" => "object",
                            "properties" => [
                                "body" => [
                                    "type" => ["array", "string"],
                                    "description" => "Full answer body contents.",
                                ],
                                "format" => [
                                    "description" => "Answer post format.",
                                    "type" => "string",
                                ],
                                "url" => [
                                    "description" => "Full URL to the answer.",
                                    "type" => "string",
                                ],
                            ],
                        ],
                        "type" => "array",
                    ]
                ]),
                "DiscussionArticle"
            );
        }

        return $this->schema($this->discussionArticleSchema, $type);
    }

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
                    "body",
                    "format",
                    "name",
                    "locale?",
                    "sort?",
                    "discussionID?",
                    "draftID?" => [
                        "type" => "integer",
                        "description" => "Unique ID of a draft to remove upon creating an article.",
                    ],
                    "foreignID?",
                    "dateInserted?",
                    "dateUpdated?",
                    "insertUserID?",
                    "fileRehosting?" => $this->fileRehostSchema(),
                ])->add($this->fullSchema()),
                "ArticlePost"
            );
        }

        return $this->schema($this->articlePostSchema, $type);
    }

    /**
     * Get an article schema with minimal editable fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function articlePatchSchema(string $type = ""): Schema {
        if ($this->articlePatchSchema === null) {
            $this->articlePatchSchema = $this->schema(
                Schema::parse([
                    "knowledgeCategoryID?",
                    "format?",
                    "body?",
                    "name?",
                    "locale?",
                    "validateLocale?",
                    "sort?",
                    "discussionID?",
                    "previousRevisionID?",
                    "draftID?" => [
                        "type" => "integer",
                        "description" => "Unique ID of a draft to remove upon updating an article.",
                    ],
                    "foreignID?",
                    "dateInserted?",
                    "dateUpdated?",
                    "insertUserID?",
                    "updateUserID?",
                    "fileRehosting?" => $this->fileRehostSchema(),
                ])->add($this->fullSchema()),
                "ArticlePatch"
            );
        }

        return $this->schema($this->articlePatchSchema, $type);
    }

    /**
     * @return Schema
     */
    public function fileRehostSchema(): Schema {
        return Schema::parse([
            'enabled:b?',
            'requestHeaders:a?',
        ]);
    }

    /**
     * Get an article schema with minimal editable fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function firstArticleRevisionPatchSchema(string $type = ""): Schema {
        if ($this->firstArticleRevisionPatchSchema === null) {
            $this->firstArticleRevisionPatchSchema = $this->schema(
                Schema::parse([
                    "format",
                    "body",
                    "name",
                    "locale",
                    "validateLocale?",
                    "dateInserted?",
                    "sort?",
                    "discussionID?",
                    "previousRevisionID?",
                    "draftID?" => [
                        "type" => "integer",
                        "description" => "Unique ID of a draft to remove upon updating an article.",
                            ]
                        ])->add($this->fullSchema()),
                "firstArticleRevisionPatch"
            );
        }

        return $this->schema($this->firstArticleRevisionPatchSchema, $type);
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
            $this->articleSchema = Schema::parse([
                "articleID",
                "articleRevisionID",
                "knowledgeCategoryID",
                "breadcrumbs?",
                "knowledgeBaseID",
                "name",
                "body",
                "outline",
                "excerpt",
                "seoDescription",
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
                "reactions?",
                "aliases?",
                "status",
                "featured",
                "dateFeatured",
                "locale",
                "translationStatus",
                "foreignID"
            ])->add($this->fullSchema());
        }
        return $this->articleSchema;
    }

    /**
     * Get aliases for an article.
     *
     * @param string $type
     * @return Schema
     */
    public function articleAliasesSchema(string $type = ""): Schema {
        if ($this->articleAliasesSchema === null) {
            $this->articleAliasesSchema = $this->schema(Schema::parse([
                "articleID",
                "knowledgeCategoryID",
                "knowledgeBaseID",
                "aliases",
                "status",
                "locale",
            ])->add($this->fullSchema()), "Article");
        }
        return $this->schema($this->articleAliasesSchema, $type);
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
                "locale",
                "translationStatus"
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
            "insertUser?" => SchemaFactory::get(UserFragmentSchema::class),
            "dateInserted" => [
                "description" => "When the draft was created.",
                "type" => "datetime",
            ],
            "updateUserID" => [
                "description" => "Unique ID of the last user to update the draft.",
                "type" => "integer",
            ],
            "updateUser?" => SchemaFactory::get(UserFragmentSchema::class),
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
                "default" => RichFormat::FORMAT_KEY,
                "type" => "string",
                "enum" => [
                    RichFormat::FORMAT_KEY,
                    BBCodeFormat::FORMAT_KEY,
                    MarkdownFormat::FORMAT_KEY,
                    HtmlFormat::FORMAT_KEY,
                    WysiwygFormat::FORMAT_KEY,
                    TextFormat::FORMAT_KEY,
                    TextExFormat::FORMAT_KEY,
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
                "excerpt",
                "outline",
                "bodyRendered",
                "locale",
                "translationStatus",
                "validateLocale"
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
                "allowNull" => false,
                "Category the article belongs in.",
            ],
            "breadcrumbs:a?" => new InstanceValidatorSchema(Breadcrumb::class),
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
            "featured:b",
            "score:i" => "Score of the article.",
            "views:i" => "How many times the article has been viewed.",
            "url:s" => "Full URL to the article.",
            "insertUserID:i" => "Unique ID of the user who originally created the article.",
            "dateInserted:dt" => "When the article was created.",
            "updateUserID:i" => "Unique ID of the last user to update the article.",
            "dateUpdated:dt" => "When the article was last updated.",
            "insertUser?" => SchemaFactory::get(UserFragmentSchema::class),
            "updateUser?" => SchemaFactory::get(UserFragmentSchema::class),
            "status:s" => [
                'description' => "Article status.",
                'enum' => ArticleModel::getAllStatuses(),
            ],
            "discussionID:i?" => [
                "allowNull" => false,
                "description" => "Discussion ID to link article url as discussion canonical url.",
            ],
            "reactions:a?" => Schema::parse([
                'reactionType:s' => [
                    'enum' => ArticleReactionModel::getReactionTypes(),
                ],
                'yes:i' => 'Positive reactions count of reaction type',
                'no:i' => 'Negative reactions count of reaction type',
                'total:i' => 'Total reactions count of reaction type',
                'userReaction' => [
                    'enum' => ArticleReactionModel::getHelpfulReactions(),
                ]
            ]),
            "aliases:a?" => ['items' => ['type' => 'string']],
            "translationStatus:s" => [
                "allowNull" => false,
                "description" => "Translation status of revision. Ex: up-to-date, out-of-date,",
                "enum" => ArticleRevisionModel::getTranslationStatuses()
            ],
            "foreignID:s" => [
                "description" => "Foreign ID to some external resource.",
                "type" => "string",
                "maxLength" => 32,
                "allowNull" => true,
            ],
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
            "previousRevisionID:i" => "Article's last known revision ID. Needs to be passed when patching an existing article.",
            "articleID:i" => "Associated article ID.",
            "status:s" => [
                "allowNull" => true,
                "description" => "",
                "enum" => ["published"],
            ],
            "format:s" => [
                "enum" => ["text", "textex", "markdown", "wysiwyg", "html", "bbcode", "rich"],
                "description" => "Format of the raw body content.",
            ],
            "name:s" => [
                "description" => "Title of the article.",
                "minLength" => 1,
            ],
            "body:s" => [
                "description" => "Body contents.",
                "minLength" => 1,
            ],
            "bodyRendered:s" => [
                "allowNull" => true,
                "description" => "Rendered body contents.",
            ],
            "locale:s" => [
                "allowNull" => true,
                "description" => "Locale the article was written in.",
            ],
            "excerpt:s?" => [
                "allowNull" => true,
                "description" => "Plain-text excerpt of the current article body.",
            ],
            "outline:a?" => Schema::parse([
                'ref:s' => 'Heading blot reference id. Ex: #title',
                'level:i' => 'Heading level',
                'text:s' => [
                    'type' => 'string',
                    'minLength' => 0
                ],
            ]),
            "seoImage:s?" => [
                "allowNull" => true,
            ],
            "translationStatus:s" => [
                "allowNull" => false,
                "description" => "Translation status of revision. Ex: up-to-date, out-of-date,",
                "enum" => ArticleRevisionModel::getTranslationStatuses()
            ],
            "insertUserID:i" => "Unique ID of the user who originally created the article.",
            "dateInserted:dt" => "When the article was created.",
            "dateUpdated:dt" => "When the article was updated.",
            "insertUser?" => SchemaFactory::get(UserFragmentSchema::class),
            "updateUser?" => SchemaFactory::get(UserFragmentSchema::class),
            "featured:b",
            "dateFeatured?:dt",
            "validateLocale:b?" => [
                "description" => "Apply validation to locale.",
                "type" => "boolean",
                "default" => true
            ]
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
            $this->idParamSchema = Schema::parse([
                "id:i" => "The article ID.",
                "locale:s?" => "Locale of the article",
                "only-translated:b?" => [
                    "description" => "If translated revisions does not exist don not return related article.",
                    "type" => "boolean",
                    "default" => false
                ],
            ]);
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * Validate the value of aliases array when put aliases. Compatible with \Garden\Schema\Schema.
     *
     * @param array $aliases
     * @param \Garden\Schema\ValidationField $validationField
     * @return bool
     * @throws \Garden\Schema\ValidationException If the selected row fails output validation.
     */
    public static function validateAliases(array $aliases, \Garden\Schema\ValidationField $validationField): bool {
        $valid = true;
        foreach ($aliases as $alias) {
            $encoded = implode('/', array_map(
                function ($str) {
                    return rawurlencode(rawurldecode($str));
                },
                explode('/', $alias)
            ));
            if ($alias !== $encoded) {
                $validationField->getValidation()->addError(
                    $validationField->getName(),
                    "Alias is not valid url: '".$alias."'. Try: '".$encoded."'"
                );
                $valid = false;
            }
        }
        return $valid;
    }
}
