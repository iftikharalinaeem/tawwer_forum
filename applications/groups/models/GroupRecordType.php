<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Navigation;

use Vanilla\Contracts\RecordInterface;

/**
 * An instance of a group.
 */
class GroupRecordType implements RecordInterface {

    const TYPE = "group";

    /** @var int */
    private $groupID;

    /**
     * Constructor.
     *
     * @param int $groupID
     */
    public function __construct(int $groupID) {
        $this->groupID = $groupID;
    }

    /**
     * @inheritDoc
     */
    public function getRecordID(): int {
        return $this->groupID;
    }

    /**
     * @inheritDoc
     */
    public function getRecordType(): string {
        return self::TYPE;
    }
}
