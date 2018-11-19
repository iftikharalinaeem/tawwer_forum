<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;

/**
 * Endpoint for the knowledge category resource.
 */
class KnowledgeCategoriesApiController extends AbstractApiController {

    /** @var Schema */
    private $idParamSchema;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var Schema */
    private $knowledgeCategoryPostSchema;

    /**
     * KnowledgeCategoriesApiController constructor.
     *
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     */
    public function __construct(KnowledgeCategoryModel $knowledgeCategoryModel) {
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
    }

    /**
     * Delete a knowledge category.
     *
     * @param int $id
     * @throws ValidationException If output validation fails while getting the knowledge category.
     * @throws \Garden\Web\Exception\HttpException If a ban has been applied on the permission(s) for this session.
     * @throws \Garden\Web\Exception\NotFoundException If the knowledge category could not be found.
     * @throws \Vanilla\Exception\PermissionException If the user does not have the specified permission(s).
     * @throws \Garden\Web\Exception\ClientException If the target knowledge category is not empty.
     */
    public function delete(int $id) {
        $this->permission("garden.setttings.manage");

        $this->idParamSchema()->setDescription("Delete a knowledge category.");
        $this->schema([], "out");

        $row = $this->knowledgeCategoryByID($id);
        if ($row["articleCount"] < 1 && $row["childCategoryCount"] < 1) {
            $this->knowledgeCategoryModel->delete(["knowledgeCategoryID" => $row["knowledgeCategoryID"]]);
            if (!empty($row['parentID']) && $row['parentID'] !== -1) {
                $this->knowledgeCategoryModel->updateCounts($row['parentID']);
            }
        } else {
            throw new \Garden\Web\Exception\ClientException("Knowledge category is not empty.");
        }
    }

    /**
     * Get a schema representing all available fields for a knowledge category.
     *
     * @return Schema
     */
    private function fullSchema(): Schema {
        return Schema::parse([
            "knowledgeCategoryID" => [
                "description" => "Unique knowledge category ID.",
                "type" => "integer",
            ],
            "name" => [
                "description" => "Name for the category.",
                "length" => 255,
                "type" => "string",
            ],
            "parentID" => [
                "allowNull" => true,
                "description" => "Unique ID of the parent for a category.",
                "type" => "integer",
            ],
            "sortChildren" => [
                "allowNull" => true,
                "description" => "Sort order for contents of the category.",
                "enum" => ["name", "dateInserted", "dateInsertedDesc", "manual"],
                "type" => "string",
            ],
            "sort" => [
                "allowNull" => true,
                "description" => "Sort weight of the category. Used when sorting the parent category's contents.",
                "type" => "integer",
            ],
            "insertUserID" => [
                "description" => "Unique ID of the user who originally created the knowledge category.",
                "type" => "integer",
            ],
            "dateInserted:dt" => [
                "description" => "When the knowledge category was created.",
                "type" => "datetime",
            ],
            "updateUserID:i" => [
                "description" => "Unique ID of the last user to update the knowledge category.",
                "type" => "integer",
            ],
            "dateUpdated:dt" => [
                "description" => "When the knowledge category was last updated.",
                "type" => "datetime",
            ],
            "lastUpdatedArticleID" => [
                "allowNull" => true,
                "description" => "Unique ID of the last article to be updated in the category.",
                "type" => "integer",
            ],
            "lastUpdatedUserID" => [
                "allowNull" => true,
                "description" => "Unique ID of the last user to update an article in the category.",
                "type" => "integer",
            ],
            "articleCount" => [
                "description" => "Total articles in the category.",
                "type" => "integer",
            ],
            "articleCountRecursive" => [
                "description" => "Aggregate total of all articles in the category and its children.",
                "type" => "integer",
            ],
            "childCategoryCount" => [
                "description" => "Total child categories.",
                "type" => "integer",
            ],
            "url" => [
                "description" => "Full URL to the knowledge category.",
                "type" => "string",
            ],
        ]);
    }

