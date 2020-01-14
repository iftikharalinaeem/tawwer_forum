<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Jobs;

use Garden\Http\HttpClient;
use Garden\Http\HttpResponse;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\Job\LocalJobInterface;
use Vanilla\Webhooks\Models\DeliveryModel;

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
    public function __construct(HttpClient $client, DeliveryModel $deliveryModel) {
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

        $json = json_encode($body);

        $headers = [
            "Content-Type" => "application/json",
            "X-Vanilla-Event" => $this->type,
            "X-Vanilla-ID" => $this->deliveryID,
            "X-Vanilla-Signature" => $this->payloadSignature($json, $this->webhookSecret),
        ];

        $startTime = microtime(true);
        $response = $this->client->post(
            $this->webhookUrl,
            $json,
            $headers
        );
        $endTime = microtime(true);
        $requestDuration = ($endTime - $startTime) * 1000;

        $this->writeDeliveryRecord(
            $this->deliveryID,
            $this->webhookID,
            $response,
            $requestDuration
        );

        return $response->isResponseClass("2xx");
    }

    /**
     * Given an array of HTTP headers, format them as a string.
     *
     * @param array $headers
     * @return string
     */
    private function formatHeaderArray(array $headers): string {
        $result = [];
        foreach ($headers as $header => $values) {
            foreach ($values as $value) {
                $result[] = "{$header}: {$value}";
            }
        }
        return implode("\n", $result);
    }

    /**
     * Generate the signature header for a particular payload.
     *
     * @param string $payload
     * @param string $secret
     * @return string
     */
    private function payloadSignature(string $payload, string $secret): string {
        $signature = hash_hmac("sha1", json_encode($payload), $secret);
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
     * Record details of a webhook event delivery attempt.
     *
     * @param string $deliveryID
     * @param integer $webhookID
     * @param HttpResponse $response
     * @param integer $requestDuration
     * @return void
     */
    private function writeDeliveryRecord(string $deliveryID, int $webhookID, HttpResponse $response, int $requestDuration): void {
        $request = $response->getRequest();
        $row = [
            "deliveryID" => $deliveryID,
            "webhookID" => $webhookID,
            "request" => [
                "headers" => $this->formatHeaderArray($request->getHeaders()),
                "body" => $request->getBody(),
            ],
            "response" => [
                "headers" => $this->formatHeaderArray($response->getHeaders()),
                "body" => $response->getBody(),
            ],
            "responseCode" => $response->getStatusCode(),
            "requestDuration" => $requestDuration,
        ];

        $this->deliveryModel->insert($row);
    }
}
