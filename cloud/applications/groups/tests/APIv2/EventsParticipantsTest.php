<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

use VanillaTests\Groups\Utils\GroupsAndEventsApiTestTrait;

/**
 * Test the /api/v2/events/:eventID/participants sub-resource endpoints.
 */
class EventsParticipantsTest extends AbstractAPIv2Test {

    use GroupsAndEventsApiTestTrait;

    protected static $group;

   /** @var array $userIDs List of userIDs created to this test suite. */
    protected static $userIDs;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::$userIDs = [];
        self::$addons = ['vanilla', 'groups'];
        parent::setupBeforeClass();
        \PermissionModel::resetAllRoles();

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = static::container()->get('UsersAPIController');

        $classParts = explode('\\', __CLASS__);
        $className = $classParts[count($classParts) - 1];
        for ($i = 1; $i <= 5; $i++) {
            $user = $usersAPIController->post([
                'name' => self::randomUsername(),
                'email' => "{$className}{$i}$i@example.com",
                'password' => "$%#$&ADSFBNYI*&WBV$i",
            ]);
            self::$userIDs[] = $user['userID'];
        }

         /** @var \GroupsApiController $groupsAPIController */
        $groupsAPIController = static::container()->get('GroupsApiController');

        $groupTxt = uniqid(__CLASS__);
        self::$group = $groupsAPIController->post([
            'name' => $groupTxt,
            'description' => $groupTxt,
            'format' => 'Markdown',
            'privacy' => 'public',
        ]);

        // Disable email sending.
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get('Config');
        $config->set('Garden.Email.Disabled', true, true, false);

