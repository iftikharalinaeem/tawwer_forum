<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Contracts\RecordInterface;

/**
 * An instance of a event.
 */
class EventRecordType implements RecordInterface {

    const TYPE = "event";

    /** @var int */
    private $eventID;

    /**
     * Constructor.
     *
     * @param int $eventID
     */
    public function __construct(int $eventID) {
        $this->eventID = $eventID;
    }

    /**
     * @inheritDoc
     */
    public function getRecordID(): int {
        return $this->eventID;
    }

    /**
     * @inheritDoc
     */
    public function getRecordType(): string {
        return self::TYPE;
    }
}
