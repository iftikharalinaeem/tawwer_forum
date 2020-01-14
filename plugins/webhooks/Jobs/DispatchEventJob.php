<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Jobs;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\Job\LocalJobInterface;
use Vanilla\Utility\StringUtils;
use Vanilla\Webhooks\Models\WebhookDeliveryModel;

/**
 * Dispatch an event to a webhook.
 */
class DispatchEventJob implements LocalJobInterface {

    /** @var string */
    private $action;

    /** @var HttpClient */
    private $client;

    /** @var DeliveryModel */
    private $deliveryModel;

    /** @var int */
    private $delay;

    /** @var string */
    private $deliveryID;

    /** @var array */
    private $payload;

    /** @var JobPriority */
    private $priority;

    /** @var string */
    private $type;

    /** @var array|null */
    private $user;

    /** @var int */
    private $webhookID;

    /** @var  string */
    private $webhookSecret;

    /** @var string */
    private $webhookUrl;

    /**
     * Setup the job.
     *
     * @param HttpClient $client
     * @param DeliveryModel $deliveryModel
     */
    public function __construct(HttpClient $client, WebhookDeliveryModel $deliveryModel) {
        $this->client = $client;
        $this->deliveryModel = $deliveryModel;
    }

    /**
     * Send a single event to a webhook.
     *
     * @return bool
     */
    private function dispatchEvent(): bool {
        $body = [
            "action" => $this->action,
            "payload" => $this->payload,
            "site" => $this->site(),
        ];
        if (is_array($this->user)) {
            $body["user"] = $this->user;
        }

        $json = StringUtils::jsonEncodeChecked($body, \JSON_UNESCAPED_SLASHES);

        $headers = [
            "Content-Type" => "application/json",
            "X-Vanilla-Event" => $this->type,
            "X-Vanilla-ID" => $this->deliveryID,
            "X-Vanilla-Signature" => $this->bodySignature($json, $this->webhookSecret),
        ];

        $startTime = microtime(true);
        $response = $this->client->post(
            $this->webhookUrl,
            $json,
            $headers
        );
        $endTime = microtime(true);
        $requestDuration = ($endTime - $startTime) * 1000;

        $this->logRequest($response->getRequest());
        $this->logResponse($response, $requestDuration);

        return $response->isResponseClass("2xx");
    }

    /**
     * Get the configured action.
     *
     * @return string|null
     */
    public function getAction(): ?string {
        return $this->action;
    }

    /**
     * Get the event type.
     *
     * @return string|null
     */
    public function getType(): ?string {
        return $this->type;
    }

    /**
     * Generate the signature header for a particular payload.
     *
     * @param string $payload
     * @param string $secret
     * @return string
     */
    private function bodySignature(string $body, string $secret): string {
        $signature = hash_hmac("sha1", $body, $secret);
        $result = "sha1={$signature}";
        return $result;
    }

    /**
     * Execute all queued items.
     *
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus {
        $result = $this->dispatchEvent();
        return $result ? JobExecutionStatus::complete() : JobExecutionStatus::error();
    }

    /**
     * Setup the job.
     *
     * @param array $message The webhook event message.
     */
    public function setMessage(array $message) {
        $this->setType($message["type"]);
        $this->setAction($message["action"]);
        $this->setPayload($message["payload"]);
        $this->setWebhookID($message["webhookID"]);
        $this->setWebhookUrl($message["webhookUrl"]);
        $this->setWebhookSecret($message["webhookSecret"]);
        $this->setDeliveryID($message["deliveryID"]);

        $this->setUser($message["user"] ?? null);
    }

    /**
     * Set the event action identifier.
     *
     * @param string $action
     * @return self
     */
    private function setAction(string $action): self {
        $this->action = $action;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDelay(int $seconds) {
        $this->delay = $seconds;
    }

    /**
     * Set the universally-unique ID for this delivery attempt.
     *
     * @param string $deliveryID
     * @return self
     */
    private function setDeliveryID(string $deliveryID): self {
        $this->deliveryID = $deliveryID;
        return $this;
    }

    /**
     * Set the payload for this event.
     *
     * @param array $payload
     * @return self
     */
    private function setPayload(array $payload): self {
        $this->payload = $payload;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPriority(JobPriority $priority) {
        $this->priority = $priority;
    }

    /**
     * Set the user associated with the event.
     *
     * @param array $user
     * @return self
     */
    private function setUser(?array $user): self {
        $this->user = $user;
        return $this;
    }

    /**
     * Set the unique ID of the associated webhook.
     *
     * @param integer $webhookID
     * @return self
     */
    private function setWebhookID(int $webhookID): self {
        $this->webhookID = $webhookID;
        return $this;
    }

    /**
     * Set the shared secret for signinf the webhook event payload.
     *
     * @param string $webhookSecret
     * @return self
     */
    private function setWebhookSecret(string $webhookSecret): self {
        $this->webhookSecret = $webhookSecret;
        return $this;
    }

    /**
     * Set the webhook URL.
     *
     * @param string $webhookUrl
     * @return self
     */
    private function setWebhookUrl(string $webhookUrl): self {
        if (filter_var($webhookUrl, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException("Invalid webhook URL.");
        }
        $this->webhookUrl = $webhookUrl;
        return $this;
    }

    /**
     * Set the event type.
     *
     * @param string $type
     * @return self
     */
    private function setType(string $type): self {
        $this->type = $type;
        return $this;
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
     * Record request of an event dispatch attempt.
     *
     * @param HttpRequest $request
     * @return void
     */
    private function logRequest(HttpRequest $request): void {
        $row = [
            "webhookDeliveryID" => $this->deliveryID,
            "webhookID" => $this->webhookID,
            "requestHeaders" => $request->getHeaders(),
            "requestBody" => $request->getBody(),
        ];

        $this->deliveryModel->insert($row);
    }

    /**
     * Record response of an event dispatch attempt.
     *
     * @param HttpResponse $response
     * @param integer $requestDuration
     * @return void
     */
    private function logResponse(HttpResponse $response, int $requestDuration): void {
        $fields = [
            "responseHeaders" => $response->getHeaders(),
            "responseBody" => $response->getRawBody(),
            "responseCode" => $response->getStatusCode(),
            "requestDuration" => $requestDuration,
        ];

        $this->deliveryModel->update($fields, ["webhookDeliveryID" => $this->deliveryID]);
    }
}
