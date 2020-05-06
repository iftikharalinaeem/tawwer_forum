<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Groups\Models;

use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\ServerException;

/**
 * Simple class representing a row of group permissions.
 */
class GroupPermissions implements \JsonSerializable {

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

    const GROUP_PERMISSONS = [
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

    /** @var array Reasons why a permission is denied. */
    private $reasons = [];

    private $values = [
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
     * Get the permission values array.
     * @return array
     */
    public function toArray(): array {
        return $this->values;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return $this->toArray();
    }

    /**
     * Set a permission value.
     *
     * @param string $permissionName The name of the permission. Use the constants from this class.
     * @param bool $permissionValue The value of the permission.
     * @param string|null $reason Why the permission is not granted.
     *
     * @return $this Chaining.
     */
    public function setPermission(string $permissionName, bool $permissionValue, string $reason = null): GroupPermissions {
        $this->validatePermissionName($permissionName);
        $this->values[$permissionName] = $permissionValue;

        if (!$permissionValue) {
            $this->reasons[$permissionName] = $reason;
        }
        return $this;
    }

    /**
     * Return whether or not a particular permission is set.
     *
     * @param string $permissionName
     * @return bool
     */
    public function hasPermission(string $permissionName): bool {
        $this->validatePermissionName($permissionName);

        return $this->values[$permissionName];
    }

    /**
     * Check whether or not a particular permission is set. Throw an exception if not found.
     *
     * @param string $permissionName
     * @return bool
     */
    public function checkPermission(string $permissionName): bool {
        if (!$this->hasPermission($permissionName)) {
            $reason = $this->reasons[$permissionName] ?? null;
            if ($reason === null) {
                // These look backwards, but this is what the existing translation strings looked like.
                // they've been preserved through the refactoring.
                if (in_array($permissionName, [self::MEMBER, self::LEADER])) {
                    $reason = t(sprintf("You aren't a %s of this group.", strtolower($permissionName)));
                } else {
                    $reason = sprintf(t("You aren't allowed to %s this group."), t(strtolower($permissionName)));
                }
            } else {
                $reason = t($reason);
            }

            throw new ForbiddenException($reason);
        }
        return $this->values[$permissionName];
    }

    /**
     * Validate that a proper permission is being set.
     *
     * @param string $permissionName
     */
    private function validatePermissionName(string $permissionName) {
        if (!in_array($permissionName, self::GROUP_PERMISSONS)) {
            throw new ServerException(sprintf("%s is not a valid group permission", $permissionName));
        }
    }
}
