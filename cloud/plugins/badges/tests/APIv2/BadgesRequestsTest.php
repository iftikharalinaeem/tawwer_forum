<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

require_once(__DIR__.'/AbstractBadgesSubResource.php');

class BadgesRequestsTest extends AbstractBadgesSubResource {

    // Let's future someone fix this if the default member role ID changes. :D
    const MEMBER_ROLE_ID = 8;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClassAPIHook() {
        parent::setupBeforeClassAPIHook();

        /** @var \RolesApiController $rolesAPIController */
        $rolesAPIController = static::container()->get('RolesApiController');

        $roleData = [
            'name' => 'Badge Requester',
            'description' => 'Allow a user to request a badge.',
            'type' => 'member',
            'deletable' => true,
            'canSession' => true,
            'personalInfo' => false,
            'permissions' => [[
                'type' => 'global',
                'permissions' => [
                    'badges.request' => true
                ]
            ]],
        ];
        $role = $rolesAPIController->post($roleData);

        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = static::container()->get('UsersApiController');

        // Add the users to the newly created role!
        foreach (self::$userIDs as $userID) {
            $usersAPIController->patch($userID, [
                'roleID' => [self::MEMBER_ROLE_ID, $role['roleID']]
            ]);
        }
    }


    /**
     * Test POST :badgeID/requests.
     */
    public function testRequestBadge() {
        $badge = $this->createBadge(__FUNCTION__, true);

        $this->api()->setUserID(self::$userIDs[0]);
        $reason = 'Because I\'m hawt';
        $result = $this->api()->post(
            $this->createURL($badge['badgeID'], 'requests'),
            [
                'reasonBody' => $reason,
            ]
        );

        $this->assertEquals(201, $result->getStatusCode());

        $badgeRequest = $result->getBody();

        $this->assertIsArray($badgeRequest);
        $this->assertArrayHasKey('userID', $badgeRequest);
        $this->assertArrayHasKey('badgeID', $badgeRequest);
        $this->assertArrayHasKey('reasonBody', $badgeRequest);
        $this->assertArrayHasKey('insertUser', $badgeRequest);
        $this->assertEquals(self::$userIDs[0], $badgeRequest['userID']);
        $this->assertEquals(self::$userIDs[0], $badgeRequest['insertUserID']);
        $this->assertEquals($badge['badgeID'], $badgeRequest['badgeID']);
        $this->assertEquals($reason, $badgeRequest['reasonBody']);
    }

    /**
     * Test GET :badgeID/requests.
     *
     * @depends testRequestBadge
     */
    public function testListRequests() {
        $badge = $this->createBadge(__FUNCTION__, true);

        foreach (self::$userIDs as $userID) {
            $this->api()->setUserID($userID);
            $reason = 'Because I\'m hawt. From UserID['.$userID.']';
            $this->api()->post(
                $this->createURL($badge['badgeID'], 'requests'),
                [
                    'reasonBody' => $reason,
                ]
            );
        }

        $this->api()->setUserID(self::$siteInfo['adminUserID']);
        $result = $this->api()->get(
            $this->createURL('requests').'?badgeID='.$badge['badgeID']
        );

        $this->assertEquals(200, $result->getStatusCode());

        $requests = $result->getBody();

        $this->assertIsArray($requests);
        $this->assertEquals(count(self::$userIDs), count($requests));
    }

    /**
     * Test DELETE :badgeID/requests.
     *
     * @depends testRequestBadge
     */
    public function testDeleteRequest() {
        $badge = $this->createBadge(__FUNCTION__, true);

        foreach (self::$userIDs as $userID) {
            $this->api()->setUserID($userID);
            $reason = 'Because I\'m hawt. From UserID['.$userID.']';
            $this->api()->post(
                $this->createURL($badge['badgeID'], 'requests'),
                [
                    'reasonBody' => $reason,
                ]
            );
        }

        $result = $this->api()->delete(
            $this->createURL($badge['badgeID'], 'requests')
        );

        $this->assertEquals(204, $result->getStatusCode());

        $this->api()->setUserID(self::$siteInfo['adminUserID']);
        $result = $this->api()->get(
            $this->createURL('requests').'?badgeID='.$badge['badgeID']
        );

        $requests = $result->getBody();

        $this->assertIsArray($requests);
        $this->assertEquals(count(self::$userIDs) - 1, count($requests));
    }

    /**
     * @requires function \BadgesAPIController::patch_requests
     */
    public function testPatch() {
        $this->fail(__METHOD__.' needs to be implemented');
    }
}
