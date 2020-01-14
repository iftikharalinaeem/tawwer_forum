<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Test /api/v2/webhooks/:id/ping endpoint.
 */
class WebhooksPingTest extends AbstractAPIv2Test {

    /** @var MockHttpClient */
    private $httpClient;

    /**
     * Create a new webhook row.
     *
     * @param string $name
     * @param array|null $events
     * @return array
     */
    private function addWebhook(string $name, ?array $events = null): array {
        $result = $this->api()->post(
            "webhooks",
            [
                "name" => $name,
                "url" => "https://vanilla.test/webhook?name=".urlencode($name),
                "secret" => md5(time()),
                "events" => $events ?: ["*"],
            ]
        )->getBody();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function setup(): void {
        parent::setup();
        $this->httpClient = new MockHttpClient();
        static::container()->setInstance(HttpClient::class, $this->httpClient);
    }

    /**
     * {@inheritDoc}
     */
    public static function setupBeforeClass(): void {
        static::$addons = ["webhooks"];
        parent::setupBeforeClass();
    }

    /**
     * Test a basic webhook ping.
     *
     * @return void
     */
    public function testPing(): void {
        $webhook = $this->addWebhook(__FUNCTION__);

        $response = new HttpResponse(
            200,
            ["Content-Type" => "application/json"],
            json_encode(["success" => true])
        );
        $response->setRequest(new HttpRequest(HttpRequest::METHOD_POST));
        $this->httpClient->addMockResponse(
            $webhook["url"],
            $response,
            HttpRequest::METHOD_POST
        );

        $this->api()->post("webhooks/{$webhook['webhookID']}/pings");

        // We should have one recorded delivery attempt: the ping.
        $webhookDeliveries = $this->api()->get("webhooks/{$webhook['webhookID']}/deliveries")->getBody();
        $this->assertCount(1, $webhookDeliveries);
    }
}
