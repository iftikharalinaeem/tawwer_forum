<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
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
    public static function setupBeforeClass(): void {
        self::$addons = ['vanilla', 'conversations', 'groups'];
        parent::setupBeforeClass();
        \PermissionModel::resetAllRoles();
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
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

        $query = ['query' => 'New'];

        $result = $this->api()->get($this->baseUrl.'/search?query='.$query['query']);
        $this->assertEquals(200, $result->getStatusCode());
        $searchResults = $result->getBody();
        $this->assertEquals(5, count($searchResults));

        foreach ($searchResults as $result) {
            $this->assertStringContainsStringIgnoringCase($query['query'], $result['name']);
        }
    }

    /**
     * Test /api/v2/group/search endpoint as non member.
     */
    public function testGroupSearchwithSecretGroupNonMember() {
        //3 secret groups are created with name of new%
        $this->createSecretGroupsForTesting();

        //Non moderator is created
        $user = $this->createUsersForTesting('member', 'member@test.com', 8);
        $this->api()->setUserID($user['userID']);

        $query = ['query' => 'new'];

        $result = $this->api()->get($this->baseUrl.'/search?query='.$query['query']);
        $this->assertEquals(200, $result->getStatusCode());
        $searchResults = $result->getBody();

        $this->assertEquals(5, count($searchResults));

        foreach ($searchResults as $result) {
            $this->assertStringContainsStringIgnoringCase($query['query'], $result['name']);
        }
    }

    /**
     * Test /api/v2/group/search endpoint with secret group as moderator
     */
    public function testGroupSearchwithSecretGroupModerator() {
        //moderator is created
        $user = $this->createUsersForTesting('moderator', 'moderator@test.com', 16);
        $this->api()->setUserID($user['userID']);

        //all groups have been created already (5 non secret, 3 non secret)
        $query = ['query' => 'new'];

        $result = $this->api()->get($this->baseUrl.'/search?query='.$query['query']);
        $this->assertEquals(200, $result->getStatusCode());

        $searchResults = $result->getBody();

        $this->assertEquals(8, count($searchResults));

        foreach ($searchResults as $result) {
            $this->assertStringContainsStringIgnoringCase($query['query'], $result['name']);
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

    /**
     * Create secrets groups for /api/v2/group/search test.
     *
     * @return array $secretGroups test groups for search endpoint.
     */
    private function createSecretGroupsForTesting() {
        $secretGroups =[];
        for ($i = 0; $i <= 2; $i++) {
            $group = $this->api()->post($this->baseUrl, [
                'name' => 'newsecret'.$i,
                'description' => "secret group",
                'format' => 'Markdown',
                'privacy' => 'secret',
                'bannerUrl' => null,
                'iconUrl' => 'https://example.com/image.jpg',
            ])->getBody();
            $secretGroups[] = $group;
        }
        return $secretGroups;
    }

    /**
     * Create User for testing.
     *
     * @param string $name User name.
     * @param string $email User email.
     * @param int $roleID The role to be associated to user.
     * @return array $user Created user.
     */
    private function createUsersForTesting(string $name, string $email, int $roleID): array {
            $user = $this->api()->post('users', [
                'name' => $name,
                'email' =>$email,
                'password' => "$%#$&ADSFBNYI*&WBV",
                'roleID' => [$roleID],
            ])->getBody();

        return $user;
    }
}
