<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

/**
 * Class AbstractPollsSubResource
 */
abstract class AbstractPollsSubResource extends AbstractAPIv2Test {

    private static $category;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['vanilla', 'polls', 'advancedsearch'];

        parent::setupBeforeClass();

        /**
         * @var \Gdn_Session $session
         */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \CategoriesApiController $categoryAPIController */
        $categoryAPIController = static::container()->get('CategoriesApiController');

        self::$category = $categoryAPIController->post([
            'name' => 'PollOptionsTest',
            'urlcode' => 'polloptionstest',
        ]);

        $session->end();
    }

    /**
     * Create a poll.
     *
     * @param string $testName Name of the test function from which the poll is created.
     * @param bool $withOptions Whether or not to create options too.
     * @return array The created poll.
     */
    protected function createPoll($testName, $withOptions = false) {
         /** @var \PollsApiController $pollsAPIController */
        $pollsAPIController = static::container()->get('PollsApiController');
        /** @var \DiscussionsApiController $discussionsApiController */
        $discussionsApiController = static::container()->get('DiscussionsApiController');

        $pollTxt = uniqid(__CLASS__." $testName ");

        $discussion = $discussionsApiController->post([
            'name' => $pollTxt,
            'type' => 'Poll',
            'body' => $pollTxt,
            'format' => 'markdown',
            'categoryID' => self::$category['categoryID'],
        ]);

        $poll = $pollsAPIController->post([
            'name' => $pollTxt,
            'discussionID' => $discussion['discussionID'],
        ]);

        $options = [];
        if ($withOptions) {
            $option = $pollsAPIController->post_options($poll['pollID'], [
                'body' => $pollTxt.' Option 1'
            ]);
            $options[$option['pollOptionID']] = $option;

            $option = $pollsAPIController->post_options($poll['pollID'], [
                'body' => $pollTxt.' Option 2'
            ]);
            $options[$option['pollOptionID']] = $option;
        }

        $poll['options'] = $options;

        return (array)$poll;
    }

    /**
     * Create an endpoint URL.
     * /polls/:pollID/:resource[/:resourceID]
     *
     * @param int $pollID
     * @param string $resource
     * @param int|null $resourceID
     * @return string
     */
    protected function createURL($pollID, $resource = null, $resourceID = null) {
         $parts = ["/polls/$pollID"];

        if ($resource) {
             $parts[] = $resource;
         }

         if ($resourceID) {
             $parts[] = $resourceID;
         }

         return implode('/', $parts);
    }
}
