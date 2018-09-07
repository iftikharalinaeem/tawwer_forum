<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
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
class ArticlesApiController extends \AbstractApiController {

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
     * @throws ValidationException If a fetched row fails to validate against the article schema.
     */
    private function articleByID(int $id): array {
        $resultSet = $this->articleModel->get(["ArticleID" => $id], ["limit" => 1]);
        if (empty($resultSet)) {
            throw new NotFoundException("Article");
        }
        $row = reset($resultSet);
        return $row;
    }

    /**
     * Get the schema for articles joined to records.
     *
     * @return \Garden\Schema\Schema Returns a schema.
     */
    public function articleFragmentSchema(): \Garden\Schema\Schema {
        if ($this->articleFragmentSchema === null) {
            $this->articleFragmentSchema = $this->schema([
                "articleID:i" => "The ID of the article.",
                "name:s" => "Name of the article.",
                "updateUser" => $this->getUserFragmentSchema(),
                "slug:s" => "Unique path slug for the article.",
                "url:s" => "Full URL to the article.",
            ], "ArticleFragment");
        }
        return $this->articleFragmentSchema;
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
                    "knowledgeCategoryID",
                    "sort?",
                ])->add($this->articleSchema()),
                "ArticlePost"
            );
        }

        return $this->schema($this->articlePostSchema, $type);
    }

    /**
     * Get the full schema for an article. This includes current revision fields.
     *
     * @param string $type
     * @return \Garden\Schema\Schema
     */
    public function articleSchema(string $type = ""): \Garden\Schema\Schema {
        return $this->schema($this->fullSchema(), $type);
    }

    /**
     * Get a schema representing the combined available fields from articles and revisions.
     *
     * @return \Garden\Schema\Schema
     */
    private function fullSchema(): \Garden\Schema\Schema {
        if ($this->articleSchema === null) {
            $this->articleSchema = $this->schema([
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
            ], "Article");
        }
        return $this->articleSchema;
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
        $this->permission();

        $this->idParamSchema();
        $in = $this->schema([
            "expand" => ApiUtils::getExpandDefinition(["all", "ancestors", "articleRevision"]),
        ], "in")->setDescription("Get an article.");
        $out = $this->schema($this->articleSchema(), "out");

        $query = $in->validate($query);

        $article = $this->articleByID($id);
        $this->userModel->expandUsers(
            $article,
            ["insertUserID", "updateUserID"]
        );
        $article = $this->normalizeOutput($article, $query["expand"] ?: []);
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
     * Massage article row data for useful API output.
     *
     * @param array $row
     * @param array $expand
     * @return array
     * @throws ServerException If no article ID was found in the row.
     * @throws ValidationException If a fetched article fails to validate against the article schema.
     * @throws ValidationException If a fetched article revision fails to validate against the article revision schema.
     */
    public function normalizeOutput(array $row, array $expand = []): array {
        $articleID = $row["articleID"] ?? null;
        if (!$articleID) {
            throw new ServerException("No ID in article row.");
        }

        $publishedRevision = $this->articleRevisionModel->get([
            "articleID" => $row["articleID"],
            "status" => "published"
        ]);
        $articleRevision = reset($publishedRevision) ?: null;
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
        $row["seoName"] = "Example SEO Name";
        $row["seoDescription"] = "Example SEO description.";
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

        $in = $this->articlePostSchema("in");
        $out = $this->articleSchema("out");

        $body = $in->validate($body, true);
        $this->articleModel->update($body, ["articleID" => $id]);
        $row = $this->articleByID($id);
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
        $this->permission();

        $in = $this->articlePostSchema("in");
        $out = $this->articleSchema("out");

        $body = $in->validate($body);
        $articleID = $this->articleModel->insert($body);
        $row = $this->articleByID($articleID);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }
}
