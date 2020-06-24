<?php
/**
 * @author Dani M <dani.m@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Webhooks\Models;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vanilla\Webhooks\Library\EventDispatcher;
use Vanilla\Webhooks\Library\EventScheduler;
use Vanilla\Webhooks\Library\WebhookConfig;
use Vanilla\Webhooks\Mocks\MockDiscussionEvent;
use Vanilla\Webhooks\Models\WebhookModel;
use VanillaTests\SiteTestTrait;

/**
 * Test the EventDispatcher class.
 */
class EventDispatcherTest extends TestCase {

    use SiteTestTrait {
        setUpBeforeClass as private siteSetupBeforeClass;
    }

    /** @var EventDispatcher */
    private $eventDispatcher;

    /** @var MockObject */
    private $mockScheduler;

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
     * Provide data for testing basic resource event dispatching.
     *
     * @return array
     */
    public function provideTestResourceEventData(): array {
        $result = [
            "Wildcard-only hook" => [["*"], 1],
            "Discussion-only hook" => [["discussion"], 1],
            "Comment-only event" => [["comment"], 0],
            "Discussion and comment hook" => [["comment", "discussion"], 1],
        ];

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();

        /** @var WebhookModel */
        $this->webhookModel = $this->container()->get(WebhookModel::class);

        /** @var EventScheduler */
        $this->mockScheduler = $this->getMockBuilder(EventScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = new EventDispatcher($this->webhookModel, $this->mockScheduler);

        // Cleanup webhook data between tests.
        $this->webhookModel->delete(["webhookID >" => 0]);
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
     * Test basic webhook event scheduling.
     *
     * @param array $events
     * @param int $dispatches
     * @return void
     * @dataProvider provideTestResourceEventData
     */
    public function testResourceEvent(array $events, int $dispatches): void {
        $webhook = $this->addWebhook(__FUNCTION__, $events);
        $config = new WebhookConfig($webhook);
        $event = new MockDiscussionEvent(
            MockDiscussionEvent::ACTION_INSERT,
            ["foo" => "bar"]
        );

        $this->mockScheduler
            ->expects($this->exactly($dispatches))
            ->method("addDispatchEventJob")
            ->with($this->identicalTo($event), $config);

        $this->eventDispatcher->dispatch($event);
    }
}