    /**
     * Get a single knowledge category.
     *
     * @param int $id
     * @return array
     * @throws \Exception If no session is available.
     * @throws \Garden\Web\Exception\HttpException If a ban has been applied on the permission(s) for this session.
     * @throws \Vanilla\Exception\PermissionException If the user does not have the specified permission(s).
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     * @throws \Garden\Web\Exception\NotFoundException If the knowledge category could not be found.
     */
    public function get(int $id): array {
        $this->permission("knowledge.kb.view");

        $this->idParamSchema()->setDescription("Get a single knowledge category.");
        $out = $this->schema($this->fullSchema(), "out");

        $row = $this->knowledgeCategoryByID($id);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a knowledge category for editing.
     *
     * @param int $id
     * @return array
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     * @throws \Garden\Web\Exception\HttpException If a ban has been applied on the permission(s) for this session.
     * @throws \Garden\Web\Exception\NotFoundException If the knowledge category could not be found.
     * @throws \Vanilla\Exception\PermissionException If the user does not have the specified permission(s).
     */
    public function get_edit(int $id): array {
        $this->permission("garden.settings.manage");

        $this->idParamSchema()->setDescription("Get a knowledge category for editing.");
        $out = $this->schema(Schema::parse([
            "knowledgeCategoryID",
            "name",
            "parentID",
            "sort",
            "sortChildren",
        ])->add($this->fullSchema()), "out");

        $row = $this->knowledgeCategoryByID($id);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only knowledge category schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema(string $type = "in"): Schema {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(["id:i" => "Knowledge category ID."]),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * List knowledge categories.
     *
     * @return array
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     * @throws \Garden\Web\Exception\HttpException If a ban has been applied on the permission(s) for this session.
     * @throws \Vanilla\Exception\PermissionException If the user does not have the specified permission(s).
     */
    public function index(): array {
        $this->permission("knowledge.kb.view");

        $in = $this->schema([])->setDescription("List knowledge categories.");
        $out = $this->schema([":a" => $this->fullSchema()], "out");

        $rows = $this->knowledgeCategoryModel->get();
        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row);
        }

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Get a single knowledge category by its ID.
     *
     * @param int $knowledgeCategoryID
     * @return array
     * @throws \Garden\Web\Exception\NotFoundException If the knowledge category could not be found.
     * @throws ValidationException If the knowledge category row fails validating against the model's output schema.
     */
    public function knowledgeCategoryByID(int $knowledgeCategoryID): array {
        try {
            $result = $this->knowledgeCategoryModel->selectSingle(["knowledgeCategoryID" => $knowledgeCategoryID]);
        } catch (\Vanilla\Exception\Database\NoResultsException $e) {
            throw new \Garden\Web\Exception\NotFoundException("Knowledge-Category");
        }
        return $result;
    }

    /**
     * Get a knowledge category schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function knowledgeCategoryPostSchema(string $type = "in"): Schema {
        if ($this->knowledgeCategoryPostSchema === null) {
            $this->knowledgeCategoryPostSchema = $this->schema(
                Schema::parse([
                    "name",
                    "parentID",
                    "sort?",
                    "sortChildren?",
                ])->add($this->fullSchema()),
                "KnowledgeCategoryPost"
            );
        }

        return $this->schema($this->knowledgeCategoryPostSchema, $type);
    }

    /**
     * Massage knowledge category row data for useful API output.
     *
     * @param array $row
     * @return array
     * @throws \Exception If $row is not a valid knowledge category.
     */
    public function normalizeOutput(array $row): array {
        $row["url"] = $this->knowledgeCategoryModel->url($row, false);
        return $row;
    }

    /**
     * Update an existing knowledge category.
     *
     * @param int $id
     * @param array $body
     * @return array
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     * @throws \Garden\Web\Exception\HttpException If a relevant ban has been applied on the permission(s) for this session.
     * @throws \Garden\Web\Exception\NotFoundException If the knowledge category could not be found.
     * @throws \Vanilla\Exception\PermissionException If the user does not have the specified permission(s).
     */
    public function patch(int $id, array $body = []): array {
        $this->permission("garden.setttings.manage");

        $this->idParamSchema();
        $in = $this->schema($this->knowledgeCategoryPostSchema())
            ->setDescription("Update an existing knowledge category.")
            ->addValidator("parentID", [$this->knowledgeCategoryModel, "validateParentID"]);
        $out = $this->schema($this->fullSchema(), "out");

        $body = $in->validate($body, true);

        $previousState = $this->knowledgeCategoryByID($id);

        $this->knowledgeCategoryModel->update($body, ["knowledgeCategoryID" => $id]);
        if (!empty($body['parentID']) && $body['parentID'] != $previousState['parentID']) {
            $this->knowledgeCategoryModel->updateCounts($id);
        }
        $row = $this->knowledgeCategoryByID($id);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Create a new knowledge category.
     *
     * @param array $body
     * @return array
     * @throws ValidationException If input validation fails.
     * @throws ValidationException If output validation fails.
     * @throws \Garden\Web\Exception\HttpException If a relevant ban has been applied on the permission(s) for this session.
     * @throws \Garden\Web\Exception\NotFoundException If the knowledge category could not be found.
     * @throws \Vanilla\Exception\PermissionException If the user does not have the specified permission(s).
     */
    public function post(array $body = []): array {
        $this->permission("garden.setttings.manage");

        $in = $this->schema($this->knowledgeCategoryPostSchema())
            ->setDescription("Create a new knowledge category.")
            ->addValidator("parentID", [$this->knowledgeCategoryModel, "validateParentID"]);
        $out = $this->schema($this->fullSchema(), "out");

        $body = $in->validate($body);

        $knowledgeCategoryID = $this->knowledgeCategoryModel->insert($body);
        if (!empty($body['parentID']) && $body['parentID'] != -1) {
            $this->knowledgeCategoryModel->updateCounts($body['parentID']);
        }
        $row = $this->knowledgeCategoryByID($knowledgeCategoryID);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }
}
