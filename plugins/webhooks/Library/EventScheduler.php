<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Webhooks\Library;

use Garden\Events\ResourceEvent;
use Gdn_Session as SessionInterface;
use Ramsey\Uuid\Uuid;
use UserModel;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Webhooks\Jobs\DispatchEventJob;

/**
 * Scheduler wrapper to simplify adding event dispatch jobs for webhooks.
 */
class EventScheduler {

    /** @var SchedulerInterface */
    private $scheduler;

    /** @var SessionInterface */
    private $session;

    /** @var UserModel */
    private $userModel;

    /**
     * Setup the scheduler.
     *
     * @param SchedulerInterface $scheduler
     */
    public function __construct(SchedulerInterface $scheduler, UserModel $userModel, SessionInterface $session) {
        $this->scheduler = $scheduler;
        $this->session = $session;
        $this->userModel = $userModel;
    }

    /**
     * Add a sender (user) to the job config.
     *
     * @return void
     */
    private function getSender(): array {
        if ($this->session->UserID) {
            $sender = $this->userModel->getFragmentByID($this->session->UserID);
        } else {
            $sender = $this->userModel->getGeneratedFragment(UserModel::GENERATED_FRAGMENT_KEY_GUEST);
        }
        return $sender;
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
            "sender" => $this->getSender(),
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
