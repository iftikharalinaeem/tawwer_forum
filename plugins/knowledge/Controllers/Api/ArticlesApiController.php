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
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;

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
    private $articleNoBodySchema;

    /** @var ArticleModel */
    private $articleModel;

    /** @var ArticleRevisionModel */
    private $articleRevisionModel;

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
     */
    public function __construct(
        ArticleModel $articleModel,
        ArticleRevisionModel $articleRevisionModel,
        UserModel $userModel
    ) {
        $this->articleModel = $articleModel;
        $this->articleRevisionModel = $articleRevisionModel;
        $this->userModel = $userModel;
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
                    "locale" => ["default" => $this->getLocale()->current()],
                    "sort?",
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
     * Get a full article schema that also includes its name from a revision.
     *
     * @param string $type
     * @return Schema
     */
    public function articleNoBodySchema(string $type = ""): Schema {
        if ($this->articleNoBodySchema === null) {
            $this->articleNoBodySchema = $this->schema(Schema::parse([
                "articleID",
                "knowledgeCategoryID",
                "name",
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
            ])->add($this->fullSchema()), "ArticleNoBody");
        }
        return $this->schema($this->articleNoBodySchema, $type);
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
                'enum' => ArticleModel::getAllStatuses()
            ],
            "excerpt:s?" => [
                "allowNull" => true,
                "description" => "Plain-text excerpt of the current article body.",
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
            "articleID:i" => "Associated article ID.",
            "status:s" => [
                "allowNull" => true,
                "description" => "",
                "enum" => ["published"],
            ],
            "name:s" => [
                "allowNull" => true,
                "description" => "Title of the article.",
            ],
            "format:s" => [
                "allowNull" => true,
                "enum" => ["text", "textex", "markdown", "wysiwyg", "html", "bbcode", "rich"],
                "description" => "Format of the raw body content.",
            ],
            "body:s" => [
                "allowNull" => true,
                "description" => "Body contents.",
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
        $this->permission();

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
     * @return array
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     * @throws HttpException If a relevant ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function index(array $query = []) {
        $this->permission("knowledge.kb.view");

        $in = $this->schema([
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
        ], "in")->setDescription("List published articles in a given knowledge category.");
        $out = $this->schema([":a" => $this->articleNoBodySchema()], "out");

        $query = $in->validate($query);

        $rows = $this->articleModel->getWithRevision(
            ["knowledgeCategoryID" => $query["knowledgeCategoryID"]],
            [
                "limit" => $query["limit"],
                "includeBody" => false,
            ]
        );
        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row);
        }
        $this->userModel->expandUsers(
            $rows,
            ["insertUserID", "updateUserID"]
        );

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * List excerpts from published articles in a given knowledge category.
     *
     * @param array $query
     * @return array
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     * @throws HttpException If a relevant ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function index_excerpts(array $query = []) {
        $this->permission("knowledge.kb.view");

        $in = $this->schema([
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
        ], "in")->setDescription("List excerpts from published articles in a given knowledge category.");
        $out = $this->schema([":a" => $this->articleFragmentSchema()], "out");
        $query = $in->validate($query);

        $articles = $this->articleModel->getWithRevision(
            ["knowledgeCategoryID" => $query["knowledgeCategoryID"]],
            ["limit" => $query["limit"]]
        );
        foreach ($articles as &$article) {
            $article = $this->normalizeOutput($article);
        }
        $this->userModel->expandUsers(
            $articles,
            ["insertUserID", "updateUserID"]
        );
        foreach ($articles as &$article) {
            $article["excerpt"] = $article["body"] ? sliceString(Gdn_Format::plainText($article["body"], "Html"), self::EXCERPT_MAX_LENGTH) : null;
        }

        $result = $out->validate($articles);
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
        $this->permission();

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
            ])->add($this->fullRevisionSchema())
        ], "out");

        $article = $this->articleByID($id);
        $revisions = $this->articleRevisionModel->get(["articleID" => $article["articleID"]]);

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

        // Placeholder data.
        $row["seoName"] = null;
        $row["seoDescription"] = null;
        $row["slug"] = $slug;

        return $row;
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
        $this->permission();

        $in = $this->articlePostSchema("in")->setDescription("Update an existing article.");
        $out = $this->articleSchema("out");

        $article = $this->articleByID($id);
        $this->editPermission($article["insertUserID"]);
        $body = $in->validate($body, true);
        $this->save($body, $id);
        $row = $this->articleByID($id, true);
        $row = $this->normalizeOutput($row);
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
        $this->permission();

        $this->idParamSchema();
        $in = $this->schema([
            "status:s" => [
                "description" => "Article status.",
                "enum" => ArticleModel::getAllStatuses()
            ]
        ], "in")->setDescription("Set the status of an article.");
        $out = $this->articleSchema("out");
        $body = $in->validate($body);
        $article = $this->articleByID($id);
        $this->editPermission($article["insertUserID"]);
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
        $this->permission(["knowledge.articles.add", "knowledge.articles.manage"]);

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
            $this->articleModel->update($article, ["articleID" => $articleID]);
        } else {
            $articleID = $this->articleModel->insert($fields);
        }

        if (!empty($revision)) {
            // Grab the current revision, if available, to load as initial defaults.
            $currentRevision = array_intersect_key($this->articleModel->getIDWithRevision($articleID), $revisionFields);
            $revision = array_merge($currentRevision, $revision);
            $revision["articleID"] = $articleID;

            // Temporary defaults until drafts are implemented, at which point these fields will be required.
            $revision["name"] = $revision["name"] ?? "";
            $revision["body"] = $revision["body"] ?? "";
            $revision["format"] = $revision["format"] ?? strtolower(\Gdn_Format::defaultFormat());

            $revision["bodyRendered"] = \Gdn_Format::to($revision["body"], $revision["format"]);
            $articleRevisionID = $this->articleRevisionModel->insert($revision);
            $this->articleRevisionModel->publish($articleRevisionID);
        }

        return $articleID;
    }
}
