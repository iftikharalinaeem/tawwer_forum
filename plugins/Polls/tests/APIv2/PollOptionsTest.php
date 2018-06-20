<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2;

require_once(__DIR__.'/AbstractPollsSubResource.php');

/**
 * Test the /api/v2/polls/:id/options endpoints.
 */
class PollOptionsTest extends AbstractPollsSubResource {

    /**
     * Test the creation of a poll option.
     */
    public function testCreatePollOption() {
        $poll = $this->createPoll(__FUNCTION__);

        $record = [
            'body' => 'Is this an option?',
        ];

        $result = $this->api()->post(
            $this->createURL($poll['pollID'], 'options'),
            $record
        );

        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();
        $this->assertTrue(is_int($body['pollOptionID']));
        $this->assertTrue($body['pollOptionID'] > 0);

        $this->assertRowsEqual($record, $body);
    }

    /**
     * Test the listing of a poll's options.
     */
    public function testListPollOptions() {
        $poll = $this->createPoll(__FUNCTION__);

        for ($i = 1; $i <= 5; $i++) {
            $this->api()->post(
                $this->createURL($poll['pollID'], 'options'),
                [
                    'body' => 'Is this an option? #'.$i,
                ]
            );
        }

        $result = $this->api()->get($this->createURL($poll['pollID'], 'options'));

        $this->assertEquals(200, $result->getStatusCode());

        $body = $result->getBody();
        $this->assertEquals(5, count($body));
    }

    /**
     * The the update of a poll option.
     */
    public function testUpdatePollOption() {
        $poll = $this->createPoll(__FUNCTION__);

        $record = [
            'body' => 'Is this an option?',
        ];
        $option = $this->api()->post(
            $this->createURL($poll['pollID'], 'options'),
            $record
        );

        $record['body'] .= uniqid();

        $result = $this->api()->patch(
            $this->createURL($poll['pollID'], 'options', $option['pollOptionID']),
            $record
        );

        $this->assertEquals(200, $result->getStatusCode());

        $updatedOption = $result->getBody();
        $this->assertRowsEqual($record, $updatedOption);
        $this->assertNotEmpty($updatedOption['dateUpdated']);
    }

    /**
     * Test the deletion of a poll option.
     */
    public function testDeletePollOption() {
        $poll = $this->createPoll(__FUNCTION__);

        $option = $this->api()->post(
            $this->createURL($poll['pollID'], 'options'),
            [
            'body' => 'Is this an option?',
            ]
        );

        $result = $this->api()->delete(
            $this->createURL($poll['pollID'], 'options', $option['pollOptionID'])
        );

        $this->assertEquals(204, $result->getStatusCode());

        try {
            $this->api()->get($this->createURL($poll['pollID'], 'options').'?pollOptionID='.$option['pollOptionID']);
            $this->fail('The pollOption did not get deleted.');
        } catch (\Exception $ex) {
            $this->assertEquals(404, $ex->getCode());
            return;
        }
        $this->fail('Something odd happened while deleting a pollOption.');
    }
}
