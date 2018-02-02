<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2;

require_once(__DIR__.'/AbstractPollsSubResource.php');

/**
 * Test the /api/v2/polls/:id/votes endpoints.
 */
class PollVotesTest extends AbstractPollsSubResource {

    /**
     * Test the creation of a poll vote.
     *
     * @return array
     */
    public function testCreatePollVote($caller = null) {
        $poll = $this->createPoll($caller ?? __FUNCTION__, true);

        $optionID = array_keys($poll['options'])[0];

        $result = $this->api()->post(
            $this->createURL($poll['pollID'], 'votes'),
            [
                'pollOptionID' => $optionID,
            ]
        );

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();
        $this->assertTrue(is_int($body['userID']));
        $this->assertNotEmpty($body['pollOptionID']);
        $this->assertEquals($optionID, $body['pollOptionID']);

        $updatedPollResult = $this->api()->get($this->createURL($poll['pollID']));
        $updatedPoll = $updatedPollResult->getBody();

        $this->assertEquals(1, $updatedPoll['countVotes']);

        $optionsResult = $this->api()->get($this->createURL($poll['pollID'], 'options').'?pollOptions='.$optionID);
        $updatedOption = $optionsResult->getBody()[0];

        $this->assertEquals(1, $updatedOption['countVotes']);

        return $poll;
    }

    /**
     * Test getting poll's vote.
     *
     * @depends testCreatePollVote
     */
    public function testGetPollVotes() {
        $poll = $this->testCreatePollVote(__FUNCTION__);

        $votesResult = $this->api()->get($this->createURL($poll['pollID'], 'votes'));
        $votes = $votesResult->getBody();

        $this->assertEquals(1, count($votes));
    }

    /**
     * Test the deletion of a vote.
     *
     * @depends testGetPollVotes
     */
    public function testDeletePollOption() {
        $poll = $this->testCreatePollVote(__FUNCTION__);

        $this->api()->delete($this->createURL($poll['pollID'], 'votes'));

        $votesResult = $this->api()->get($this->createURL($poll['pollID'], 'votes'));
        $votes = $votesResult->getBody();

        $this->assertEmpty($votes);

        $updatedPoll = $this->api()->get($this->createURL($poll['pollID']));
        $this->assertEquals(0, $updatedPoll['countVotes']);

        $optionID = array_keys($poll['options'])[0];
        $options = $this->api()->get($this->createURL($poll['pollID'], 'options').'?pollOptions='.$optionID);
        $updatedOption = $options[0];

        $this->assertEquals(0, $updatedOption['countVotes']);
    }
}
