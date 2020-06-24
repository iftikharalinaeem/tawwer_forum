<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

/**
 * Trait CheckGlobalPermissionTrait
 * @package Vanilla\Knowledge\Controllers\Api
 */
trait CheckGlobalPermissionTrait {
    /**
     * @param string $permission
     */
    public function checkPermission(string $permission = '') {
        if (!empty($permission)) {
            $this->knowledgeBaseModel->checkGlobalPermission($permission);
        } else {
            parent::permission();
        }
    }
}
