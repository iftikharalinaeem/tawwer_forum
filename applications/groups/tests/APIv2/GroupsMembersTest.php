<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ForbiddenException;

require_once(__DIR__.'/AbstractGroupsSubResource.php');

/**
 * Test the /api/v2/groups members sub-resource endpoints.
 */
class GroupsMembersTest extends AbstractGroupsSubResource {
    /**
     * Test POST :groupID/join where the group is public.
     */
    public function testJoinPublic() {
        $originalGroup = $this->createGroup(__FUNCTION__, true);

        // Join as one or our test user.
        $this->api()->setUserID(self::$userIDs[0]);

        $result = $this->api()->post(
            $this->createURL($originalGroup['groupID'], 'join')
        );

        $member = $result->getBody();

        $this->assertIsArray($member);
        $this->assertArrayHasKey('userID', $member);
        $this->assertArrayHasKey('role', $member);
        $this->assertEquals(self::$userIDs[0], $member['userID']);
        $this->assertEquals('member', $member['role']);

        $this->assertEquals(201, $result->getStatusCode());

        $result = $this->api()->get($this->createURL($originalGroup['groupID']));
        $updatedGroup = $result->getBody();

        $this->assertEquals($originalGroup['countMembers'] + 1, $updatedGroup['countMembers']);
    }

    /**
     * Test POST :groupID/join where the group is private.
     */
    public function testJoinPrivate() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('A group must be public or you have to be invited to join it.');

        $originalGroup = $this->createGroup(__FUNCTION__, false);

        // Join as one or our test user.
        $this->api()->setUserID(self::$userIDs[0]);

