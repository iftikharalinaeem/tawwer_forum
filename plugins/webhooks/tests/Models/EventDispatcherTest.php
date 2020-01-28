<?php
/**
 * @author Dani M <dani.m@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Webhooks;

use Garden\EventManager;
use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Webhooks\Jobs\DispatchEventJob;
use Vanilla\Webhooks\Library\EventDispatcher;
use Vanilla\Webhooks\Mocks\MockDiscussionEvent;
use Vanilla\Webhooks\Models\WebhookModel;
use VanillaTests\Fixtures\MockHttpClient;
use VanillaTests\SiteTestTrait;

/**
 * Test the EventDispatcher class.
 */
class EventDispatcherTest extends TestCase {

    use SiteTestTrait {
        setUpBeforeClass as private siteSetupBeforeClass;
    }

    /** @var array */
    private $dispatchedTrackingSlips = [];

    /** @var EventDispatcher */
    private $eventDispatcher;

    /** @var MockHttpClient */
    private $httpClient;

    /** @var WebhookModel */
    private $webhookModel;

    /**
     * Create a new webhook row.
     *
     * @param string $name
     * @param array|null $events
     * @return array
     */
    private function addWebhook(string $name, ?array $events = null): array {
        $webhookID = $this->webhookModel->insert([
                "name" => $name,
                "url" => "https://vanilla.test/webhook?name=".urlencode($name),
                "secret" => md5(time()),
                "events" => $events ?: ["*"],
        ]);
        $result = $this->webhookModel->selectSingle(["webhookID" => $webhookID]);
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();
        $this->eventDispatcher = $this->container()->get(EventDispatcher::class);
        $this->webhookModel = $this->container()->get(WebhookModel::class);

        $this->httpClient = new MockHttpClient();
        static::container()->setInstance(HttpClient::class, $this->httpClient);

        /** @var EventManager */
        $eventManager = $this->container()->get(EventManager::class);
        $eventManager->bind("SchedulerDispatched", function (array $trackingSlips) {
            $this->dispatchedTrackingSlips = $trackingSlips;
        });
    }

    /**
     * {@inheritDoc}
     */
    public static function setUpBeforeClass(): void {
        static::$addons = ["webhooks"];
        parent::setUpBeforeClass();
        self::siteSetupBeforeClass();
    }

    /**
     * Test a basic webhook ping.
     *
     * @return void
     */
    public function testResourceEvent(): void {
        $webhook = $this->addWebhook(__FUNCTION__);
        $this->eventDispatcher->registerEvent(MockDiscussionEvent::class, "discussion");

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
        $event = new MockDiscussionEvent(MockDiscussionEvent::ACTION_INSERT, ["foo" => "bar"]);
        $scheduler = $this->container()->get(SchedulerInterface::class);
        $eventManager->dispatch($event);

        $this->assertWebhookEventDispatched("discussion", MockDiscussionEvent::ACTION_INSERT);
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
