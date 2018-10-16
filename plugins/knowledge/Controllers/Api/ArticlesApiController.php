<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Exception;
use Gdn_Format;
use UserModel;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\Exception\PermissionException;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;

/**
 * API controller for managing the articles resource.
 */
class ArticlesApiController extends AbstractKnowledgeApiController {

    /** @var \Garden\Schema\Schema */
    private $articleFragmentSchema;

    /** @var \Garden\Schema\Schema */
    private $articlePostSchema;

    /** @var \Garden\Schema\Schema */
    private $articleSchema;

    /** @var ArticleModel */
    private $articleModel;

    /** @var ArticleRevisionModel */
    private $articleRevisionModel;

    /** @var ArticleRevisionsApiController */
    private $articleRevisionsApiController;

    /** @var \Garden\Schema\Schema */
    private $idParamSchema;

    /** @var UserModel */
    private $userModel;

    /**
     * ArticlesApiController constructor.
     *
     * @param ArticleModel $articleModel
     * @param ArticleRevisionModel $articleRevisionModel
     * @param ArticleRevisionsApiController $articleRevisionsApiController
     * @param UserModel $userModel
     */
    public function __construct(
        ArticleModel $articleModel,
        ArticleRevisionModel $articleRevisionModel,
        ArticleRevisionsApiController $articleRevisionsApiController,
        UserModel $userModel
    ) {
        $this->articleModel = $articleModel;
        $this->articleRevisionModel = $articleRevisionModel;
        $this->articleRevisionsApiController = $articleRevisionsApiController;
        $this->userModel = $userModel;
    }

    /**
     * Get an article by its numeric ID.
     *
     * @param int $id Article ID.
     * @return array
     * @throws NotFoundException If the article could not be found.
     */
    private function articleByID(int $id): array {
        try {
            $article = $this->articleModel->getID($id);
        } catch (Exception $e) {
            throw new NotFoundException("Article");
        }
        return $article;
    }

