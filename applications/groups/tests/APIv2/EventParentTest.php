<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use DateTime;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use VanillaTests\Groups\Utils\GroupsAndEventsApiTestTrait;
use VanillaTests\InternalClient;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for parent resources of events.
 */
class EventParentTest extends AbstractAPIv2Test {

    protected static $addons = ['vanilla', 'groups', 'dashboard'];
    use GroupsAndEventsApiTestTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        // Needed to finish permission initialization.
        \PermissionModel::resetAllRoles();
    }

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
     * Test that permissions on the parent group surrounding event creation work properly.
     */
    public function testGroupMembersEventCreate() {
        $group = $this->createGroup();
        $newUser = $this->createUser();
        $this->api()->setUserID($this->lastUserID);
        $this->joinGroup();
        $this->clearGroupMemoryCache();

        // By default members can add events.
        $event = $this->createEvent();
        $this->assertEquals($group['groupID'], $event['parentRecordID']);

        \Gdn::config()->saveToConfig('Groups.Members.CanAddEvents', false);

        $hasException = false;
        try {
            $this->createEvent();
        } catch (ForbiddenException $e) {
            $hasException = true;
        }
        $this->assertEquals(true, $hasException, 'A forbidden exception was received');
        \Gdn::config()->removeFromConfig('Groups.Members.CanAddEvents');
    }

    /**
     * Test permissions on creating an event in a category.
     */
    public function testCategoryPermissionEventCreate() {
        $category = $this->createCategory();
        $role = $this->createRole([
            'permissions' => [[
                "id" => $this->lastInsertedCategoryID,
                "permissions" => [
                    'events.manage' => true,
                    'events.view' => true,
                ],
                "type" => "category"
            ]]
        ]);
        $this->createUser([
            'roleID' => [\RoleModel::MEMBER_ID, $this->lastRoleID]
        ]);
        $this->api()->setUserID($this->lastUserID);

        $event = $this->createEvent();
        $this->assertEquals($category['categoryID'], $event['parentRecordID']);
    }

    /**
     * Test permissions on creating an event in a category.
     *
     * @param string $privacy
     *
     * @dataProvider provideRestrictedGroupPrivacies
     */
    public function testRestrictedGroupEventView(string $privacy) {
        $this->createGroup(
            ['privacy' => $privacy]
        );
        $this->createEvent();
        $this->createUser();
        $this->api()->setUserID($this->lastUserID);

        $exception = null;
        try {
            $this->api()->get("/events/".$this->lastInsertedEventID);
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(ForbiddenException::class, $exception);

        // Have them join the group
        $this->api()->setUserID(InternalClient::DEFAULT_USER_ID);
        $this->api()->post('/groups/'.$this->lastInsertedGroupID.'/members', [
            'role' => 'member',
            'userID' => $this->lastUserID,
        ]);
        $this->clearGroupMemoryCache();

        // Switch back and check again.
        $this->api()->setUserID($this->lastUserID);
        $response = $this->api()->get("/events/".$this->lastInsertedEventID);
        $this->assertEquals($this->lastInsertedGroupID, $response['parentRecordID']);
    }

    /**
     * @return array
     */
    public function provideRestrictedGroupPrivacies(): array {
        return [
            ['private', ForbiddenException::class],
            ['secret', NotFoundException::class],
        ];
    }

    /**
     * Test permissions on creating an event in a category.
     */
    public function testCategoryPermissionEventView() {
        $this->createCategory();
        $this->createEvent();

        // Create a user that shouldn't be able to access it.
        $this->createRole([
            'permissions' => [[
                "id" => $this->lastInsertedCategoryID,
                "permissions" => [
                    'events.view' => false
                ],
                "type" => "category"
            ]]
        ]);
        $this->createUser([
            'roleID' => [\RoleModel::MEMBER_ID, $this->lastRoleID]
        ]);
        $this->api()->setUserID($this->lastUserID);

        $this->expectException(ForbiddenException::class);
        $this->api()->get("/events/".$this->lastInsertedEventID);
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
     * Make sure we get proper permission errors when we can't access a category.
     */
    public function testGetEventsCategoryPermissionError() {
        $this->createCategory();
        $this->createEvent();
        $this->createEvent();

        // Enable custom permissions for the category.
        $this->createRole([
            'permissions' => [[
                "id" => $this->lastInsertedCategoryID,
                "permissions" => [],
                "type" => "category"
            ]]
        ]);

        $this->createUser();
        $this->api()->setUserID($this->lastUserID);

        $this->expectException(ForbiddenException::class);
        $this->api()->get(
            "/events",
            [
                "parentRecordType" => \EventModel::PARENT_TYPE_CATEGORY,
                "parentRecordID" => $this->lastInsertedCategoryID,
            ]
        )->getBody();
    }

    /**
     * Test that we get proper permission errors when fetching a group we can't access.
     *
     * @param string $groupPrivacy
     * @param string $exceptionClass
     *
     * @dataProvider provideRestrictedGroupPrivacies
     */
    public function testGetEventsGroupPermissionError(string $groupPrivacy, string $exceptionClass) {
        $this->createGroup([
            'privacy' => $groupPrivacy,
        ]);
        $this->createEvent();

        $this->createUser();
        $this->api()->setUserID($this->lastUserID);

        $this->expectException($exceptionClass);
        $this->api()->get(
            "/events",
            [
                "parentRecordType" => \EventModel::PARENT_TYPE_GROUP,
                "parentRecordID" => $this->lastInsertedGroupID
            ]
        )->getBody();
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
     * @param array $dates
     * @param string $queryParam
     * @param int $expected
     * @dataProvider provideDateStartsFilter
     */
    public function testGetEventsDateStartsFilter(array $dates, string $queryParam, int $expected) {
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
     * @param array $dateStarts
     * @param array $dateEnds
     * @param string $queryParam
     * @param int $expected
     * @dataProvider provideDateEndsFilter
     */
    public function testGetEventsDateEndsFilter(array $dateStarts, array $dateEnds, string $queryParam, int $expected) {
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
                ['2020-05-05 05:05:05', '2020-06-06 06:06:06', '2020-07-07 0 7:07:07'],
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
     * @param array $dateStarts
     * @param array $dateEnds
     * @param array $queryParam
     * @param int $expected
     * @dataProvider provideDatesFilter
     */
    public function testGetEventsWithBothDates(array $dateStarts, array $dateEnds, array $queryParam, int $expected) {
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
