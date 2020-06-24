<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/polls endpoints.
 */
class PollsTest extends AbstractResourceTest {

    private static $category;

    private static $discussion;

    private static $recordCounter = 0;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['vanilla', 'polls'];

        parent::setupBeforeClass();

        /**
         * @var \Gdn_Session $session
         */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \CategoriesApiController $categoryAPIController */
        $categoryAPIController = static::container()->get('CategoriesApiController');

        self::$category = $categoryAPIController->post([
            'name' => 'PollsTest',
            'urlcode' => 'pollstest',
        ]);

        $session->end();
    }

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/polls';

        parent::__construct($name, $data, $dataName);

        $this->editFields = ['name', 'discussionID'];
        $this->patchFields = ['name', 'discussionID'];
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
        parent::setUp();

        self::$recordCounter++;

        /** @var \DiscussionsApiController $discussionsApiController */
        $discussionsApiController = static::container()->get('DiscussionsApiController');

        self::$discussion = $discussionsApiController->post([
            'name' => 'PollsTest #'.(self::$recordCounter),
            'type' => 'Poll',
            'body' => 'PollsTest',
            'format' => 'markdown',
            'categoryID' => self::$category['categoryID'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function record() {
        return [
            'name' => 'Is this a poll? #'.(self::$recordCounter),
            'discussionID' => self::$discussion['discussionID'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function modifyRow(array $row) {
        $row = parent::modifyRow($row);

        if (isset($row['discussionID'])) {
            /** @var \DiscussionsApiController $discussionsApiController */
            $discussionsApiController = static::container()->get('DiscussionsApiController');

            $createDiscussion = false;
            try {
                $discussionsApiController->get($row['discussionID'], []);
            } catch(\Exception $e) {
                $createDiscussion = true;
            }

            if ($createDiscussion) {
                $discussionsApiController->post([
                    'name' => 'PollsTest #'.(self::$recordCounter).' - Patch',
                    'type' => 'Poll',
                    'body' => 'PollsTest',
                    'format' => 'markdown',
                    'categoryID' => self::$category['categoryID'],
                ]);
            }
        }

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function generateIndexRows() {
        $rows = [];

        for ($i = 0; $i < self::INDEX_ROWS; $i++) {
            /** @var \DiscussionsApiController $discussionsApiController */
            $discussionsApiController = static::container()->get('DiscussionsApiController');

            self::$discussion = $discussionsApiController->post([
                'name' => 'PollsTest Index #'.$i,
                'type' => 'Poll',
                'body' => 'PollsTest',
                'format' => 'markdown',
                'categoryID' => self::$category['categoryID'],
            ]);

            $rows[] = $this->testPost();
        }

        return $rows;
    }
}
