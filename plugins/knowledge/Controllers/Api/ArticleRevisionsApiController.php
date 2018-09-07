<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Gdn_Format as Formatter;
use UserModel;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Models\ArticleRevisionModel;

/**
 * API controller for managing the article revisions resource.
 */
class ArticleRevisionsApiController extends AbstractApiController {

    /** @var ArticleRevisionModel */
    private $articleRevisionModel;

    /** @var Schema */
    private $articleRevisionPostSchema;

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
     * @param UserModel $userModel
     */
    public function __construct(ArticleRevisionModel $articleRevisionModel, UserModel $userModel) {
        $this->articleRevisionModel = $articleRevisionModel;
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
     * Get an article revision schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function articleRevisionPostSchema(string $type = ""): Schema {
        if ($this->articleRevisionPostSchema === null) {
            $this->articleRevisionPostSchema = $this->schema(
                Schema::parse([
                    "articleID",
                    "name",
                    "locale" => [ "default" => $this->getLocale()->current()],
                    "body",
                    "format",
                    "status?",
                ])->add($this->articleRevisionSchema()),
                "ArticleRevisionPost"
            );
        }

        return $this->schema($this->articleRevisionPostSchema, $type);
    }

    /**
     * Get the full schema for an article revision.
     *
     * @param string $type
     * @return Schema
     */
    public function articleRevisionSchema(string $type = ""): Schema {
        return $this->schema($this->fullSchema(), $type);
    }

    /**
     * Get a schema representing an article revision.
     *
     * @return Schema
     */
    private function fullSchema(): Schema {
        if ($this->articleRevisionSchema === null) {
            $this->articleRevisionSchema = $this->schema([
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
                    "description" => "Raw body contents.",
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
            ], "Article");
        }
        return $this->articleRevisionSchema;
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
        $this->permission();

        $this->idParamSchema()->setDescription("Get an article revision.");
        $out = $this->articleRevisionSchema("out");

        $row = $this->articleRevisionByID($id);
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
     * Get an article revision by its unique ID.
     *
     * @param array $body
     * @return array
     * @throws ValidationException If input or output fail schema validation.
     * @throws \Garden\Web\Exception\HttpException If a permission ban applies to this user ("Access denied").
     * @throws \Vanilla\Exception\PermissionException If the current user does not have sufficient permissions.
     */
    public function post(array $body): array {
        $this->permission();

        $in = $this->articleRevisionPostSchema("in")->setDescription("Create a new article revision.");
        $out = $this->articleRevisionSchema("out");

        $body = $in->validate($body);
        $body["bodyRendered"] = Formatter::to($body["body"], $body["format"]);
        $articleRevisionID = $this->articleRevisionModel->insert($body);

        // Remove the "published" flag from the currently-published revision.
        $this->articleRevisionModel->update(
            ["status" => null],
            ["articleID" => $body["articleID"], "status" => "published"]
        );
        // Publish this revision.
        $this->articleRevisionModel->update(
            ["status" => "published"],
            ["articleRevisionID" => $articleRevisionID]
        );

        $row = $this->articleRevisionByID($articleRevisionID);
        $this->userModel->expandUsers(
            $row,
            ["insertUserID"]
        );

        $result = $out->validate($row);
        return $result;
    }
}
