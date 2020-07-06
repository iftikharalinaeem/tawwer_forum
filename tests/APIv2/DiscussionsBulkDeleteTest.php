<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\Web\Middleware\LogTransactionMiddleware;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Test that deleting a discussion delets it's comments.
 */
class DiscussionsBulkDeleteTest extends AbstractAPIv2Test {

    use CommunityApiTestTrait;
    use CommunityApiTestTrait;

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
    public function testDeleteDiscussion() {
        $this->api()->setDefaultHeader(LogTransactionMiddleware::HEADER_NAME, 1000);
        $category = $this->createCategory();
        $discussion = $this->createDiscussion();
        $this->createComment();
        $this->createComment();
        $this->createComment();

        $this->api()->delete("/discussions/{$discussion['discussionID']}");

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
