<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Exception;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Gdn_Format;
use UserModel;
use Vanilla\Models\DraftModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\Formatting\Quill\BlotGroup;
use Vanilla\Formatting\Quill\Blots\Lines\HeadingTerminatorBlot;
use Vanilla\Formatting\Quill\Parser;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;

/**
 * API controller for managing the articles resource.
 */
class ArticlesApiController extends AbstractKnowledgeApiController {

    // Maximum length before article excerpts are truncated.
    const EXCERPT_MAX_LENGTH = 325;

    /** @var Schema */
    private $articleFragmentSchema;

    /** @var Schema */
    private $articlePostSchema;

    /** @var Schema */
    private $articleSchema;

    /** @var Schema */
    private $articleSimpleSchema;

    /** @var ArticleModel */
    private $articleModel;

    /** @var ArticleRevisionModel */
    private $articleRevisionModel;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var DraftModel */
    private $draftModel;

    /** @var Schema */
    private $idParamSchema;

    /** @var UserModel */
    private $userModel;

    /**
     * ArticlesApiController constructor.
     *
     * @param ArticleModel $articleModel
     * @param ArticleRevisionModel $articleRevisionModel
     * @param UserModel $userModel
     * @param DraftModel $draftModel
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     */
    public function __construct(
        ArticleModel $articleModel,
        ArticleRevisionModel $articleRevisionModel,
        UserModel $userModel,
        DraftModel $draftModel,
        KnowledgeCategoryModel $knowledgeCategoryModel
    ) {
        $this->articleModel = $articleModel;
        $this->articleRevisionModel = $articleRevisionModel;
        $this->draftModel = $draftModel;
        $this->userModel = $userModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
    }

