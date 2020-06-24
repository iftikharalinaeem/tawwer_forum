<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

require_once(__DIR__.'/AbstractBadgesSubResource.php');

class UserBadgesTest extends AbstractBadgesSubResource {

    /**
     * Test POST :badgeID/users.
     */
    public function testGiveBadge() {
        $badge = $this->createBadge(__FUNCTION__, true);

        $reason = 'Because he\'s hawt';
        $result = $this->api()->post(
            $this->createURL($badge['badgeID'], 'users'),
            [
                'userID' => self::$userIDs[0],
                'reasonBody' => $reason,
            ]
        );

        $this->assertEquals(201, $result->getStatusCode());

        $userBadge = $result->getBody();

        $this->assertIsArray($userBadge);
        $this->assertArrayHasKey('userID', $userBadge);
        $this->assertArrayHasKey('badgeID', $userBadge);
        $this->assertArrayHasKey('reasonBody', $userBadge);
        $this->assertArrayHasKey('insertUser', $userBadge);
        $this->assertArrayHasKey('dateEarned', $userBadge);
        $this->assertEquals(self::$userIDs[0], $userBadge['userID']);
        $this->assertEquals(self::$siteInfo['adminUserID'], $userBadge['insertUserID']);
        $this->assertEquals($badge['badgeID'], $userBadge['badgeID']);
        $this->assertEquals($reason, $userBadge['reasonBody']);
    }

    /**
     * Test GET :badgeID/users.
     *
     * @depends testGiveBadge
     */
    public function testListUserBadge() {
        $badge = $this->createBadge(__FUNCTION__, true);

        foreach (self::$userIDs as $userID) {
            $reason = 'Because he\'s hawt';
            $this->api()->post(
                $this->createURL($badge['badgeID'], 'users'),
                [
                    'userID' => $userID,
                    'reasonBody' => $reason,
                ]
            );
        }

        $result = $this->api()->get(
            $this->createURL('users').'?badgeID='.$badge['badgeID']
        );

        $this->assertEquals(200, $result->getStatusCode());

        $requests = $result->getBody();

        $this->assertIsArray($requests);
        $this->assertEquals(count(self::$userIDs), count($requests));
    }

    /**
     * Test DELETE :badgeID/users.
     *
     * @depends testListUserBadge
     */
    public function testDeleteUserBadge() {
       $badge = $this->createBadge(__FUNCTION__, true);

        foreach (self::$userIDs as $userID) {
            $reason = 'Because he\'s hawt';
            $this->api()->post(
                $this->createURL($badge['badgeID'], 'users'),
                [
                    'userID' => $userID,
                    'reasonBody' => $reason,
                ]
            );
        }

        $result = $this->api()->delete(
            $this->createURL($badge['badgeID'], 'users', self::$userIDs[0])
        );

        $this->assertEquals(204, $result->getStatusCode());

        $result = $this->api()->get(
            $this->createURL('users').'?badgeID='.$badge['badgeID']
        );

        $requests = $result->getBody();

        $this->assertIsArray($requests);
        $this->assertEquals(count(self::$userIDs) - 1, count($requests));
    }

    /**
     * @requires function \BadgesApiController::patch_users
     */
    public function testPatch() {
        $this->fail(__METHOD__.' needs to be implemented');
    }
}
