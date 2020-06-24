<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

require_once(__DIR__.'/AbstractGroupsSubResource.php');

/**
 * Test the /api/v2/groups/:groupID/invites sub-resource endpoints.
 */
class GroupsInvitesTest extends AbstractGroupsSubResource {
    /**
     * Test POST :groupID/invites.
     */
    public function testInviteUser() {
        $originalGroup = $this->createGroup(__FUNCTION__, true);

        $result = $this->api()->post(
            $this->createURL($originalGroup['groupID'], 'invites'),
            [
                'userID' => self::$userIDs[0],
            ]
        );

        $this->assertEquals(201, $result->getStatusCode());

        $invite = $result->getBody();

        $this->assertIsArray($invite);
        $this->assertArrayHasKey('userID', $invite);
        $this->assertArrayHasKey('insertUserID', $invite);
        $this->assertEquals(self::$userIDs[0], $invite['userID']);
        $this->assertEquals(self::$siteInfo['adminUserID'], $invite['insertUserID']);
    }

    /**
     * Test GET :groupID/invites.
     *
     * @depends testInviteUser
     */
    public function testListInvites() {
        $originalGroup = $this->createGroup(__FUNCTION__, true);
        $url = $this->createURL($originalGroup['groupID'], 'invites');

        foreach (self::$userIDs as $userID) {
            $this->api()->post(
                $url,
                [
                    'userID' => $userID,
                ]
            );
        }

        $result = $this->api()->get($url);

        $this->assertEquals(200, $result->getStatusCode());

        $invites = $result->getBody();

        $this->assertIsArray($invites);
        $this->assertEquals(count(self::$userIDs), count($invites));

        $this->pagingTest($url);
    }

    /**
     * Test DELETE :groupID/invites/:userID.
     *
     * @depends testListInvites
     */
    public function testDeleteInvitation() {
        $originalGroup = $this->createGroup(__FUNCTION__, true);

        $this->api()->post(
            $this->createURL($originalGroup['groupID'], 'invites'),
            [
                'userID' => self::$userIDs[0],
            ]
        );

        $result = $this->api()->delete(
            $this->createURL($originalGroup['groupID'], 'invites', self::$userIDs[0])
        );
        $this->assertEquals(204, $result->getStatusCode());

        $result = $this->api()->get(
            $this->createURL($originalGroup['groupID'], 'invites')
        );

        $invites = $result->getBody();

        $this->assertIsArray($invites);
        $this->assertEquals(0, count($invites));
    }
}
