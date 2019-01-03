<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use UserModel;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Knowledge\Models\ArticleModel;

/**
 * API controller for managing the article revisions resource.
 *
 * This API controller currently exists here instead of as a subresource on the articles controller
 * due to limitations of our routing system. There is currently no way to differentiate
 * - /articles/revisions/:revisionID
 * - /articles/:articleID/revisions
 * As a result we have to do /article-revisions/:id
 * @see https://github.com/vanilla/knowledge/issues/264
 */
class ArticleRevisionsApiController extends AbstractKnowledgeApiController {

    /** @var ArticleModel */
    private $articleModel;

    /** @var ArticleRevisionModel */
    private $articleRevisionModel;

    /** @var Schema */
    private $articleRevisionSchema;

    /** @var Schema */
    private $idParamSchema;

    /** @var UserModel */
    private $userModel;

    /**
     * ArticleRevisionsApiController constructor.
     *
     * @param ArticleRevisionModel $articleRevisionModel
     * @param ArticleModel $articleModel
     * @param UserModel $userModel
     */
    public function __construct(ArticleRevisionModel $articleRevisionModel, ArticleModel $articleModel, UserModel $userModel) {
        $this->articleRevisionModel = $articleRevisionModel;
        $this->articleModel = $articleModel;
        $this->userModel = $userModel;
    }

    /**
     * Get an article by its numeric ID.
     *
     * @param int $id Article ID.
     * @return array
     * @throws NotFoundException If the article revision could not be found.
     * @throws ValidationException If a fetched row fails to validate against the article schema.
     */
    private function articleRevisionByID(int $id): array {
        $resultSet = $this->articleRevisionModel->get(["articleRevisionID" => $id], ["limit" => 1]);
        if (empty($resultSet)) {
            throw new NotFoundException("Article Revision");
        }
        $row = reset($resultSet);
        return $row;
    }

    /**
     * Get the full schema for an article revision.
     *
     * @param string $type
     * @return Schema
     */
    public function articleRevisionSchema(string $type = ""): Schema {
        if ($this->articleRevisionSchema === null) {
            $this->articleRevisionSchema = $this->schema(Schema::parse([
                "articleRevisionID",
                "articleID",
                "status",
                "name",
                "body",
                "bodyRendered",
                "locale",
                "insertUserID",
                "dateInserted",
                "insertUser?",
            ])->add($this->fullSchema()), "ArticleRevision");
        }
        return $this->schema($this->articleRevisionSchema, $type);
    }

    /**
     * Get a schema representing an article revision.
     *
     * @return Schema
     */
    private function fullSchema(): Schema {
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
        ]);
    }

    /**
     * Get an article revision by its unique ID.
     *
     * @param int|string $id
     * @return array
     * @throws NotFoundException If the article revision could not be located.
     * @throws ValidationException If input or output fail schema validation.
     * @throws \Garden\Web\Exception\HttpException If a permission ban applies to this user ("Access denied").
     * @throws \Vanilla\Exception\PermissionException If the current user does not have sufficient permissions.
     */
    public function get($id): array {
        $this->permission("knowledge.kb.view");

        $this->idParamSchema()->setDescription("Get an article revision.");
        $out = $this->articleRevisionSchema("out");

        $row = $this->articleRevisionByID($id);
        $row = $this->normalizeOutput($row);
        $this->userModel->expandUsers(
            $row,
            ["insertUserID"]
        );
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only article revision schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    private function idParamSchema(string $type = "in"): Schema {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(["id:i" => "The article revision ID."]),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * Massage article row data for useful API output.
     *
     * @param array $row
     * @return array
     */
    private function normalizeOutput(array $row): array {
        return $row;
    }
}
