<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ForbiddenException;

/**
 * Test the /api/v2/discussions with groups
 */
class GroupsDiscussionsTest extends DiscussionsTest {

    /** @var array */
    protected static $groups;

    /** @var array */
    protected static $secretGroups;

    /** @var array List of userID's */
    protected static $userIDs;

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
    public static function setupBeforeClass(): void {
        self::$addons = ['vanilla', 'groups'];
        parent::setupBeforeClass();
        \PermissionModel::resetAllRoles();

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
        // Create secret groups
        for ($i = 1; $i <= 2; $i++) {
            $groupTxt = uniqid(__CLASS__." $i ");
            self::$secretGroups[] = $groupsAPIController->post([
                'name' => $groupTxt,
                'description' => $groupTxt,
                'format' => 'Markdown',
                'privacy' => 'secret',
            ]);
        }
        // Create members
        self::createUsers(8);
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
     * Verify group count is incremented when discussions added via the API.
     */
    public function testDiscussionIncrement() {
        $groupID = self::$groups[0]['groupID'];
        $group = $this->api()->get("groups/{$groupID}")->getBody();

        $this->createDiscussion($groupID);

        $updatedGroup = $this->api()->get("groups/{$groupID}")->getBody();
        $expectedCount = $group["countDiscussions"] + 1;

        $this->assertEquals($expectedCount, $updatedGroup["countDiscussions"]);
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

    /**
     * Create Users for test.
     *
     * @param int $roleID The roleID to associate to the new user.
     */
    protected static function createUsers($roleID) {
        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = static::container()->get('UsersAPIController');

        $classParts = explode('\\', __CLASS__);
        $className = $classParts[count($classParts) - 1];
        for ($i = 1; $i <= 2; $i++) {
            $user = $usersAPIController->post([
                'name' => self::randomUsername(),
                'email' => "{$className}{$i}$i@example.com",
                'password' => "$%#$&ADSFBNYI*&WBV$i",
                'roles' => [
                   'roleID' => $roleID,
                ]
            ]);
            self::$userIDs[] = $user['userID'];
        }
    }

    /**
     * Create a discussion in a group.
     *
     * @param int $groupID The group the discussion will be created in.
     * @return int $discussionID The id of the newly created discussion.
     */
    protected function createDiscussion($groupID) {
        $discussion = $this->api()->post("/discussions", [
                "name" => "test",
                "body" => "Hello Again",
                "format" => "Markdown",
                "categoryID" => 2,
                "groupID" => $groupID,
            ])->getBody();

        $discussionID = $discussion['discussionID'] ?? 0 ;
        return $discussionID;
    }

    /**
     * Test /discussion/:id endpoint with a secret group.
     */
    public function testSecretGroupDiscussionID() {
        $secretGroupID = self::$secretGroups[0]['groupID'];
        $secretDiscussionID = $this->createDiscussion($secretGroupID);

        //add user as member to secret group.
        /** @var \GroupModel $groupModel */
        $groupModel = static::container()->get('GroupModel');
        $groupModel->resetCachedPermissions();
        $groupModel->addUser($secretGroupID, self::$userIDs[0], 'Member');

        // Set session to user 3.
        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$userIDs[0], false, false);

        $indexUrl = $this->indexUrl();
        $result = $this->api()->get($indexUrl.'/'.$secretDiscussionID);
        $this->assertEquals(200, $result->getStatusCode());
        $requestedDiscussion = $result->getBody();
        $this->assertEquals($secretDiscussionID, $requestedDiscussion['discussionID']);
    }

    /**
     * Test /discussion/:id endpoint with a secret group and non member.
     */
    public function testFailSecretGroupDiscussionID() {
        $secretGroupID = self::$secretGroups[0]['groupID'];
        $secretDiscussionID = $this->createDiscussion($secretGroupID);

        // Set session to user 4.
        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$userIDs[1], false, false);

        $indexUrl = $this->indexUrl();

        try {
            $this->api()->get($indexUrl.'/'.$secretDiscussionID);
        } catch (ForbiddenException $e) {
            $this->assertSame(
                "You need the Vanilla.Discussions.View permission to do that.",
                $e->getDescription()
            );
            return;
        }

        $this->fail("Permission error not encountered.");
    }
}
