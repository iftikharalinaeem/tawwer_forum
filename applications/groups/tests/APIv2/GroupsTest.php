<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/groups endpoints.
 */
class GroupsTest extends AbstractResourceTest {

    /** @var int */
    protected static $recordCounter = 0;

    /** @var bool */
    protected $incrementRecordCounterOnCall = false;

     /** {@inheritdoc} */
    protected $formattedFields = ['body', 'description'];

    /** {@inheritdoc} */
    protected $baseUrl = '/groups';

    /** {@inheritdoc} */
    protected $editFields = ['description', 'name', 'format', 'privacy', 'bannerUrl', 'iconUrl'];

    /** {@inheritdoc} */
    protected $patchFields = ['description', 'name', 'format', 'privacy', 'bannerUrl', 'iconUrl'];

    /** {@inheritdoc} */
    protected $pk = 'groupID';

    /** {@inheritdoc} */
    protected $singular = 'group';


    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        self::$addons = ['vanilla', 'conversations', 'groups'];
        parent::setupBeforeClass();
        \PermissionModel::resetAllRoles();
    }

    /**
     * {@inheritdoc}
     */
    public function setUp() {
        parent::setUp();
        $this->incrementRecordCounterOnCall = false;
        static::$recordCounter++;
    }

    /**
     * {@inheritdoc}
     */
    public function record() {
        if ($this->incrementRecordCounterOnCall) {
            static::$recordCounter++;
        }

        $count = static::$recordCounter;
        $name = "Test Group $count";
        $record = [
            'name' => $name,
            'description' => "$name description",
            'format' => 'Markdown',
            'privacy' => 'public',
            'bannerUrl' => null,
            'iconUrl' => 'https://example.com/image.jpg',
        ];
        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public function testIndex() {
        // We need this since groups cannot have the same name and this test will create 5 of them.
        $this->incrementRecordCounterOnCall = true;
        parent::testIndex();
    }

    /**
     * Test filtering groups by member.
     *
     * @depends testIndex
     */
    public function testIndexMemberFilter() {
        $apiUserID = $this->api()->getUserID();

        // Create a group for the test.
        $group = $this->testPost();
        $groupID = $group['groupID'];

        // Create a user for the test. Configure API requests to temporarily use this user.
        $user = $user = $this->api()->post('users', [
            'name' => 'IndexMemberFilter',
            'email' => 'IndexMemberFilter@example.com',
            'password' => md5(time()),
        ])->getBody();
        $userID = $user['userID'];
        $this->api()->setUserID($userID);

        // Join the test group with our test user.
        $result = $this->api()->post("{$this->baseUrl}/{$groupID}/join");
        $this->assertEquals(201, $result->getStatusCode());

        // Switch the API user back to the original user account.
        $this->api()->setUserID($apiUserID);

        // Ths user created for this test should only be in the group created for this test.
        $groups = $this->api()->get($this->baseUrl, ['memberID' => $userID])->getBody();
        $this->assertCount(1, $groups);
        $memberGroup = reset($groups);
        $this->assertEquals($groupID, $memberGroup['groupID']);
    }

    /**
     * Test /api/v2/group/search endpoint.
     */
    public function testGroupSearch() {
        //8 groups are created.
        $groups = $this->createTestSearchGroups();
        $this->assertEquals(8, count($groups));

        $query = ['query' => 'new'];

        $result = $this->api()->get($this->baseUrl.'/search?query='.$query['query']);
        $this->assertEquals(200, $result->getStatusCode());
        $searchResults = $result->getBody();
        $this->assertEquals(5, count($searchResults));

        foreach ($searchResults as $result) {
            $this->assertContains($query['query'], $result['name'], true);

        }
    }

    /**
     * Create groups for /api/v2/group/search test.
     *
     * @return array $groups test groups for search endpoint.
     */
    private function createTestSearchGroups() {
        $groups = [];
        for ($i = 0; $i <= 4; $i++) {
            $group = $this->api()->post($this->baseUrl, [
                'name' => 'new'.$i,
                'description' => "search group",
                'format' => 'Markdown',
                'privacy' => 'public',
                'bannerUrl' => null,
                'iconUrl' => 'https://example.com/image.jpg',
            ])->getBody();
            $groups[] = $group;
        }
        //create groups that shouldn't match query parameter.
        for ($i = 0; $i <= 2; $i++) {
            $group = $this->api()->post($this->baseUrl, [
                'name' => 'invalid'.$i,
                'description' => "shouldn't show up",
                'format' => 'Markdown',
                'privacy' => 'public',
                'bannerUrl' => null,
                'iconUrl' => 'https://example.com/image.jpg',
            ])->getBody();
            $groups[] = $group;
        }

        return $groups;
    }
}
