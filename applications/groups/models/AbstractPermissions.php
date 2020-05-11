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
 * A simple base permission class.
 */
abstract class AbstractPermissions implements \JsonSerializable {

    /** @var array Current values of permissions. */
    protected $values = [];

    /** @var array Reasons why a permission is denied. */
    private $reasons = [];

    /**
     * Get the record type in use.
     *
     * @return string
     */
    abstract protected function getRecordType(): string;

    /**
     * Get the allowed list of permission names.
     *
     * @return string[]
     */
    abstract protected function getPermissionNames(): array;

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
    public function setPermission(string $permissionName, bool $permissionValue, string $reason = null): AbstractPermissions {
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
                $reason = $this->getDefaultReasonForPermission($permissionName);
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
        if (!in_array($permissionName, $this->getPermissionNames())) {
            throw new ServerException(sprintf("%s is not a valid %s permission", $permissionName, $this->getRecordType()));
        }
    }

    /**
     * Get the default reason for why a permission is not granted if one has not been provided.
     *
     * @param string $permissionName The permission name.
     *
     * @return string The reason.
     */
    protected function getDefaultReasonForPermission(string $permissionName): string {
        return sprintf(t('You need the %s permission to do that.'), $this->getRecordType().'.'.$permissionName);
    }
}
