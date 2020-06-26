<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
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

        $this->assertIsArray($invite);
        $this->assertArrayHasKey('userID', $invite);
        $this->assertArrayHasKey('reason', $invite);
        $this->assertEquals(self::$userIDs[0], $invite['userID']);
        $this->assertEquals($reason, $invite['reason']);
    }

    /**
     * Test POST :groupID/applicants.
     */
    public function testApplyToPublicGroup() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot apply to a group that is not private.');

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
        $url = $this->createURL($originalGroup['groupID'], 'applicants');

        foreach (self::$userIDs as $userID) {
            $this->api()->setUserID($userID);
            $this->api()->post(
                $url,
                [
                    'reason' => uniqid('Because '),
                ]
            );
        }

        $this->api()->setUserID(self::$siteInfo['adminUserID']);
        $result = $this->api()->get($url);

        $this->assertEquals(200, $result->getStatusCode());

        $applicants = $result->getBody();

        $this->assertIsArray($applicants);
        $this->assertEquals(count(self::$userIDs), count($applicants));

        $this->pagingTest($url);
    }

    /**
     * Test PATCH :groupID/applicants/:userID.
     *
     * @dataProvider provideApproveApplicants
     * @depends testListApplicants
     */
    public function testApproveApplicants($status) {
        $originalGroup = $this->createGroup(__FUNCTION__.'('.$status.')', false);

        // Get current applicants count.
        $applicantsResult = $this->api()->get($this->createURL($originalGroup['groupID'], 'applicants'));
        $applicantsCountsBefore = count($applicantsResult->getBody());

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
                'status' => $status,
            ]
        );

        $this->assertEquals(200, $result->getStatusCode());

        $applicant = $result->getBody();

        $this->assertIsArray($applicant);
        $this->assertArrayHasKey('userID', $applicant);
        $this->assertArrayHasKey('status', $applicant);
        $this->assertEquals(self::$userIDs[0], $applicant['userID']);
        $this->assertEquals($status, $applicant['status']);

        $result = $this->api()->get($this->createURL($originalGroup['groupID']));
        $updatedGroup = $result->getBody();

        $this->assertEquals($originalGroup['countMembers'] + ($status === 'approved' ? 1 : 0), $updatedGroup['countMembers']);

        // Let's make sure that the applicants count is the same as before.
        $applicantsResult = $this->api()->get($this->createURL($originalGroup['groupID'], 'applicants'));
        $applicantsCountsAfter = count($applicantsResult->getBody());

        $this->assertEquals($applicantsCountsBefore, $applicantsCountsAfter);
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
