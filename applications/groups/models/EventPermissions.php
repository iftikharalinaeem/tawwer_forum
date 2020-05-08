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
final class EventPermissions extends AbstractPermissions {

    const ORGANIZER = 'Organizer';

    const CREATE = 'Create';

    const EDIT = 'Edit';

    const MEMBER = 'Member';

    const VIEW = 'View';

    protected $values = [
        self::ORGANIZER => false,
        self::CREATE => false,
        self::EDIT => false,
        self::MEMBER => false,
        self::VIEW => false,
    ];

    /**
     * @inheritdoc
     */
    protected function getRecordType(): string {
        return \EventRecordType::TYPE;
    }

    /**
     * @inheritdoc
     */
    protected function getPermissionNames(): array {
        return [
            self::ORGANIZER,
            self::CREATE,
            self::EDIT,
            self::MEMBER,
            self::VIEW,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getDefaultReasonForPermission(string $permissionName): string {
        // These look backwards, but this is what the existing translation strings looked like.
        // they've been preserved through the refactoring.
        if (in_array($permissionName, [self::ORGANIZER, self::MEMBER])) {
            $message = t(sprintf("You aren't a %s of this event.", strtolower($permissionName)));
        } else {
            $message = sprintf(t("You aren't allowed to %s this event."), t(strtolower($permissionName)));
        }
        return parent::getDefaultReasonForPermission($permissionName);
    }
}
