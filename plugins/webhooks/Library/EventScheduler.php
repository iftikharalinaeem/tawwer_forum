<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Webhooks\Library;

use Garden\Events\ResourceEvent;
use Ramsey\Uuid\Uuid;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Webhooks\Jobs\DispatchEventJob;

/**
 * Scheduler wrapper to simplify adding event dispatch jobs for webhooks.
 */
class EventScheduler {

    /** @var SchedulerInterface */
    private $scheduler;

    /**
     * Setup the scheduler.
     *
     * @param SchedulerInterface $scheduler
     */
    public function __construct(SchedulerInterface $scheduler) {
        $this->scheduler = $scheduler;
    }

    /**
     * Schedule an event to be dispatched to a webhook.
     *
     * @param ResourceEvent $event
     * @param WebhookConfig $webhook
     * @param JobPriority|null $jobPriority
     * @param integer|null $delay
     * @return void
     */
    public function addDispatchEventJob(ResourceEvent $event, WebhookConfig $webhook, ?JobPriority $jobPriority = null, ?int $delay = null) {
        $message = [
            "action" => $event->getAction(),
            "deliveryID" => Uuid::uuid4()->toString(),
            "payload" => $event->getPayload(),
            "type" => $event->getType(),
            "webhookID" => $webhook->getWebhookID(),
            "webhookUrl" => $webhook->getUrl(),
            "webhookSecret" => $webhook->getSecret(),
        ];

        $this->scheduler->addJob(
            DispatchEventJob::class,
            $message,
            $jobPriority,
            $delay
        );
    }
}