    /**
     * Get an article schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return \Garden\Schema\Schema Returns a schema object.
     */
    public function articlePostSchema(string $type = ""): \Garden\Schema\Schema {
        if ($this->articlePostSchema === null) {
            $this->articlePostSchema = $this->schema(
                \Garden\Schema\Schema::parse([
                    "knowledgeCategoryID?",
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
     * @return \Garden\Schema\Schema Returns a schema object.
     */
    public function articleFragmentSchema(string $type = ""): \Garden\Schema\Schema {
        if ($this->articleFragmentSchema === null) {
            $this->articleFragmentSchema = $this->schema(
                \Garden\Schema\Schema::parse([
                    "articleID",
                    "knowledgeCategoryID",
                    "sort",
                ])->add($this->fullSchema()),
                "ArticleFragment"
            );
            $this->articleFragmentSchema->setField(
                "properties.articleRevision",
                \Garden\Schema\Schema::parse(["name"])->add($this->articleRevisionsApiController->articleRevisionSchema())
            );
        }

        return $this->schema($this->articleFragmentSchema, $type);
    }

    /**
     * Get the full schema for an article. This includes current revision fields.
     *
     * @param string $type
     * @return \Garden\Schema\Schema
     */
    public function articleSchema(string $type = ""): \Garden\Schema\Schema {
        if ($this->articleSchema === null) {
            $this->articleSchema = $this->schema($this->fullSchema(), "Article");
        }
        return $this->schema($this->articleSchema, $type);
    }

    /**
     * Get a schema representing the combined available fields from articles and revisions.
     *
     * @return \Garden\Schema\Schema
     */
    private function fullSchema(): \Garden\Schema\Schema {
        return \Garden\Schema\Schema::parse([
                "articleID:i" => "Unique article ID.",
                "knowledgeCategoryID:i" => "Category the article belongs in.",
                "articleRevisionID:i" => [
                    "allowNull" => true,
                    "description" => "Unique ID of the published revision."
                ],
                "articleRevision?" => $this->articleRevisionsApiController->articleRevisionFragmentSchema(),
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
                "categoryAncestorIDs:a?" => "integer",
                "status:s" => [
                    'description' => "Article status: draft, published, deleted, undeleted, etc...",
                    'enum' => [ArticleModel::STATUS_PUBLISHED, ArticleModel::STATUS_DELETED, ArticleModel::STATUS_UNDELETED]
                ]
            ]);
    }

    /**
     * Handle GET requests to the root of the endpoint.
     *
     * @param int $id
     * @param array $query
     * @return array
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     * @throws NotFoundException If the article could not be found.
     * @throws ServerException If there was an error normalizing the output.
     */
    public function get(int $id, array $query = []) {
        $this->permission("knowledge.kb.view");

        $this->idParamSchema();
        $in = $this->schema([
            "expand" => ApiUtils::getExpandDefinition(["all", "ancestors", "articleRevision"]),
        ], "in")->setDescription("Get an article.");
        $out = $this->articleSchema("out");

        $query = $in->validate($query);

        $article = $this->articleByID($id);
        $this->userModel->expandUsers(
            $article,
            ["insertUserID", "updateUserID"]
        );
        $revision = $this->articleRevisionModel->get([
            "articleID" => $id,
            "status" => "published"
        ]);
        $revision = array_column($revision, null, "articleID");
        $article = $this->normalizeOutput($article, $query["expand"] ?: [], $revision);
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
        $out = $this->schema(\Garden\Schema\Schema::parse([
            "articleID",
            "knowledgeCategoryID",
            "sort",
        ])->add($this->fullSchema()), "out");

        $article = $this->articleByID($id);
        $result = $out->validate($article);
        return $result;
    }

    /**
     * Get an ID-only article schema.
     *
     * @param string $type The type of schema.
     * @return \Garden\Schema\Schema Returns a schema object.
     */
    public function idParamSchema(string $type = "in"): \Garden\Schema\Schema {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                \Garden\Schema\Schema::parse(["id:i" => "The article ID."]),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * List published articles in a given knowledge category.
     *
     * @param array $query
     * @return mixed
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
        $out = $this->schema([":a" => $this->articleSchema()], "out");

        $query = $in->validate($query);

        $rows = $this->articleModel->getPublishedByCategory(
            $query["knowledgeCategoryID"],
            ["limit" => $query["limit"]]
        );
        $articleIDs = array_column($rows, "articleID");
        if ($articleIDs) {
            $revisions = $this->articleRevisionModel->get([
                "articleID" => $articleIDs,
                "status" => "published",
            ]);
            $revisions = array_column($revisions, null, "articleID");
        } else {
            $revisions = [];
        }
        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row, ["articleRevision"], $revisions);
        }

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Massage article row data for useful API output.
     *
     * @param array $row
     * @param array $expand
     * @param array $revisions Relevant published revisions. Indexed by article ID.
     * @return array
     * @throws ServerException If no article ID was found in the row.
     * @throws ValidationException If a fetched article fails to validate against the article schema.
     * @throws ValidationException If a fetched article revision fails to validate against the article revision schema.
     */
    public function normalizeOutput(array $row, array $expand = [], array $revisions = []): array {
        $articleID = $row["articleID"] ?? null;
        if (!$articleID) {
            throw new ServerException("No ID in article row.");
        }

        $articleRevision = $revisions[$articleID] ?? null;
        if ($articleRevision) {
            $this->userModel->expandUsers(
                $articleRevision,
                ["insertUserID"]
            );
            $row["articleRevisionID"] = $articleRevision["articleRevisionID"];
            $slug = Gdn_Format::url($articleRevision["name"] ? "{$articleRevision['name']}-{$row['articleID']}" : $row["articleID"]);
        } else {
            $row["articleRevisionID"] = null;
            $slug = null;
        }
        if ($this->isExpandField("articleRevision", $expand)) {
            $row["articleRevision"] = $articleRevision;
        }

        $row["url"] = \Gdn::request()->url("/kb/articles/".($slug ?: $row["articleID"]), true);

        // Placeholder data.
        $row["seoName"] = null;
        $row["seoDescription"] = null;
        $row["slug"] = $slug;
        if ($this->isExpandField("ancestors", $expand)) {
            $row["categoryAncestorIDs"] = [1, 2, 3];
        }

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
        $this->articleModel->update($body, ["articleID" => $id]);
        $row = $this->articleByID($id);
        $revision = $this->articleRevisionModel->get([
            "articleID" => $id,
            "status" => "published"
        ]);
        $revision = array_column($revision, null, "articleID");
        $row = $this->normalizeOutput($row, [], $revision);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Undelete an existing article.
     *
     * @param int $id ArticleID
     *
     * @return \Garden\Web\Data
     *
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function patch_undelete(int $id): \Garden\Web\Data {
        $this->permission();
        $in = $this->schema(["id:i" => "The article ID."], 'in')
            ->setDescription('Undelete an existing article.');
        $out = $this->schema([], 'out');
        $article = $this->articleByID($id);
        $this->editPermission($article["insertUserID"]);
        if ($article['status'] !== ArticleModel::STATUS_UNDELETED) {
            $this->articleModel->update(['status'=>ArticleModel::STATUS_UNDELETED], ["articleID" => $id]);
        }
        return new \Garden\Web\Data('', 204);
    }

    /**
     * Delete an existing article.
     *
     * @param int $id ArticleID
     *
     * @return \Garden\Web\Data
     *
     * @throws Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     */
    public function patch_delete(int $id): \Garden\Web\Data {
        $this->permission();

        $in = $this->schema(["id:i" => "The article ID."], 'in')
            ->setDescription('Delete an existing article.');
        $out = $this->schema([], 'out');

        $article = $this->articleByID($id);
        $this->editPermission($article["insertUserID"]);
        if ($article['status'] !== ArticleModel::STATUS_DELETED) {
            $this->articleModel->update(['status'=>ArticleModel::STATUS_DELETED], ["articleID" => $id]);
        }
        return new \Garden\Web\Data('', 204);
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
        $articleID = $this->articleModel->insert($body);
        $row = $this->articleByID($articleID);
        $revision = $this->articleRevisionModel->get([
            "articleID" => $articleID,
            "status" => "published"
        ]);
        $revision = array_column($revision, null, "articleID");
        $row = $this->normalizeOutput($row, [], $revision);
        $result = $out->validate($row);
        return $result;
    }
}
