<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Webhooks\Library;

use Garden\Events\ResourceEvent;
use Garden\Http\HttpRequest;
use Gdn_Session as SessionInterface;
use Ramsey\Uuid\Uuid;
use Vanilla\Contracts\Models\UserProviderInterface;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Utility\StringUtils;
use Vanilla\Webhooks\Jobs\DeliveryFeedbackJob;
use Vanilla\Webhooks\Jobs\HttpRequestJob;
use Vanilla\Webhooks\Jobs\LogDeliveryJob;
use Vanilla\Webhooks\Jobs\RemoteRequestJob;

/**
 * Scheduler wrapper to simplify adding event dispatch jobs for webhooks.
 */
class EventScheduler {

    /** @var SchedulerInterface */
    private $scheduler;

    /** @var SessionInterface */
    private $session;

    /** @var bool */
    private $useHostedQueue = false;

    /** @var UserProviderInterface */
    private $userProvider;

    /**
     * Setup the scheduler.
     *
     * @param SchedulerInterface $scheduler
     * @param UserProviderInterface $userProvider
     * @param SessionInterface $session
     */
    public function __construct(
        SchedulerInterface $scheduler,
        UserProviderInterface $userProvider,
        SessionInterface $session
    ) {
        $this->scheduler = $scheduler;
        $this->session = $session;
        $this->userProvider = $userProvider;
    }

    /**
     * Add a sender (user) to the job config.
     *
     * @return void
     */
    private function getSender(): array {
        if ($this->session->UserID) {
            $sender = $this->userProvider->getFragmentByID($this->session->UserID);
        } else {
            $sender = $this->userProvider->getGeneratedFragment(\UserModel::GENERATED_FRAGMENT_KEY_GUEST);
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
        $deliveryID = Uuid::uuid4()->toString();
        $message = $this->generateJobMessage($event, $webhook, $deliveryID);

        $this->scheduler->addJob(
            $this->shouldUseHostedQueue() ? RemoteRequestJob::class : HttpRequestJob::class,
            $message,
            $jobPriority,
            $delay
        );
    }

    /**
     * Generate the job message, given an event and a webhook config.
     *
     * @param ResourceEvent $event
     * @param WebhookConfig $webhook
     * @param string $deliveryID
     * @return array
     */
    public function generateJobMessage(ResourceEvent $event, WebhookConfig $webhook, string $deliveryID): array {

        $body = [
            "action" => $this->getActionFromEvent($event),
            "payload" => $event->getPayload(),
            "sender" => $this->getSender(),
            "site" => $this->site(),
        ];

        array_walk_recursive($body, function (&$value) {
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format(\DATE_ATOM);
            }
        });
        $json = StringUtils::jsonEncodeChecked($body, \JSON_UNESCAPED_SLASHES);

        $result = [
            "body" => $json,
            "feedbackJob" => $this->shouldUseHostedQueue() ? DeliveryFeedbackJob::class : LogDeliveryJob::class,
            "feedbackMessage" => [
                "webhookDeliveryID" => $deliveryID,
                "webhookID" => $webhook->getWebhookID(),
            ],
            "headers" => [
                "Content-Type" => "application/json",
                "X-Vanilla-Event" => $event->getType(),
                "X-Vanilla-ID" => $deliveryID,
                "X-Vanilla-Signature" => $this->generateSignature($json, $webhook->getSecret()),
            ],
            "method" => HttpRequest::METHOD_POST,
            "uri" => $webhook->getUrl(),
        ];

        return $result;
    }

    /**
     * Generate a relevant action slug to be delivered alongside the event.
     *
     * @param ResourceEvent $event
     * @return string
     */
    private function getActionFromEvent(ResourceEvent $event): string {
        $action = $event->getAction();
        $class = strtolower((new \ReflectionClass($event))->getShortName());
        $resourceBoundary = strrpos($class, "event");

        if ($resourceBoundary === false || $resourceBoundary < 1) {
            return $action;
        }

        $resource = substr($class, 0, $resourceBoundary);
        $result = "{$resource}_{$action}";
        return $result;
    }

    /**
     * Generate a signature for a value.
     *
     * @param string $value
     * @param string $secret
     * @return string
     */
    private function generateSignature(string $value, string $secret): string {
        $signature = hash_hmac("sha1", $value, $secret);
        $result = "sha1={$signature}";
        return $result;
    }

    /**
     * Should the hosted queue be used for event deliveries?
     *
     * @return boolean
     */
    public function shouldUseHostedQueue(): bool {
        return $this->useHostedQueue;
    }

    /**
     * Get site details to include alongside event data.
     *
     * @return array
     */
    private function site(): array {
        if (class_exists('\Infrastructure')) {
            $result = ["siteID" => \Infrastructure::site("siteid")];
        } else {
            $result = ["siteID" => 0];
        }
        return $result;
    }

    /**
     * Define whether or not the hosted queue should be use to schedule event deliveries.
     *
     * @param boolean $useHostedQueue
     * @return void
     */
    public function useHostedQueue(bool $useHostedQueue): void {
        $this->useHostedQueue = $useHostedQueue;
    }
}
