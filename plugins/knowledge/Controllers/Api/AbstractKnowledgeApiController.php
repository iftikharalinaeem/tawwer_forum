<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Vanilla\Knowledge\Models\KnowledgeBaseModel;

/**
 * Base controller class for API endpoints in the Knowledge addon.
 */
abstract class AbstractKnowledgeApiController extends \AbstractApiController {
    /**
     * Given an article row, determine if the current user has permission to modify it.
     *
     * @param int $insertUserID Numeric ID of the user who created the article.
     * @throws \Garden\Web\Exception\HttpException If a ban has been applied on the permission(s) for this session.
     * @throws \Vanilla\Exception\PermissionException If the user does not have access to edit the article.
     */
    protected function editPermission(int $insertUserID) {
        $this->permission(KnowledgeBaseModel::EDIT_PERMISSION);
    }
}