    /**
     * Get an article by its numeric ID.
     *
     * @param int $id Article ID.
     * @param bool $includeRevision
     * @return array
     * @throws NotFoundException If the article could not be found.
     * @throws ValidationException If the result fails schema validation.
     */
    private function articleByID(int $id, bool $includeRevision = false): array {
        try {
            if ($includeRevision) {
                $article = $this->articleModel->getIDWithRevision($id);
            } else {
                $article = $this->articleModel->selectSingle(["articleID" => $id]);
            }
        } catch (NoResultsException $e) {
            throw new NotFoundException("Article");
        }
        return $article;
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
                "name",
                "body",
                "outline",
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
     * Delete an article draft.
     *
     * @param int $draftID
     * @return mixed
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws NotFoundException If the article draft could not be found.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws ValidationException If the output fails to validate against the schema.
     */
    public function delete_drafts(int $draftID) {
        $this->permission("signin.allow");

        $in = $this->schema([
            "draftID" => [
                "description" => "Target article draft ID.",
                "type" => "integer",
            ],
        ], "in")->setDescription("Delete an article draft.");
        $out = $this->schema([], "out");

        $draft = $this->draftByID($draftID);
        if ($draft["insertUserID"] !== $this->getSession()->UserID) {
            $this->permission("settings.manage");
        }
        $this->draftModel->delete(
            ["draftID" => $draft["draftID"]]
        );
    }

    /**
     * Get an article draft by its numeric ID.
     *
     * @param int $id Article ID.
     * @return array
     * @throws NotFoundException If the draft could not be found.
     * @throws ValidationException If the result fails schema validation.
     */
    private function draftByID(int $id): array {
        try {
            $draft = $this->draftModel->selectSingle([
                "draftID" => $id,
                "recordType" => "article",
            ]);
        } catch (NoResultsException $e) {
            throw new NotFoundException("Draft");
        }
        return $draft;
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
     * Handle GET requests to the root of the endpoint.
     *
     * @param int $id
     * @return array
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     * @throws NotFoundException If the article could not be found.
     * @throws ServerException If there was an error normalizing the output.
     */
    public function get(int $id) {
        $this->permission("knowledge.kb.view");

        $in = $this->idParamSchema()->setDescription("Get an article.");
        $out = $this->articleSchema("out");

        $article = $this->articleByID($id, true);
        $this->userModel->expandUsers(
            $article,
            ["insertUserID", "updateUserID"]
        );

        $article = $this->normalizeOutput($article);
        $result = $out->validate($article);
        return $result;
    }

    /**
     * Get a single article draft.
     *
     * @param int $draftID
     * @return mixed
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws NotFoundException If the article draft could not be found.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws ValidationException If the output fails to validate against the schema.
     */
    public function get_drafts(int $draftID) {
        $this->permission("articles.add");

        $in = $this->schema([
            "draftID" => [
                "description" => "Target article draft ID.",
                "type" => "integer",
            ],
        ], "in")->setDescription("Get a single article draft.");
        $out = $this->schema($this->fullDraftSchema(), "out");

        $draft = $this->draftByID($draftID);
        $result = $out->validate($draft);
        return $result;
    }

    /**
     * Get an article for editing.
     *
     * @param int $id
     * @return array
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws NotFoundException If the article could not be found.
     * @throws ValidationException If the output fails to validate against the schema.
     */
    public function get_edit(int $id): array {
        $this->permission("knowledge.articles.add");

        $this->idParamSchema()->setDescription("Get an article for editing.");
        $out = $this->schema(Schema::parse([
            "articleID",
            "knowledgeCategoryID",
            "sort",
            "name",
            "body",
            "format",
            "locale",
        ])->add($this->fullSchema()), "out");

        $article = $this->articleByID($id, true);
        $body = $article['body'];
        $article = $this->normalizeOutput($article);
        $article['body'] = $body;
        $result = $out->validate($article);
        return $result;
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

    /**
     * List published articles in a given knowledge category.
     *
     * @param array $query
     * @return \Garden\Web\Data
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     * @throws HttpException If a relevant ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function index(array $query = []) {
        $this->permission("knowledge.kb.view");

        $in = $this->schema([
            "expand?" => \Vanilla\ApiUtils::getExpandDefinition(["excerpt"]),
            "knowledgeCategoryID" => [
                "type" => "integer",
                "minimum" => 1,
            ],
            "limit" => [
                "default" => ArticleModel::LIMIT_DEFAULT,
                "minimum" => 1,
                "maximum" => 100,
                "type" => "integer",
            ],
            "order:s?" => [
                "description" => "Sort method for results.",
                "enum" => ["dateInserted", "dateUpdated", "sort"],
                "default" => "dateInserted",
            ],
            "page:i?" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
                "maximum" => 100,
            ],
        ], "in")->setDescription("List published articles in a given knowledge category.");
        $out = $this->schema([":a" => $this->articleSimpleSchema()], "out");

        $query = $in->validate($query);

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);
        $includeExcerpts = $this->isExpandField("excerpt", $query["expand"]);

        $options = [
            "includeBody" => $includeExcerpts,
            "limit" => $limit,
            "offset" => $offset,
            "orderFields" => $query["order"],
        ];
        switch ($query["order"]) {
            case "dateUpdated":
                $options["orderDirection"] = "desc";
                break;
            default:
                $options["orderDirection"] = "asc";
        }
        $rows = $this->articleModel->getWithRevision(
            ["knowledgeCategoryID" => $query["knowledgeCategoryID"]],
            $options
        );
        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row);
            if ($includeExcerpts) {
                $row["excerpt"] = $row["body"] ? sliceString(Gdn_Format::plainText($row["body"], "Html"), self::EXCERPT_MAX_LENGTH) : null;
            }
        }
        $this->userModel->expandUsers(
            $rows,
            ["insertUserID", "updateUserID"]
        );

        $result = $out->validate($rows);
        return new \Garden\Web\Data($result, [
            'paging' => \Vanilla\ApiUtils::morePagerInfo($result, "/api/v2/articles", $query, $in)
        ]);
    }

    /**
     * List article drafts.
     *
     * @param array $query
     * @return mixed
     * @throws HttpException If a relevant ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     */
    public function index_drafts(array $query) {
        $this->permission("articles.add");

        $in = $this->schema([
            "articleID?" => [
                "description" => "Unique ID article associated with a draft.",
                "type" => "integer",
                "x-filter" => [
                    "field" => "recordID",
                ],
            ],
            "insertUserID?" => [
                "description" => "Unique ID of the user who created the article draft.",
                "type" => "integer",
                "x-filter" => [
                    "field" => "insertUserID",
                ],
            ],
        ], "in")->setDescription("List article drafts.")->requireOneOf(["articleID", "insertUserID"]);
        $out = $this->schema([
            ":a" => $this->fullDraftSchema(),
        ], "out");

        $query = $in->validate($query);

        $where = ["recordType" => "article"] + \Vanilla\ApiUtils::queryToFilters($in, $query);
        $rows = $this->draftModel->get($where);

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Get revisions from a specific article.
     *
     * @param int $id
     * @return array
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws NotFoundException If the article could not be found.
     * @throws ValidationException If the output fails to validate against the schema.
     */
    public function index_revisions(int $id): array {
        $this->permission("knowledge.kb.view");

        $this->idParamSchema()->setDescription("Get revisions from a specific article.");
        $out = $this->schema([
            ":a" => Schema::parse([
                "articleRevisionID",
                "articleID",
                "status",
                "name",
                "locale",
                "insertUser",
                "dateInserted",
            ])->add($this->fullRevisionSchema()),
        ], "out");

        $article = $this->articleByID($id);
        $revisions = $this->articleRevisionModel->get(
            ["articleID" => $article["articleID"]],
            [
                "orderFields" => "dateInserted",
                "orderDirection" => "desc",
            ]
        );

        foreach ($revisions as &$revision) {
            $this->userModel->expandUsers(
                $revision,
                ["insertUserID", "updateUserID"]
            );
        }

        $result = $out->validate($revisions);
        return $result;
    }

    /**
     * Massage article row data for useful API output.
     *
     * @param array $row
     * @return array
     * @throws ServerException If no article ID was found in the row.
     */
    public function normalizeOutput(array $row): array {
        $articleID = $row["articleID"] ?? null;
        if (!$articleID) {
            throw new ServerException("No ID in article row.");
        }

        $name = $row["name"] ?? null;
        $slug = $articleID . ($name ? "-" . Gdn_Format::url($name) : "");
        $row["url"] = \Gdn::request()->url("/kb/articles/{$slug}");

        $bodyRendered = $row["bodyRendered"] ?? null;
        $row["body"] = $bodyRendered;
        $row["outline"] = isset($row["outline"]) ? json_decode($row["outline"], true) : [];
        // Placeholder data.
        $row["seoName"] = null;
        $row["seoDescription"] = null;
        $row["slug"] = $slug;

        return $row;
    }

    /**
     * Generate outline array from article body
     *
     * @param string $body
     * @return array
     */
    public function getOutline(string $body): array {
        $outline = [];
        $body = json_decode($body, true);
        if (is_array($body) && count($body) > 0) {
            $parser = (new Parser())
                ->addBlot(HeadingTerminatorBlot::class);
            $blotGroups = $parser->parse($body);

            /** @var BlotGroup $blotGroup */
            foreach ($blotGroups as $blotGroup) {
                $blot = $blotGroup->getPrimaryBlot();
                if ($blot instanceof HeadingTerminatorBlot && $blot->getReference()) {
                    $outline[] = [
                        'ref' => $blot->getReference(),
                        'level' => $blot->getHeadingLevel(),
                        'text' => $blotGroup->getUnsafeText(),
                    ];
                }
            }
        }
        return $outline;
    }
    /**
     * Update an existing article.
     *
     * @param int $id
     * @param array $body
     * @return array
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function patch(int $id, array $body = []): array {
        $this->permission("knowledge.articles.add");

        $in = $this->articlePostSchema("in")->setDescription("Update an existing article.");
        $out = $this->articleSchema("out");

        $body = $in->validate($body, true);
        $this->save($body, $id);
        $row = $this->articleByID($id, true);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Update an article draft.
     *
     * @param int $draftID
     * @param array $body
     * @return array
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function patch_drafts(int $draftID, array $body): array {
        $this->permission("articles.add");

        $this->schema(["draftID" => "Target article draft ID."], "in");
        $in = $this->schema($this->draftPostSchema(), "in")
            ->setDescription("Update an article draft.");
        $out = $this->schema($this->fullDraftSchema(), "out");

        $body = $in->validate($body, true);
        $body["recordType"] = "article";

        $draft = $this->draftByID($draftID);
        if ($draft["insertUserID"] !== $this->getSession()->UserID) {
            $this->permission("settings.manage");
        }

        $this->draftModel->update($body, ["draftID" => $draftID]);
        $row = $this->draftByID($draftID);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Update article status an existing article.
     *
     * @param int $id ArticleID
     * @param array $body Incoming json array with 'status' key.
     *        Possible values: published, deleted, etc
     *
     * @return array Data array Article record/item
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function patch_status(int $id, array $body): array {
        $this->permission("knowledge.articles.add");

        $this->idParamSchema();
        $in = $this->schema([
            "status:s" => [
                "description" => "Article status.",
                "enum" => ArticleModel::getAllStatuses(),
            ],
        ], "in")->setDescription("Set the status of an article.");
        $out = $this->articleSchema("out");
        $body = $in->validate($body);
        $article = $this->articleByID($id);
        if ($article['status'] !== $body['status']) {
            $this->articleModel->update(
                ['status' => $body['status']],
                ["articleID" => $id]
            );
        }

        $row = $this->articleByID($id, true);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Create a new article.
     *
     * @param array $body
     * @return array
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function post(array $body): array {
        $this->permission("knowledge.articles.add");

        $in = $this->articlePostSchema("in")->setDescription("Create a new article.");
        $out = $this->articleSchema("out");

        $body = $in->validate($body);
        $articleID = $this->save($body);
        $row = $this->articleByID($articleID, true);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Create a new article draft.
     *
     * @param array $body
     * @return array
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function post_drafts(array $body): array {
        $this->permission("articles.add");

        $in = $this->schema($this->draftPostSchema(), "in")
            ->setDescription("Create a new article draft.");
        $out = $this->schema($this->fullDraftSchema(), "out");

        $body = $in->validate($body);
        $body["recordType"] = "article";
        $draftID = $this->draftModel->insert($body);
        $row = $this->draftByID($draftID);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Separate article and revision fields from request input and save to the proper resources.
     *
     * @param array $fields
     * @param int|null $articleID
     * @return int
     * @throws Exception If an error is encountered while performing underlying database operations.
     * @throws NoResultsException If the article could not be found.
     */
    private function save(array $fields, int $articleID = null): int {
        $revisionFields = ["body" => true, "format" => true, "locale" => true, "name" => true];

        $article = array_diff_key($fields, $revisionFields);
        $revision = array_intersect_key($fields, $revisionFields);

        if ($articleID !== null) {
            $prevState = $this->articleModel->get(['articleID' => $articleID]);
            $this->articleModel->update($article, ["articleID" => $articleID]);
            if (isset($article['knowledgeCategoryID']) && ($prevState['knowledgeCategoryID'] !== $article['knowledgeCategoryID'])) {
                if (!empty($prevState['knowledgeCategoryID'])) {
                    $this->knowledgeCategoryModel->updateCounts($prevState['knowledgeCategoryID']);
                }
            }
        } else {
            $articleID = $this->articleModel->insert($fields);
        }
        if (!empty($article['knowledgeCategoryID'])) {
            $this->knowledgeCategoryModel->updateCounts($article['knowledgeCategoryID']);
        }


        if (!empty($revision)) {
            // Grab the current revision, if available, to load as initial defaults.
            $currentRevision = array_intersect_key($this->articleModel->getIDWithRevision($articleID), $revisionFields);
            $revision = array_merge($currentRevision, $revision);
            $revision["articleID"] = $articleID;
            $revision["locale"] = $fields["locale"] ?? $this->getLocale()->current();

            // Temporary defaults until drafts are implemented, at which point these fields will be required.
            $revision["name"] = $revision["name"] ?? "";
            $revision["body"] = $revision["body"] ?? "";
            $revision["format"] = $revision["format"] ?? strtolower(\Gdn_Format::defaultFormat());

            // Temporary hack to avoid a Rich format error if we have no body.
            if ($revision["body"] === "" && $revision["format"] === "rich") {
                $revision["body"] = "[]";
            }

            $revision["bodyRendered"] = \Gdn_Format::to($revision["body"], $revision["format"]);
            $outline = $this->getOutline($revision["body"]);
            $revision["outline"] = json_encode($outline);
            $articleRevisionID = $this->articleRevisionModel->insert($revision);
            $this->articleRevisionModel->publish($articleRevisionID);
        }

        if (array_key_exists("draftID", $fields)) {
            $this->draftModel->delete([
                "draftID" => $fields["draftID"],
                "recordType" => "article",
            ]);
        }

        return $articleID;
    }
}
