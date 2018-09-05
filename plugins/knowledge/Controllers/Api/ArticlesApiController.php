<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\Exception\PermissionException;
use \Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;

/**
 * API controller for managing the articles resource.
 */
class ArticlesApiController extends \AbstractApiController {

    /** @var \Garden\Schema\Schema */
    private $articleFragmentSchema;

    /** @var \Garden\Schema\Schema */
    private $articleSchema;

    /** @var ArticleModel */
    private $articleModel;

    /** @var ArticleRevisionModel */
    private $articleRevisionModel;

    /** @var \Garden\Schema\Schema */
    private $idParamSchema;

    /**
     * ArticlesApiController constructor.
     *
     * @param ArticleModel $articleModel
     * @param ArticleRevisionModel $articleRevisionModel
     */
    public function __construct(ArticleModel $articleModel, ArticleRevisionModel $articleRevisionModel) {
        $this->articleModel = $articleModel;
        $this->articleRevisionModel = $articleRevisionModel;
    }

    /**
     * Get an article by its numeric ID.
     *
     * @param int $id Article ID.
     * @return array
     * @throws \Garden\Web\Exception\NotFoundException If the article could not be found.
     */
    private function articleByID(int $id): array {
        $resultSet = $this->articleModel->get(["ArticleID" => $id], ["limit" => 1]);
        if (empty($resultSet)) {
            throw new \Garden\Web\Exception\NotFoundException("Article");
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
     * Get an article revision by its numeric ID.
     *
     * @param int $id Article ID.
     * @return array
     * @throws \Garden\Web\Exception\NotFoundException If the article could not be found.
     */
    private function articleRevisionByID(int $id): array {
        $resultSet = $this->articleRevisionModel->get(["ArticleID" => $id], ["limit" => 1]);
        if (empty($resultSet)) {
            throw new \Garden\Web\Exception\NotFoundException("Article Revision");
        }
        $row = reset($resultSet);
        return $row;
    }

    /**
     * Get the full schema for an article. This includes current revision fields.
     *
     * @return \Garden\Schema\Schema
     */
    public function articleSchema(): \Garden\Schema\Schema {
        if ($this->articleSchema === null) {
            $this->articleSchema = $this->schema([
                "articleID:i" => "Unique article ID.",
                "name:s" => "Title of the article.",
                "locale:s" => [
                    "allowNull" => true,
                    "description" => "Locale the article was written in.",
                ],
                "body:s" => "Raw body contents.",
                "bodyRendered:s" => "Rendered body contents.",
                "format:s" => [
                    "enum" => ["text", "textex", "markdown", "wysiwyg", "html", "bbcode", "rich"],
                    "description" => "Format of the raw body content.",
                ],
                "knowledgeCategoryID:i" => "Category the article belongs in.",
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
                "insertUserID:i" => "Unique ID of the user who originally created the article.",
                "dateInserted:dt" => "When the article was created.",
                "updateUserID:i" => "Unique ID of the last user to update the article.",
                "dateUpdated:dt" => "When the article was last updated.",
                "insertUser?" => $this->getUserFragmentSchema(),
                "updateUser?" => $this->getUserFragmentSchema(),
                "score:i" => "Score of the article.",
                "views:i" => "How many times the article has been viewed.",
                "url:s" => "Full URL to the article.",
                "categoryAncestorIDs:a?" => "integer",
            ], "Article");
        }
        return $this->articleSchema;
    }

    /**
     * Handle GET requests to the root of the endpoint.
     *
     * @param int $id
     * @return array
     *
     * @throws \Exception if no session is available.
     * @throws HttpException if a ban has been applied on the permission(s) for this session.
     * @throws PermissionException if the user does not have the specified permission(s).
     * @throws ValidationException If the output is not properly validated.
     * @throws NotFoundException If the requested resource is not found.
     * @throws ServerException If malformed data was passed to normalization.
     */
    public function get(int $id, array $query = []) {
        $this->permission();

        $this->idParamSchema();
        $in = $this->schema([
            "expand" => ApiUtils::getExpandDefinition(["ancestors", "all", "user"]),
        ], "in")->setDescription("Get an article.");
        $out = $this->schema($this->articleSchema(), "out");

        $query = $in->validate($query);

        $article = $this->articleByID($id);
        $articleRevisionID = $article["articleRevisionID"] ?? null;
        if ($articleRevisionID) {
            $articleRevision = $this->articleRevisionByID($articleRevisionID);
            $article = array_merge($articleRevision, $article);
        }

        $article = $this->normalizeOutput($article, $query["expand"] ?: []);
        $result = $out->validate($article);
        return $result;
    }

    /**
     * Get an ID-only discussion record schema.
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
     * @param array $row
     * @param array $expand
     * @return array
     *
     * @throws ServerException
     */
    public function normalizeOutput(array $row, array $expand = []): array {
        $articleID = $row["articleID"] ?? null;
        if (!$articleID) {
            throw new ServerException("No ID in article row.");
        }
        $slug = \Gdn_Format::url($row["name"] ?? "example-slug");

        // Placeholder data.
        $row["seoName"] = "Example SEO Name";
        $row["seoDescription"] = "Example SEO description.";
        $row["slug"] = $slug;
        $row["url"] = url("/kb/articles/{$slug}-{$articleID}", true);

        if ($this->isExpandField("ancestors", $expand)) {
            $row["categoryAncestorIDs"] = [1, 2, 3];
        }

        return $row;
    }
}
