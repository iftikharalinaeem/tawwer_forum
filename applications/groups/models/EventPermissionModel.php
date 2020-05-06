<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Groups\Models;

/**
 * Model for handling permissions on events.
 */
class EventPermissionModel {

    /** @var string */
    const MANAGE_PERMISSION_NAME = 'Vanilla.Events.Manage';

    /** @var string */
    const VIEW_PERMISSION_NAME = 'Vanilla.Events.View';

    /** @var \Gdn_Session */
    private $session;

    /** @var \CategoryModel */
    private $categoryModel;

    /** @var \EventModel */
    private $eventModel;

    /** @var \GroupModel */
    private $groupModel;

    /** @var \PermissionModel */
    private $permissionModel;

    /**
     * DI.
     *
     * @param \Gdn_Session $session
     * @param \CategoryModel $categoryModel
     * @param \EventModel $eventModel
     * @param \GroupModel $groupModel
     * @param \PermissionModel $permissionModel
     */
    public function __construct(
        \Gdn_Session $session,
        \CategoryModel $categoryModel,
        \EventModel $eventModel,
        \GroupModel $groupModel,
        \PermissionModel $permissionModel
    ) {
        $this->session = $session;
        $this->categoryModel = $categoryModel;
        $this->eventModel = $eventModel;
        $this->groupModel = $groupModel;
        $this->permissionModel = $permissionModel;
    }

    /**
     * Define category permissions using the permission model.
     */
    public function defineCategoryPermissions() {
        $this->permissionModel->define(
            [
                'Vanilla.Events.Manage' => 'Garden.Community.Manage',
                'Vanilla.Events.View' => 'Vanilla.Discussions.View',
            ],
            'tinyint',
            'Category',
            'PermissionCategoryID'
        );
    }

    /**
     * Check if the user has permission for events in a parent resource.
     *
     * @param array $permissionNames The name of the permissions to check.
     * @param string $parentRecordType The parent record.
     * @param int $parentRecordID The parent record ID.
     * @return bool
     */
    public function hasParentPermissions(array $permissionNames, string $parentRecordType, int $parentRecordID): bool {
        if ($parentRecordType === 'category') {
            return $this->categoryModel::checkPermission($parentRecordID, $permissionNames);
        } else {
//            return $this->eventModel->checkPermission()
        }
    }
}
