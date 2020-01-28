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

    /** @var EventScheduler */
    private $scheduler;

    /** @var WebhookModel */
    private $webhookModel;

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
        $type = $this->typeFromResourceEvent($event);
        if ($type === null) {
            return;
        }

        $webhooks = $this->webhookModel->getByEvent($type);
        foreach ($webhooks as $webhook) {
            $webhookConfig = new WebhookConfig($webhook);
            $this->scheduler->addDispatchEventJob($event, $webhookConfig);
        }
    }

    /**
     * Given a resource event, determine its type based on standard naming conventions.
     *
     * @param ResourceEvent $event
     * @return string|null
     */
    public function typeFromResourceEvent(ResourceEvent $event): ?string {
        $name = strtolower(get_class($event));
        if (($basenamePosition = strrpos($name, "\\")) !== false) {
            $name = substr($name, $basenamePosition + 1);
        }
        if (substr($name, -5) !== "event") {
            return null;
        }
        $result = substr($name, 0, -5);
        return $result;
    }
}
