<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use VanillaTests\Groups\Utils\GroupsAndEventsApiTestTrait;

/**
 * Tests for parent resources of events.
 */
class EventParentTest extends AbstractAPIv2Test {

    protected static $addons = ['vanilla', 'groups'];
    use GroupsAndEventsApiTestTrait;

    /**
     * Test that we can insert events into groups, and fetch them back.
     */
    public function testParentGroup() {
        $this->createGroup();
        $this->createEvent();

        $event = $this->api()->get("/events/".$this->lastInsertedEventID)->getBody();

        $this->assertEquals($this->lastInsertedGroupID, $event['parentRecordID']);
        $this->assertEquals('group', $event['parentRecordType']);

        // Backwards compatibility
        $this->assertEquals($this->lastInsertedGroupID, $event['groupID']);
    }

    /**
     * Test that we can insert events into groups, and fetch them back.
     */
    public function testParentCategory() {
        $this->createCategory();
        $this->createEvent();

        $event = $this->api()->get("/events/".$this->lastInsertedEventID)->getBody();

        $this->assertEquals($this->lastInsertedCategoryID, $event['parentRecordID']);
        $this->assertEquals('category', $event['parentRecordType']);

        // Backwards compatibility
        $this->assertFalse(isset($event['groupID']));
    }
}
