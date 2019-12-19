<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Library;

use Garden\Http\HttpClient;
use Ramsey\Uuid\Uuid;

/**
 * Client for dispatching events to webhooks.
 */
class Client extends HttpClient {

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
     * @param string $url
     * @param array $data
     * @param string $event
     * @param string $secret
     * @return void
     */
    public function sendEvent(string $url, array $data, string $event, string $secret) {
        $deliveryID = Uuid::uuid4()->toString();
        $payload = json_encode($data);

        $headers = [
            "Content-Type" => "application/json",
            "X-Vanilla-Event" => $event,
            "X-Vanilla-ID" => $deliveryID,
            "X-Vanilla-Signature" => $this->payloadSignature($payload, $secret),
        ];

        $this->post(
            $url,
            $payload,
            $headers
        );
    }
}
