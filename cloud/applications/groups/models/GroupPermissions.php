<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Groups\Models;

/**
 * Simple class representing a row of group permissions.
 */
final class GroupPermissions extends AbstractPermissions {

    const ACCESS = 'Access';

    const MEMBER = 'Member';

    const LEADER = 'Leader';

    const APPLY = 'Apply';

    const JOIN = 'Join';

    const LEAVE = 'Leave';

    const EDIT = 'Edit';

    const DELETE = 'Delete';

    const MODERATE = 'Moderate';

    const VIEW = 'View';

    protected $values = [
        self::ACCESS => true,
        self::MEMBER => false,
        self::LEADER => false,
        self::APPLY => false,
        self::JOIN => false,
        self::LEAVE => false,
        self::EDIT => false,
        self::DELETE => false,
        self::MODERATE => false,
        self::VIEW => true,
    ];

    /**
     * @inheritdoc
     */
    protected function getRecordType(): string {
        return \GroupModel::RECORD_TYPE;
    }

    /**
     * @inheritdoc
     */
    protected function getPermissionNames(): array {
        return [
            self::ACCESS,
            self::MEMBER,
            self::LEADER,
            self::APPLY,
            self::JOIN,
            self::LEAVE,
            self::EDIT,
            self::DELETE,
            self::MODERATE,
            self::VIEW,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultReasonForPermission(string $permissionName): string {
        // These look backwards, but this is what the existing translation strings looked like.
        // they've been preserved through the refactoring.
        if (in_array($permissionName, [self::MEMBER, self::LEADER])) {
            $reason = t(sprintf("You aren't a %s of this group.", strtolower($permissionName)));
        } else {
            $reason = sprintf(t("You aren't allowed to %s this group."), t(strtolower($permissionName)));
        }
        return parent::getDefaultReasonForPermission($permissionName);
    }
}
