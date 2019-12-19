<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Jobs;

use Ramsey\Uuid\Uuid;

/**
 * Ping a webhook.
 */
class PingWebhook extends WebhookEvent {

    private const EVENT_TYPE = "ping";

    /**
     * {@inheritDoc}
     */
    protected function getData(): array {
        return [
            "ping" => Uuid::uuid4()->toString(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getEventType(): string {
        return self::EVENT_TYPE;
    }
}
