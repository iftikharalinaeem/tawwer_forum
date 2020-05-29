<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Groups\Models;

use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Groups\Utils\GroupsAndEventsApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the legacy group and event permission functions with .Reason syntax.
 */
class LegacyGroupEventPermissionsTest extends AbstractAPIv2Test {

    use UsersAndRolesApiTestTrait;
    use GroupsAndEventsApiTestTrait;

    public static $addons = ['vanilla', 'conversations', 'groups'];

    /**
     * Test legacy group permission function.
     */
    public function testLegacyGroupPermission() {
        $model = new \GroupModel();
        $user2 = $this->createUser([
            'roleID' => [\RoleModel::MEMBER_ID]
        ]);
        $group = $this->createGroup();

        // Success returns true.
        $this->assertTrue(@$model->checkPermission('Leader', $group['groupID']));

        $this->api()->setUserID($user2['userID']);
        $this->assertFalse(@$model->checkPermission('Leader', $group['groupID']));

        $this->assertEquals('You need the group.Leader permission to do that.', @$model->checkPermission('Leader.Reason', $group['groupID']));
    }

    /**
     * Test legacy event permission function.
     */
    public function testLegacyEventPermission() {
        $model = new \EventModel();
        $user2 = $this->createUser([
            'roleID' => [\RoleModel::MEMBER_ID]
        ]);
        $group = $this->createGroup();
        $event = $this->createEvent();

        // Success returns true.
        $this->assertTrue(@$model->checkPermission('Edit', $group['groupID']));

        $this->api()->setUserID($user2['userID']);
        $this->assertFalse(@$model->checkPermission('Edit', $group['groupID']));

        $this->assertEquals('You aren\'t allowed to edit this event.', @$model->checkPermission('Edit.Reason', $group['groupID']));
    }
}
