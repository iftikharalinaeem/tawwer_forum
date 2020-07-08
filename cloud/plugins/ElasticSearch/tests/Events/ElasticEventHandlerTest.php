<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Cloud\ElasticSearch\Events;

use Garden\Events\ResourceEvent;
use Vanilla\Cloud\ElasticSearch\ElasticEventHandler;
use Vanilla\Cloud\ElasticSearch\LocalElasticDeleteJob;
use Vanilla\Cloud\ElasticSearch\LocalElasticSingleIndexJob;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Webhooks\Library\EventScheduler;
use VanillaTests\Fixtures\Events\TestResourceEvent;
use VanillaTests\MinimalContainerTestCase;

/**
 * Test the EventDispatcher class.
 */
class EventDispatcherTest extends MinimalContainerTestCase {

    /** @var ElasticEventHandler */
    private $eventHandler;

    /** @var MockObject */
    private $mockScheduler;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();

        /** @var EventScheduler */
        $this->mockScheduler = $this->getMockBuilder(SchedulerInterface::class)
            ->getMock();

        $this->eventHandler = new ElasticEventHandler($this->mockScheduler);
    }

    /**
     * Test that insert resource events are queued properly.
     *
     * @param string $type
     *
     * @dataProvider provideInsertAndUpdate
     */
    public function testQueueInsertUpdate(string $type) {
        $event = new TestResourceEvent($type, ['testResource' => [
            'testResourceID' => 5262
        ]]);

        $this->mockScheduler
            ->expects($this->once())
            ->method("addJob")
            ->with(LocalElasticSingleIndexJob::class, [
                'apiUrl' => '/api/v2/testResources/5262',
                'apiParams' => ['expand' => ['crawl']],
                'indexName' => 'testResource'
            ]);

        $this->eventHandler->handleResourceEvent($event);
    }

    /**
     * @return string[][]
     */
    public function provideInsertAndUpdate(): array {
        return [
            ResourceEvent::ACTION_INSERT => [ResourceEvent::ACTION_INSERT],
            ResourceEvent::ACTION_UPDATE => [ResourceEvent::ACTION_UPDATE],
        ];
    }

    /**
     * Test that insert resource events are queued properly.
     */
    public function testQueueDelete() {
        $event = new TestResourceEvent(ResourceEvent::ACTION_DELETE, ['testResource' => [
            'testResourceID' => 5262
        ]]);

        $this->mockScheduler
            ->expects($this->once())
            ->method("addJob")
            ->with(LocalElasticDeleteJob::class, [
                'elasticIDs' => [5262],
                'indexName' => 'testResource'
            ]);

        $this->eventHandler->handleResourceEvent($event);
    }
}
