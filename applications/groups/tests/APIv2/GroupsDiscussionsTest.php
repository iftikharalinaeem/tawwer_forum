<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/discussions with groups
 */
class GroupsDiscussionsTest extends DiscussionsTest {

    /** @var array */
    protected static $groups;

    /**
     * GroupsDiscussionsTest constructor.
     *
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);

        $this->patchFields[] = 'groupID';
    }

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        self::$addons = ['vanilla', 'groups'];
        parent::setupBeforeClass();

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \GroupsApiController $groupsAPIController */
        $groupsAPIController = static::container()->get('GroupsApiController');

        foreach ([1, 2] as $i) {
            $groupTxt = uniqid(__CLASS__." $i ");
            self::$groups[] = $groupsAPIController->post([
                'name' => $groupTxt,
                'description' => $groupTxt,
                'format' => 'Markdown',
                'privacy' => 'public',
            ]);
        }

        $session->end();
    }

    /**
     * @inheritdoc
     */
    public function record() {
        $record = parent::record();

        unset($record['categoryID']);
        $record['groupID'] = self::$groups[0]['groupID'];

        return $record;
    }

    /**
     * @inheritdoc
     */
    public function modifyRow(array $row) {
        $row = parent::modifyRow($row);

        // Assign the discussion to the other group.
        if (isset($row['groupID'])) {
            $row['groupID'] = self::$groups[1]['groupID'];
        }
        return $row;
    }

    /**
     * Test /discussions?groupID={ID}
     */
    public function testIndexGroupFilter() {
        $this->testIndex();

        $indexUrl = $this->indexUrl();

        $result = $this->api()->get($indexUrl.'?groupID='.self::$groups[0]['groupID']);
        $this->assertEquals(200, $result->getStatusCode());

        $groupDiscussions = $result->getBody();

        $this->assertTrue(count($groupDiscussions) > 0);
        foreach ($groupDiscussions as $discussion) {
            $this->assertArrayHasKey('groupID', $discussion);
            $this->assertEquals($discussion['groupID'], self::$groups[0]['groupID']);
        }
    }
}
