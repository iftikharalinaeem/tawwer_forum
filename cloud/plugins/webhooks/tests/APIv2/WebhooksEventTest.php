<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Vanilla\Webhooks\Events\PingEvent;
use Vanilla\Webhooks\Library\EventScheduler;
use Vanilla\Webhooks\Library\WebhookConfig;
use Vanilla\Webhooks\Models\WebhookModel;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Test dispatching events to webhooks.
 */
class WebhooksEventTest extends AbstractAPIv2Test {

    /** @var MockObject */
    private $mockScheduler;

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

        /** @var EventScheduler */
        $this->mockScheduler = $this->getMockBuilder(EventScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container()->setInstance(EventScheduler::class, $this->mockScheduler);

        $webhookModel = $this->container()->get(WebhookModel::class);
        $webhookModel->delete(["webhookID >" => 0]);
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
    public function testPingEndpoint(): void {
        $webhook = $this->addWebhook(__FUNCTION__);
        $config = new WebhookConfig($webhook);

        $this->mockScheduler
            ->expects($this->once())
            ->method("addDispatchEventJob")
            ->with($this->callback(function ($value) {
                    return $value instanceof PingEvent;
            }), $config);

        $this->api()->post("webhooks/{$webhook['webhookID']}/pings");
    }
}