        $session->end();
    }

    /**
     * Create an endpoint URL.
     * /events/:eventID[/:action][/:userID]
     *
     * @param int $eventID
     * @param string|null $action
     * @param int|null $userID
     * @return string
     */
    protected function createURL($eventID, $action = null, $userID=null) {
         $parts = ["/events/$eventID"];
         if ($action) {
             $parts[] = $action;
         }
         if ($userID) {
             $parts[] = $userID;
         }

         return implode('/', $parts);
    }

    /**
     * Test POST :eventID/events.
     *
     * @dataProvider providerRSVP
     *
     * @param string|null $attending
     */
    public function testRSVP($attending) {
        $this->createGroup();
        $event = $this->createEvent();
        $this->api()->setUserID(self::$userIDs[0]);
        $this->joinGroup();

        $result = $this->api()->post(
            $this->createURL($event['eventID'], 'participants'),
            [
                'attending' => $attending,
            ]
        );

        $this->assertEquals(201, $result->getStatusCode());

        $participant = $result->getBody();

        $this->assertIsArray($participant);
        $this->assertArrayHasKey('userID', $participant);
        $this->assertArrayHasKey('attending', $participant);
        $this->assertEquals(self::$userIDs[0], $participant['userID']);
        $this->assertEquals($attending, $participant['attending']);
    }

    /**
     * Test POST :eventID/events.
     *
     * @dataProvider providerRSVP
     *
     * @param string|null $attending
     */
    public function testRSVPForUser($attending) {
        $this->createGroup();
        $event = $this->createEvent();

        $result = $this->api()->post(
            $this->createURL($event['eventID'], 'participants'),
            [
                'userID' => self::$userIDs[0],
                'attending' => $attending,
            ]
        );

        $this->assertEquals(201, $result->getStatusCode());

        $participant = $result->getBody();

        $this->assertIsArray($participant);
        $this->assertArrayHasKey('userID', $participant);
        $this->assertArrayHasKey('attending', $participant);
        $this->assertEquals(self::$userIDs[0], $participant['userID']);
        $this->assertEquals($attending, $participant['attending']);
    }

    /**
     * Test POST :eventID/events.
     *
     */
    public function testRSVPMultipleTimes() {
        $this->createGroup();
        $event = $this->createEvent();
        $this->api()->setUserID(self::$userIDs[0]);
        $this->joinGroup();

        foreach (['maybe', null, 'yes', 'no'] as $attending) {
            $result = $this->api()->post(
                $this->createURL($event['eventID'], 'participants'),
                [
                    'attending' => $attending,
                ]
            );

            $this->assertEquals(201, $result->getStatusCode());

            $participant = $result->getBody();

            $this->assertIsArray($participant);
            $this->assertArrayHasKey('userID', $participant);
            $this->assertArrayHasKey('attending', $participant);
            $this->assertEquals(self::$userIDs[0], $participant['userID']);
            $this->assertEquals($attending, $participant['attending']);
        }

        $this->api()->setUserID(self::$siteInfo['adminUserID']);
        $result = $this->api()->get(
            $this->createURL($event['eventID'], 'participants')
        );
        $participants = $result->getBody();
        $this->assertEquals(1, count($participants));
    }

    /**
     * Test GET :groupID/participants.
     *
     * @depends testRSVP
     */
    public function testListParticipants() {
        $this->createGroup();
        $event = $this->createEvent();

        foreach (self::$userIDs as $userID) {
            $this->api()->setUserID($userID);
            $this->joinGroup();
            $this->api()->post(
                $this->createURL($event['eventID'], 'participants'),
                [
                    'attending' => 'yes',
                ]
            );
        }

        $this->api()->setUserID(self::$siteInfo['adminUserID']);
        $result = $this->api()->get(
            $this->createURL($event['eventID'], 'participants')
        );

        $this->assertEquals(200, $result->getStatusCode());

        $participants = $result->getBody();

        $this->assertIsArray($participants);
        $this->assertEquals(count(self::$userIDs), count($participants));
    }

    /**
     * Test GET :groupID/participants with '?attending=' filter.
     *
     * @depends testRSVPMultipleTimes
     * @depends testListParticipants
     */
    public function testListParticipantsWithAttendingFilter() {
        $this->createGroup();
        $event = $this->createEvent();

        $attendingAnswers = ['maybe', null, 'yes', 'no'];
        foreach ($attendingAnswers as $index => $attending) {
            $userID = self::$userIDs[$index];
            $this->api()->setUserID($userID);
            $this->joinGroup();
            $result = $this->api()->post(
                $this->createURL($event['eventID'], 'participants'),
                [
                    'attending' => $attending,
                ]
            );

            $this->assertEquals(201, $result->getStatusCode());

            $participant = $result->getBody();

            $this->assertIsArray($participant);
            $this->assertArrayHasKey('userID', $participant);
            $this->assertArrayHasKey('attending', $participant);
            $this->assertEquals($userID, $participant['userID']);
            $this->assertEquals($attending, $participant['attending']);

            $this->api()->setUserID(self::$siteInfo['adminUserID']);
            $result = $this->api()->get(
                $this->createURL($event['eventID'], 'participants').'?attending='.($attending === null ? 'unanswered' : $attending)
            );
            $participants = $result->getBody();
            $this->assertEquals(1, count($participants));
        }

        $result = $this->api()->get(
            $this->createURL($event['eventID'], 'participants').'?attending=all'
        );
        $participants = $result->getBody();
        $this->assertEquals(count($attendingAnswers), count($participants));

        $result = $this->api()->get(
            $this->createURL($event['eventID'], 'participants').'?attending=answered'
        );
        $participants = $result->getBody();
        $this->assertEquals(count($attendingAnswers) - 1, count($participants));

        $result = $this->api()->get(
            $this->createURL($event['eventID'], 'participants').'?attending=unanswered'
        );
        $participants = $result->getBody();
        $this->assertEquals(1, count($participants));
    }

    /**
     * @return array
     */
    public function providerRSVP() {
        return [
            ['yes'],
            ['no'],
            ['maybe'],
            [null],
        ];
    }
}
