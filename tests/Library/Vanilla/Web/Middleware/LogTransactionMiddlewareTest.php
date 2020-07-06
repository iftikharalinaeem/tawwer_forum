<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Middleware;

use Vanilla\Formatting\Formats\MarkdownFormat;
use Vanilla\Web\Middleware\LogTransactionMiddleware;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Tests for logging using the transaction middleware.
 */
class LogTransactionMiddlewareTest extends AbstractAPIv2Test {

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        parent::setUp();
        \Gdn::sql()->truncate('Log');
    }

    /**
     * Test that deleting a discussion deletes all comments and logs them with the correct transactionID.
     */
    public function testLogDeletedDiscussionCommentsTransaction() {
        $this->api()->setDefaultHeader(LogTransactionMiddleware::HEADER_NAME, 1000);
        $commonParams = [
            'body' => 'hello',
            'format' => MarkdownFormat::FORMAT_KEY,
        ];
        $discussion = $this->api()->post('/discussions', $commonParams + [
            'name' => 'hello',
            'categoryID' => -1,
        ]);
        $discussionID = $discussion->getBody()['discussionID'];

        $this->api()->post('/comments', $commonParams + [
            'discussionID' => $discussionID,
        ]);
        $this->api()->post('/comments', $commonParams + [
            'discussionID' => $discussionID,
        ]);
        $this->api()->post('/comments', $commonParams + [
            'discussionID' => $discussionID,
        ]);

        $this->api()->delete("/discussions/$discussionID");

        /** @var \LogModel $logModel */
        $logModel = $this->container()->get(\LogModel::class);

        $logs = $logModel->getWhere([
            'TransactionLogID' => 1000,
        ]);
        $this->assertCount(4, $logs);

        // Comment count
        $this->assertCount(0, $this->api()->get('/comments', ['insertUserID' => $this->api()->getUserID()])->getBody());

        // Restoration off of our transactionID.
        $logModel->restore($logs[0]);
        $this->assertCount(0, $logModel->getWhere([ 'TransactionLogID' => 1000 ]));
        $this->assertCount(3, $this->api()->get('/comments', ['insertUserID' => $this->api()->getUserID()])->getBody());
    }
}