        $this->api()->post(
            $this->createURL($originalGroup['groupID'], 'join')
        );
    }

    /**
     * Test POST :groupID/join where the group is private.
     */
    public function testJoinPrivateWithInvites() {
        $originalGroup = $this->createGroup(__FUNCTION__, false);

        // Invite the user to the private group. This is tested properly elsewhere.
        $result = $this->api()->post(
            $this->createURL($originalGroup['groupID'], 'invites'),
            [
                'userID' => self::$userIDs[0],
            ]
        );
        $this->assertEquals(201, $result->getStatusCode());

        // Join as our test user.
        $this->api()->setUserID(self::$userIDs[0]);
        $result = $this->api()->post(
            $this->createURL($originalGroup['groupID'], 'join')
        );

        $this->assertEquals(201, $result->getStatusCode());

        $member = $result->getBody();

        $this->assertIsArray($member);
        $this->assertArrayHasKey('userID', $member);
        $this->assertArrayHasKey('role', $member);
        $this->assertEquals(self::$userIDs[0], $member['userID']);
        $this->assertEquals('member', $member['role']);

        $result = $this->api()->get($this->createURL($originalGroup['groupID']));
        $updatedGroup = $result->getBody();

        $this->assertEquals($originalGroup['countMembers'] + 1, $updatedGroup['countMembers']);
    }

    /**
     * Test POST :groupID/members
     */
    public function testAddNewMemberToGroup() {
        $originalGroup = $this->createGroup(__FUNCTION__, true);;

        $result = $this->api()->post(
            $this->createURL($originalGroup['groupID'], 'members'),
            ['userID' => self::$userIDs[0]]
        );

        $this->assertEquals(201, $result->getStatusCode());

        $member = $result->getBody();

        $this->assertIsArray($member);
        $this->assertArrayHasKey('role', $member);
        $this->assertEquals('member', $member['role']);

        $result = $this->api()->get($this->createURL($originalGroup['groupID']));
        $updatedGroup = $result->getBody();

        $this->assertEquals($originalGroup['countMembers'] + 1, $updatedGroup['countMembers']);
    }

    /**
     * Test POST :groupID/members
     */
    public function testAddNewLeaderToGroup() {
        $originalGroup = $this->createGroup(__FUNCTION__, true);;

        $result = $this->api()->post(
            $this->createURL($originalGroup['groupID'], 'members'),
            [
                'userID' => self::$userIDs[0],
                'role' => 'leader',
            ]
        );

        $this->assertEquals(201, $result->getStatusCode());

        $member = $result->getBody();

        $this->assertIsArray($member);
        $this->assertArrayHasKey('role', $member);
        $this->assertEquals('leader', $member['role']);

        $result = $this->api()->get($this->createURL($originalGroup['groupID']));
        $updatedGroup = $result->getBody();

        $this->assertEquals($originalGroup['countMembers'] + 1, $updatedGroup['countMembers']);
    }

    /**
     * Test POST :groupID/leave as the owner of the group.
     */
    public function testOwnerLeaveGroup() {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('You can\'t leave the group you started.');

        $originalGroup = $this->createGroup(__FUNCTION__, true);

        $this->api()->post(
            $this->createURL($originalGroup['groupID'], 'leave')
        );
    }

    /**
     * Test POST :groupID/leave as a member of the group.
     *
     * @depends testJoinPublic
     */
    public function testMemberLeaveGroup() {
        $originalGroup = $this->createGroup(__FUNCTION__, true);

        // Join as one of our test user.
        $this->api()->setUserID(self::$userIDs[0]);
        $this->api()->post($this->createURL($originalGroup['groupID'], 'join'));
        $this->clearGroupMemoryCache();

        // Leave
        $result = $this->api()->post(
            $this->createURL($originalGroup['groupID'], 'leave')
        );
        $this->assertEquals(201, $result->getStatusCode());

        $result = $this->api()->get($this->createURL($originalGroup['groupID']));
        $updatedGroup = $result->getBody();

        $this->assertEquals($originalGroup['countMembers'], $updatedGroup['countMembers']);
    }

    /**
     * Test DELETE :groupID/members/:userID
     *
     * @depends testJoinPublic
     */
    public function testRemoveMemberFromGroup() {
        $originalGroup = $this->createGroup(__FUNCTION__, true);

         // Join as one or our test user.
        $this->api()->setUserID(self::$userIDs[0]);
        $result = $this->api()->post($this->createURL($originalGroup['groupID'], 'join'));
        $this->clearGroupMemoryCache();

        // Let's continue to do requests as the group creator.
        $this->api()->setUserID(self::$siteInfo['adminUserID']);
        $result = $this->api()->delete(
            $this->createURL($originalGroup['groupID'], 'members', self::$userIDs[0])
        );

        $this->assertEquals(204, $result->getStatusCode());

        $result = $this->api()->get($this->createURL($originalGroup['groupID']));
        $updatedGroup = $result->getBody();

        $this->assertEquals($originalGroup['countMembers'], $updatedGroup['countMembers']);
    }

    /**
     * Test PATCH :groupID/members/:userID
     *
     * @depends testJoinPublic
     */
    public function testUpdateMemberRole() {
        $originalGroup = $this->createGroup(__FUNCTION__, true);

         // Join as one or our test user.
        $this->api()->setUserID(self::$userIDs[0]);
        $result = $this->api()->post($this->createURL($originalGroup['groupID'], 'join'));
        $originalMember = $result->getBody();

        // Let's continue to do requests as the group creator.
        $this->api()->setUserID(self::$siteInfo['adminUserID']);
        $result = $this->api()->patch(
            $this->createURL($originalGroup['groupID'], 'members', self::$userIDs[0]),
            [
                'role' => 'leader'
            ]
        );

        $this->assertEquals(200, $result->getStatusCode());

        $updatedMember = $result->getBody();
        $this->assertEquals($originalMember['userID'], $updatedMember['userID']);
        $this->assertEquals('leader', $updatedMember['role']);
    }

    /**
     * Test GET :groupID/members
     *
     * @depends testAddNewMemberToGroup
     */
    public function testListGroupMember() {
        $originalGroup = $this->createGroup(__FUNCTION__, true);

        foreach (self::$userIDs as $userID) {
            $this->api()->post(
                $this->createURL($originalGroup['groupID'], 'members'),
                [
                    'userID' => $userID,
                    'role' => 'member',
                ]
            );
        }

        $result = $this->api()->get($this->createURL($originalGroup['groupID'], 'members'));
        $this->assertEquals(200, $result->getStatusCode());

        $groupMembers = $result->getBody();

        $this->assertIsArray($groupMembers);
        $this->assertEquals(1 + count(self::$userIDs), count($groupMembers));
    }
}
