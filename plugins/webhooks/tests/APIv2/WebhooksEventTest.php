<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\EventManager;
use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use ReflectionClass;
use Vanilla\Scheduler\Job\JobInterface;
use Vanilla\Scheduler\TrackingSlip;
use Vanilla\Webhooks\Events\PingEvent;
use Vanilla\Webhooks\Jobs\DispatchEventJob;
use Vanilla\Webhooks\Mocks\DiscussionEvent;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Test dispatching events to webhooks.
 */
class WebhooksEventTest extends AbstractAPIv2Test {

    /** @var array */
    private $dispatchedTrackingSlips = [];

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

        $eventManager = static::container()->get(EventManager::class);
        $eventManager->bind("SchedulerDispatched", function (array $trackingSlips) {
            $this->dispatchedTrackingSlips = $trackingSlips;
        });
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
    public function testResourceEvent(): void {
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

        /** @var EventManager */
        $eventManager = $this->container()->get(EventManager::class);
        $event = new DiscussionEvent(DiscussionEvent::ACTION_INSERT, ["foo" => "bar"]);
        $eventManager->dispatch($event);

        $this->assertWebhookEventDispatched("discussion", DiscussionEvent::ACTION_INSERT);
    }

    /**
     * Test a basic webhook ping.
     *
     * @return void
     */
    public function testPingEndpoint(): void {
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

        $this->assertWebhookEventDispatched("ping", PingEvent::ACTION_PING);
    }

    /**
     * Verify a particular type of event-dispatch job was scheduled.
     *
     * @param string $type
     * @param string $action
     * @return void
     */
    private function assertWebhookEventDispatched(string $type, string $action): void {
        $result = false;

        /** @var TrackingSlip $trackingSlip */
        foreach ($this->dispatchedTrackingSlips as $trackingSlip) {
            $driverSlip = $trackingSlip->getDriverSlip();

            // Gotta do this the hard way, since jobs are instanced outside the container.
            $reflection = new ReflectionClass($driverSlip);
            $property = $reflection->getProperty("job");
            $property->setAccessible(true);

            /** @var JobInterface */
            $job = $property->getValue($driverSlip);
            if (!($job instanceof DispatchEventJob)) {
                continue;
            } elseif ($job->getType() === $type && $job->getAction() === $action) {
                $result = true;
                break;
            }
        }

        $this->assertTrue($result, "{$type}-{$action} was not dispatched to any webhook.");
    }
}
