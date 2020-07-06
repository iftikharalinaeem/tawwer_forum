<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\EventManager;
use Garden\Events\GenericResourceEvent;
use Garden\Events\ResourceEvent;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Trait for asserting that events are dispatched properly.
 *
 * @method getMockBuilder(string $className)
 * @method atLeast(int $count)
 */
trait EventSpyTestTrait {

    /** @var MockObject */
    private $mockEventManager;

    /** @var bool */
    private $shouldStubContainer = true;

    /**
     * Setup.
     */
    public function setUpEventSpyTestTrait() {
        $this->mockEventManager = $this->getMockBuilder(EventManager::class)->getMock();

        if ($this->shouldStubContainer) {
            \Gdn::getContainer()->setInstance(EventManager::class, $this->mockEventManager);
        }
    }

    /**
     * @param ResourceEvent[] $events
     */
    private function assertEventsWillBeDispatched(array $events) {
        $calls = [];
        foreach ($events as $event) {
            $calls[] = [$event];
        }
        $this->mockEventManager
            ->expects($this->atLeast(count($events)))
            ->method("dispatch")
            ->withConsecutive(...$calls);
    }

    /**
     * Generate an excepected event.
     *
     * @param string $type
     * @param string $action
     * @param array $payload
     * @return ResourceEvent
     */
    private function expectedResourceEvent(string $type, string $action, array $payload): ResourceEvent {
        return new GenericResourceEvent($type, $action, [
            $type => $payload,
        ], $this->getCurrentUser());
    }

    /**
     * Get the current user.
     */
    private function getCurrentUser() {
        return \Gdn::userModel()->currentFragment();
    }
}
