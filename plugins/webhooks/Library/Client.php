<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Library;

use Garden\Http\HttpClient;
use Garden\Http\HttpResponse;
use Ramsey\Uuid\Uuid;
use Vanilla\Webhooks\Models\DeliveryModel;

/**
 * Client for dispatching events to webhooks.
 */
class Client extends HttpClient {

    /** @var DeliveryModel */
    private $deliveryModel;

    /**
     * Configure the instance.
     *
     * @param DeliveryModel $deliveryModel
     */
    public function __construct(DeliveryModel $deliveryModel) {
        $this->deliveryModel = $deliveryModel;
        parent::__construct();
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
     * Send a single event to a webhook.
     *
     * @param int $webhookID
     * @param string $url
     * @param array $data
     * @param string $event
     * @param string $secret
     * @return bool
     */
    public function sendEvent(int $webhookID, string $url, array $data, string $event, string $secret): bool {
        $deliveryID = Uuid::uuid4()->toString();
        $payload = json_encode($data);

        $headers = [
            "Content-Type" => "application/json",
            "X-Vanilla-Event" => $event,
            "X-Vanilla-ID" => $deliveryID,
            "X-Vanilla-Signature" => $this->payloadSignature($payload, $secret),
        ];

        $response = $this->post(
            $url,
            $payload,
            $headers
        );

        $this->writeDeliveryRecord(
            $deliveryID,
            $webhookID,
            $response
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
     * Record details of a webhook event delivery attempt.
     *
     * @param string $deliveryID
     * @param integer $webhookID
     * @param HttpResponse $response
     * @return void
     */
    private function writeDeliveryRecord(string $deliveryID, int $webhookID, HttpResponse $response): void {
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
        ];

        $this->deliveryModel->insert($row);
    }
}
