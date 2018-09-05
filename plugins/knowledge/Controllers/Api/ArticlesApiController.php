<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Exception;
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
     * Get a article schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return \Garden\Schema\Schema Returns a schema object.
     */
    public function articlePostSchema(string $type = ""): \Garden\Schema\Schema {
        if ($this->articlePostSchema === null) {
            $this->articlePostSchema = $this->schema(
                \Garden\Schema\Schema::parse([
                    "name",
                    "locale?",
                    "body",
                    "format",
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
                "name:s" => [
                    "allowNull" => true,
                    "description" => "Title of the article.",
                ],
                "locale:s" => [
                    "allowNull" => true,
                    "description" => "Locale the article was written in.",
                ],
                "body:s" => [
                    "allowNull" => true,
                    "description" => "Raw body contents.",
                ],
                "bodyRendered:s" => [
                    "allowNull" => true,
                    "description" => "Rendered body contents.",
                ],
                "format:s" => [
                    "allowNull" => true,
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
            "expand" => ApiUtils::getExpandDefinition(["ancestors", "all", "user"]),
        ], "in")->setDescription("Get an article.");
        $out = $this->schema($this->articleSchema(), "out");

        $query = $in->validate($query);

        $article = $this->articleByID($id);
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

        $slug = \Gdn_Format::url($row["name"] ? "{$row['name']}-{$row['articleID']}" : $row["articleID"]);
        $row["url"] = \Gdn::request()->url("/kb/articles/{$slug}", true);

        // Merge in the current revision.
        $revision = [
            "name" => null,
            "locale" => null,
            "body" => null,
            "bodyRendered" => null,
            "format" => null,
        ];

        $revisionResult = $this->articleRevisionModel->get([
            "articleID" => $row["articleID"],
            "status" => "published"
        ]);
        if ($revisionResult) {
            $revisionRow = reset($revisionResult);
            $revision = $revisionRow + $revision;
        }
        $row = array_merge($revision, $row);

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

        $body = $in->validate($body);
        $body["articleID"] = $id;
        $articleID = $this->save($body);
        $row = $this->articleByID($articleID);
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
        $articleID = $this->save($body);
        $row = $this->articleByID($articleID);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Save an article, diverting the data to its respective models.
     *
     * @param array $fields
     * @return int
     */
    private function save(array $fields): int {
        // Save data to the article table.
        $articleFields = [
            "articleID",
            "knowledgeCategoryID",
            "sort",
        ];
        $article = array_intersect_key($fields, array_flip($articleFields));
        $articleID = $this->saveArticle($article);

        // Save data to the revision table.
        $revisionFields = [
            "name",
            "format",
            "body",
            "locale",
        ];
        $revision = array_intersect_key($fields, array_flip($revisionFields));
        if ($revision) {
            $revision["articleID"] = $articleID;
            $this->saveRevision($revision);
        }

        return $articleID;
    }

    /**
     * Insert or update an article row.
     *
     * @param array $data
     * @return int
     * @throws Exception If saving the article fails.
     */
    private function saveArticle(array $data): int {
        $articleID = $data["articleID"] ?? null;
        $userID = $this->getSession()->UserID;

        if ($articleID) {
            // Update
            $data["updateUserID"] = $userID;
            $data["dateUpdated"] = new \DateTimeImmutable("now");
            $this->articleModel->update($data, ["articleID" => $articleID]);
        } else {
            // Insert
            $data["insertUserID"] = $data["updateUserID"] = $userID;
            $data["dateInserted"] = $data["dateUpdated"] = new \DateTimeImmutable("now");
            $articleID = $this->articleModel->insert($data);
        }

        return $articleID;
    }

    /**
     * Save a new article revision.
     *
     * @param array $data
     * @return int
     * @throws Exception If saving the article revision fails.
     */
    private function saveRevision(array $data): int {
        $data["bodyRendered"] = \Gdn_Format::to($data["body"], $data["format"]);
        $data["insertUserID"] = $this->getSession()->UserID;
        $data["dateInserted"] = new \DateTimeImmutable("now");
        $revisionID = $this->articleRevisionModel->insert($data);

        // Remove the "published" flag from the currently-published revision.
        $this->articleRevisionModel->update(
            ["status" => null],
            ["articleID" => $data["articleID"], "status" => "published"]
        );
        // Publish this revision.
        $this->articleRevisionModel->update(
            ["status" => "published"],
            ["articleRevisionID" => $revisionID]
        );
        return $revisionID;
    }
}
