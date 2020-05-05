<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use DateTime;
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

    /**
     * Test GET /events with parentRecordType of Group.
     */
    public function testGetEventsWithGroupRecordType() {
        $this->createGroup();
        $this->createEvent();
        $this->createEvent();
        $this->createEvent();

        $events = $this->api()->get(
            "/events",
            [
                "parentRecordType" => \EventModel::PARENT_TYPE_GROUP,
                "parentRecordID" => $this->lastInsertedGroupID
            ]
        )->getBody();

        $parentRecordTypes = array_column($events, 'parentRecordType');
        $parentRecordTypes = array_unique($parentRecordTypes);

        $this->assertEquals(3, count($events));
        $this->assertEquals(\EventModel::PARENT_TYPE_GROUP, $parentRecordTypes[0]);
    }
    /**
     * Test GET /events with parentRecordType of Category.
     */
    public function testGetEventsWithCategoryRecordType() {
        $this->createCategory();
        $this->createEvent();
        $this->createEvent();

        $events = $this->api()->get(
            "/events",
            [
                "parentRecordType" => \EventModel::PARENT_TYPE_CATEGORY,
                "parentRecordID" => $this->lastInsertedCategoryID,
            ]
        )->getBody();

        $parentRecordTypes = array_column($events, 'parentRecordType');
        $parentRecordTypes = array_unique($parentRecordTypes);

        $this->assertEquals(2, count($events));
        $this->assertEquals(\EventModel::PARENT_TYPE_CATEGORY, $parentRecordTypes[0]);
    }

    /**
     * Test GET/ events with allDayEvent filter.
     */
    public function testGetEventsAllDayEvent() {
        $this->createGroup();
        $this->createEvent(['dateEnds' => new DateTime('2 days')]);
        $this->createEvent();

        $events = $this->api()->get(
            "/events",
            [
                "parentRecordType" => \EventModel::PARENT_TYPE_GROUP,
                "parentRecordID" => $this->lastInsertedGroupID,
                "allDayEvent" => true,
            ]
        )->getBody();

        $this->assertEquals(1, count($events));
    }

    /**
     * Test GET/ events with dateStarts filter.
     *
     * @param $dates
     * @param $queryParam
     * @param $expected
     * @dataProvider provideDateStartsFilter
     */
    public function testGetEventsDateStartsFilter($dates, $queryParam, $expected) {
        $this->createGroup();

        $this->createEvent(['dateStarts' => $dates[0]]);
        $this->createEvent(['dateStarts' => $dates[1]]);
        $this->createEvent(['dateStarts' =>  $dates[2]]);

        $events = $this->api()->get(
            "/events",
            [
                "parentRecordType" => \EventModel::PARENT_TYPE_GROUP,
                "parentRecordID" => $this->lastInsertedGroupID,
                "dateStarts" => $queryParam,
            ]
        )->getBody();

        $this->assertEquals($expected, count($events));
    }

    /**
     * Test GET/ events with dateEnds filter.
     *
     * Provide date tests.
     *
     * @return array Returns a data provider.
     */
    public function provideDateStartsFilter() {
        $data = [
            [['2020-01-01 05:05:05', '2020-01-01 05:05:05', '2020-02-01 06:06:06'], '=2020-01-01 05:05:05', 2],
            [['2019-01-01 05:05:05', '2020-02-02 05:05:05', '2020-02-01 06:06:06'], '<2020-01-01 05:05:05', 1],
            [['2020-01-01 05:05:05', '2020-02-02 05:05:05', '2020-03-03 06:06:06'], '>2020-01-01 05:05:05', 2],
            [['2020-01-01 05:05:05', '2020-05-05 07:05:05', '2020-07-07 07:06:06'], '>=2020-01-01 05:05:05', 3],
            [['2020-01-01 05:05:05', '2020-05-05 07:05:05', '2020-07-07 07:06:06'], '<=2020-05-05 07:05:05', 2],
            [['2020-01-01 05:05:05', '2020-05-05 07:05:05', '2020-07-07 07:06:06'], '[2020-04-04 07:05:05, 2020-08-08 07:06:06]', 2],

        ];
        return $data;
    }

    /**
     * Test GET/ events dateEnds filters.
     *
     * @param $dateStarts
     * @param $dateEnds
     * @param $queryParam
     * @param $expected
     * @dataProvider provideDateEndsFilter
     */
    public function testGetEventsDateEndsFilter($dateStarts, $dateEnds, $queryParam, $expected) {
        $this->createCategory();

        $this->createEvent(['dateStarts' => $dateStarts[0], 'dateEnds' => $dateEnds[0]]);
        $this->createEvent(['dateStarts' => $dateStarts[1], 'dateEnds' => $dateEnds[1]]);
        $this->createEvent(['dateStarts' => $dateStarts[2], 'dateEnds' => $dateEnds[2]]);

        $events = $this->api()->get(
            "/events",
            [
                "parentRecordType" => \EventModel::PARENT_TYPE_CATEGORY,
                "parentRecordID" => $this->lastInsertedCategoryID,
                "dateEnds" => $queryParam,
            ]
        )->getBody();

        $this->assertEquals($expected, count($events));
    }

    /**
     * Provide dates for testGetEventsDateEndsFilter.
     *
     * @return array Returns a data provider.
     */
    public function provideDateEndsFilter() {
        $data = [
            [
                ['2020-01-01 05:05:05', '2020-01-01 05:05:05', '2020-02-01 06:06:06'],
                ['2020-05-05 05:05:05', '2020-06-06 06:06:06', '2020-07-07 07:07:07'],
                '=2020-05-05 05:05:05',
                1
            ],
            [
                ['2020-01-01 05:05:05', '2020-01-01 05:05:05', '2020-02-01 06:06:06'],
                ['2020-05-05 05:05:05', '2020-06-06 06:06:06', '2020-07-07 07:07:07'],
                '<2020-07-07 07:07:07',
                2
            ],
            [
                ['2020-01-01 05:05:05', '2020-01-01 05:05:05', '2020-02-01 06:06:06'],
                ['2020-05-05 05:05:05', '2020-06-06 06:06:06', '2020-07-07 07:07:07'],
                '>2020-05-05 05:05:05',
                2
            ],
            [
                ['2020-01-01 05:05:05', '2020-01-01 05:05:05', '2020-02-01 06:06:06'],
                ['2020-05-05 05:05:05', '2020-06-06 06:06:06', '2020-07-07 07:07:07'],
                '>=2020-05-05 05:05:05',
                3
            ],
            [
                ['2020-01-01 05:05:05', '2020-01-01 05:05:05', '2020-02-01 06:06:06'],
                ['2020-05-05 05:05:05', '2020-06-06 06:06:06', '2020-07-07 07:07:07'],
                '<=2020-06-06 06:06:06',
                2
            ],
            [
                ['2020-01-01 05:05:05', '2020-01-01 05:05:05', '2020-02-01 06:06:06'],
                ['2020-05-05 05:05:05', '2020-06-06 06:06:06', '2020-07-07 07:07:07'],
                '[2020-04-05 05:05:05, 2020-08-07 07:07:07]',
                3
            ],
        ];
        return $data;
    }

    /**
     * Test GET/ events with dateStarts and dateEnds filters.
     *
     * @param $dateStarts
     * @param $dateEnds
     * @param $queryParam
     * @param $expected
     * @dataProvider provideDatesFilter
     */
    public function testGetEventsWithBothDates($dateStarts, $dateEnds, $queryParam, $expected) {
        $this->createCategory();

        $this->createEvent(['dateStarts' => $dateStarts[0], 'dateEnds' => $dateEnds[0]]);
        $this->createEvent(['dateStarts' => $dateStarts[1], 'dateEnds' => $dateEnds[1]]);
        $this->createEvent(['dateStarts' => $dateStarts[2], 'dateEnds' => $dateEnds[2]]);

        $events = $this->api()->get(
            "/events",
            [
                "parentRecordType" => \EventModel::PARENT_TYPE_CATEGORY,
                "parentRecordID" => $this->lastInsertedCategoryID,
                "dateStarts" => $queryParam[0],
                "dateEnds" => $queryParam[1],
            ]
        )->getBody();

        $this->assertEquals($expected, count($events));
    }

    /**
     * Provide dates for testGetEventsWithBothDates.
     *
     * @return array Returns a data provider.
     */
    public function provideDatesFilter() {
        $data = [
            [
                ['2020-01-01 05:05:05', '2020-01-01 05:05:05', '2020-02-01 06:06:06'],
                ['2020-05-05 05:05:05', '2020-06-06 06:06:06', '2020-07-07 07:07:07'],
                ['=2020-01-01 05:05:05','=2020-06-06 06:06:06'],
                1
            ],
            [
                ['2020-01-01 05:05:05', '2020-01-01 05:05:05', '2020-02-01 06:06:06'],
                ['2020-05-05 05:05:05', '2020-06-06 06:06:06', '2020-07-07 07:07:07'],
                ['<2020-02-01 06:06:06', '<2020-07-07 07:07:07'],
                2
            ],
            [
                ['2020-01-01 05:05:05', '2020-01-01 05:05:05', '2020-02-01 06:06:06'],
                ['2020-05-05 05:05:05', '2020-06-06 06:06:06', '2020-07-07 07:07:07'],
                ['>2020-01-01 05:05:05','>2020-05-05 05:05:05'],
                1
            ],
            [
                ['2020-01-01 05:05:05', '2020-01-01 05:05:05', '2020-02-01 06:06:06'],
                ['2020-05-05 05:05:05', '2020-06-06 06:06:06', '2020-07-07 07:07:07'],
                ['>=2020-01-01 05:05:05','>=2020-05-05 05:05:05'],
                3
            ],
            [
                ['2020-01-01 05:05:05', '2020-01-01 05:05:05', '2020-02-01 06:06:06'],
                ['2020-05-05 05:05:05', '2020-06-06 06:06:06', '2020-07-07 07:07:07'],
                ['<=2020-01-01 05:05:05', '<=2020-06-06 06:06:06'],
                2
            ],
            [
                ['2020-01-01 05:05:05', '2020-01-01 05:05:05', '2020-02-01 06:06:06'],
                ['2020-05-05 05:05:05', '2020-06-06 06:06:06', '2020-07-07 07:07:07'],
                ['[2020-01-01 05:05:05, 2020-02-01 06:06:06]', '[2020-05-05 05:05:05, 2020-07-07 07:07:07]'],
                3
            ],
        ];
        return $data;
    }
}
