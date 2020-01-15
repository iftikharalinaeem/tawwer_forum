<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Webhooks\Events;

use Garden\Events\ResourceEvent;

/**
 * Ping event to verify deliverability to a webhook.
 */
class PingEvent extends ResourceEvent {

    /** Action used to represent when a ping is dispatched. */
    public const ACTION_PING = "ping_add";
}
