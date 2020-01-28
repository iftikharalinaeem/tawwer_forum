<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Webhooks\Library;

use Garden\Events\ResourceEvent;
use Vanilla\Webhooks\Models\WebhookModel;

/**
 * Event dispatcher for webhooks.
 */
class EventDispatcher {

    /** @var array */
    private $registeredEvents = [];

    /** @var EventScheduler */
    private $scheduler;

    /** @var WebhookModel */
    private $webhookModel;

    /** @var array */
    private $webhooks;

    /**
     * Configure the instance.
     *
     * @param WebhookModel $webhookModel
     * @param EventScheduler $scheduler
     */
    public function __construct(WebhookModel $webhookModel, EventScheduler $scheduler) {
        $this->scheduler = $scheduler;
        $this->webhookModel = $webhookModel;
    }

    /**
     * Dispatch resource events to the relevant webhooks.
     *
     * @param ResourceEvent $event
     * @return void
     */
    public function dispatch(ResourceEvent $event) {
        $type = $this->eventFromClass(get_class($event));
        if ($type === null) {
            return;
        }

        $webhooks = $this->getWebhooksForEvent($type);
        foreach ($webhooks as $webhook) {
            $webhookConfig = new WebhookConfig($webhook);
            $this->scheduler->addDispatchEventJob($event, $webhookConfig);
        }
    }

    /**
     * Get active webhooks configured to receive a particular event type.
     *
     * @param string $event
     * @return array
     */
    private function getWebhooksForEvent(string $event): array {
        $event = strtolower($event);
        if (!isset($this->webhooks)) {
            $this->webhooks = $this->webhookModel->getActive();
        }

        $result = [];
        foreach ($this->webhooks as $webhook) {
            $events = $webhook["events"] ?? [];

            if (!is_array($events)) {
                continue;
            }

            if (in_array($event, $events) || in_array(WebhookModel::EVENT_WILDCARD, $events)) {
                $result[] = $webhook;
            }
        }

        return $result;
    }

    /**
     * Given an even class, return its associated event type.
     *
     * @param string $class
     * @return string|null
     */
    private function eventFromClass(string $class): ?string {
        $class = strtolower($class);
        $result = $this->registeredEvents[$class] ?? null;
        return $result;
    }

    /**
     * Given a resource event, determine its type based on standard naming conventions.
     *
     * @param string $class
     * @param string $event
     * @return void
     */
    public function registerEvent(string $class, string $event): void {
        $class = strtolower($class);
        $event = strtolower($event);
        $this->registeredEvents[$class] = $event;
    }
}
