<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

require_once(__DIR__.'/AbstractGroupsSubResource.php');

/**
 * Test the /api/v2/groups/:groupID/applicants sub-resource endpoints.
 */
class GroupsApplicantsTest extends AbstractGroupsSubResource {
    /**
     * Test POST :groupID/applicants.
     */
    public function testApply() {
        $originalGroup = $this->createGroup(__FUNCTION__, false);

        $reason = uniqid('Because ');

        $this->api()->setUserID(self::$userIDs[0]);
        $result = $this->api()->post(
            $this->createURL($originalGroup['groupID'], 'applicants'),
            [
                'userID' => self::$userIDs[0],
                'reason' => $reason,
            ]
        );

        $this->assertEquals(201, $result->getStatusCode());

        $invite = $result->getBody();

        $this->assertInternalType('array', $invite);
        $this->assertArrayHasKey('userID', $invite);
        $this->assertArrayHasKey('reason', $invite);
        $this->assertEquals(self::$userIDs[0], $invite['userID']);
        $this->assertEquals($reason, $invite['reason']);
    }

    /**
     * Test POST :groupID/applicants.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Cannot apply to a group that is not private.
     */
    public function testApplyToPublicGroup() {
        $originalGroup = $this->createGroup(__FUNCTION__, true);

        $reason = uniqid('Because ');

        $this->api()->setUserID(self::$userIDs[0]);
        $this->api()->post(
            $this->createURL($originalGroup['groupID'], 'applicants'),
            [
                'userID' => self::$userIDs[0],
                'reason' => $reason,
            ]
        );
    }

    /**
     * Test GET :groupID/applicants.
     *
     * @depends testApply
     */
    public function testListApplicants() {
        $originalGroup = $this->createGroup(__FUNCTION__, false);

        foreach (self::$userIDs as $userID) {
            $this->api()->setUserID($userID);
            $this->api()->post(
                $this->createURL($originalGroup['groupID'], 'applicants'),
                [
                    'reason' => uniqid('Because '),
                ]
            );
        }

        $this->api()->setUserID(self::$siteInfo['adminUserID']);
        $result = $this->api()->get(
            $this->createURL($originalGroup['groupID'], 'applicants')
        );

        $this->assertEquals(200, $result->getStatusCode());

        $applicants = $result->getBody();

        $this->assertInternalType('array', $applicants);
        $this->assertEquals(count(self::$userIDs), count($applicants));
    }

    /**
     * Test PATCH :groupID/applicants/:userID.
     *
     * @dataProvider provideApproveApplicants
     * @depends testApply
     */
    public function testApproveApplicants($state) {
        $originalGroup = $this->createGroup(__FUNCTION__.'('.$state.')', false);

        $this->api()->setUserID(self::$userIDs[0]);
        $this->api()->post(
            $this->createURL($originalGroup['groupID'], 'applicants'),
            [
                'reason' => uniqid('Because '),
            ]
        );

        $this->api()->setUserID(self::$siteInfo['adminUserID']);

        $result = $this->api()->patch(
            $this->createURL($originalGroup['groupID'], 'applicants', self::$userIDs[0]),
            [
                'state' => $state,
            ]
        );

        $this->assertEquals(200, $result->getStatusCode());

        $applicant = $result->getBody();

        $this->assertInternalType('array', $applicant);
        $this->assertArrayHasKey('userID', $applicant);
        $this->assertArrayHasKey('state', $applicant);
        $this->assertEquals(self::$userIDs[0], $applicant['userID']);
        $this->assertEquals($state, $applicant['state']);

        $result = $this->api()->get($this->createURL($originalGroup['groupID']));
        $updatedGroup = $result->getBody();

        $this->assertEquals($originalGroup['countMembers'] + ($state === 'approved' ? 1 : 0), $updatedGroup['countMembers']);
    }

    /**
     * Provider of testApproveApplicants().
     *
     * @return array
     */
    public function provideApproveApplicants() {
        return [
            ['approved'],
            ['denied'],
        ];
    }
}
