<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\Web\Exception\NotFoundException;
use Vanilla\Exception\PermissionException;
use Vanilla\Knowledge\Models\ArticleDraft;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\ApiUtils;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * API controller drafts related endpoints.
 */
trait ArticlesApiDrafts {
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
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $in = $this->schema($this->draftPostSchema(), "in")
            ->setDescription("Create a new article draft.");
        $out = $this->schema($this->fullDraftSchema(), "out");

        $body = $in->validate($body);
        $body["recordType"] = "article";
        if ($body['recordID'] ?? false) {
            //check if article exists and knowledge base is "published"
            $this->articleHelper->articleByID($body['recordID']);
        }

        $body = (new ArticleDraft($this->formatService))->prepareDraftFields($body);

        $draftID = $this->draftModel->insert($body);
        $row = $this->draftByID($draftID);
        $row = (new ArticleDraft($this->formatService))->normalizeDraftFields($row);
        $result = $out->validate($row);
        return $result;
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
        $this->permission("Garden.SignIn.Allow");
        $in = $this->schema([
            "draftID" => [
                "description" => "Target article draft ID.",
                "type" => "integer",
            ],
        ], "in")->setDescription("Delete an article draft.");
        $out = $this->schema([], "out");

        $draft = $this->draftByID($draftID, true);
        if ($draft["insertUserID"] !== $this->getSession()->UserID) {
            $this->permission("Garden.Settings.Manage");
        }
        $this->draftModel->delete(
            ["draftID" => $draft["draftID"]]
        );
    }

    /**
     * Get an article draft by its numeric ID.
     *
     * @param int $id Article ID.
     * @param bool $includeDeleted Include articles which belongs to knowledge base "deleted"
     *
     * @return array
     * @throws NotFoundException If the draft could not be found.
     * @throws ValidationException If the result fails schema validation.
     */
    private function draftByID(int $id, bool $includeDeleted = false): array {
        try {
            $draft = $this->draftModel->selectSingle([
                "draftID" => $id,
                "recordType" => "article",
            ]);
            if (!$includeDeleted && ($draft['recordID'] ?? false)) {
                //check if article exists and knowledge base is "published"
                $this->articleHelper->articleByID($draft['recordID']);
            }
        } catch (NoResultsException $e) {
            throw new NotFoundException("Draft");
        }
        return $draft;
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
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $in = $this->schema([
            "draftID" => [
                "description" => "Target article draft ID.",
                "type" => "integer",
            ],
        ], "in")->setDescription("Get a single article draft.");
        $out = $this->schema($this->fullDraftSchema(), "out");

        $draft = $this->draftByID($draftID, true);
        $draft = (new ArticleDraft($this->formatService))->normalizeDraftFields($draft);
        $result = $out->validate($draft);
        $this->applyFormatCompatibility($result, 'body', 'format');
        return $result;
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
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

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
            "expand?" => ApiUtils::getExpandDefinition(["insertUser", "updateUser"]),
        ], "in")->setDescription("List article drafts.")->requireOneOf(["articleID", "insertUserID"]);
        $out = $this->schema([
            ":a" => $this->fullDraftSchema(),
        ], "out");

        $query = $in->validate($query);
        if ($query['articleID'] ?? false) {
            //check if article exists and knowledge base is "published"
            $this->articleHelper->articleByID($query['articleID']);
        }

        $where = ["recordType" => "article"] + \Vanilla\ApiUtils::queryToFilters($in, $query);
        $options = ['orderFields' => 'dateUpdated', 'orderDirection' => 'desc'];
        $rows = $this->draftModel->get($where, $options);
        $rows = (new ArticleDraft($this->formatService))->normalizeDraftFields($rows, false);

        $expandUsers = $this->resolveExpandFields(
            $query,
            [
                "insertUser" => "insertUserID",
                "updateUser" => "updateUserID",
            ]
        );
        $this->userModel->expandUsers(
            $rows,
            $expandUsers
        );

        $result = $out->validate($rows);
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
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $this->schema(["draftID" => "Target article draft ID."], "in");
        $in = $this->schema($this->draftPostSchema(), "in")
            ->setDescription("Update an article draft.");
        $out = $this->schema($this->fullDraftSchema(), "out");

        $body = $in->validate($body, true);

        $body["recordType"] = "article";
        $body = (new ArticleDraft($this->formatService))->prepareDraftFields($body);

        $draft = $this->draftByID($draftID, true);
        if ($draft["insertUserID"] !== $this->getSession()->UserID) {
            $this->permission("Garden.Settings.Manage");
        }

        $this->draftModel->update($body, ["draftID" => $draftID]);
        $row = $this->draftByID($draftID, true);
        $row = (new ArticleDraft($this->formatService))->normalizeDraftFields($row);
        $result = $out->validate($row);
        return $result;
    }
}
