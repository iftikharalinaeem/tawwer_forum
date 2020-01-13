<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Webhooks\Events;

use Garden\Events\ResourceEvent;
use Ramsey\Uuid\Uuid;

/**
 * Ping event to verify deliverability to a webhook.
 */
class PingEvent extends ResourceEvent {

    /** Action used to represent when a ping is dispatched. */
    const ACTION_PING = "ping";

    /** @var string */
    private $uuid;

    /**
     * Setup the ping event.
     */
    public function __construct() {
        $this->uuid = Uuid::uuid4()->toString();
        parent::__construct(self::ACTION_PING, [
            "ping" => $this->getUUID()
        ]);
    }

    /**
     * Get the universally-unique identifier associated with this ping.
     *
     * @return string
     */
    public function getUUID(): string {
        return $this->uuid;
    }
}
