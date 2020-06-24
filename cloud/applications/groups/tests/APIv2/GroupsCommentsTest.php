<?php
/**
 * @author VanillaForums.
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

/**
 * Test  /api/v2/comments with groups
 */
class GroupsCommentsTest extends AbstractAPIv2Test {
    /** @var array */
    protected static $groups;
    /** @var array */
    protected static $secretGroups;
    /** @var array */
    protected static $privateGroups;
    /** @var string */
    protected $baseUrl = '/comments';
    /** @var array List of userID's */
    protected static $userIDs;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['vanilla', 'groups'];
        parent::setupBeforeClass();
        \PermissionModel::resetAllRoles();

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], true, false);

        /** @var \GroupsApiController $groupsAPIController */
        $groupsAPIController = static::container()->get('GroupsApiController');
        // Create test groups
        for ($i = 1; $i <= 2; $i++) {
            self::$groups[] = $groupsAPIController->post([
                'name' => uniqid(__CLASS__),
                'description' => uniqid(__CLASS__),
                'format' => 'Markdown',
                'privacy' => 'public',
            ]);
            self::$secretGroups[] = $groupsAPIController->post([
                'name' => uniqid(__CLASS__),
                'description' => uniqid(__CLASS__),
                'format' => 'Markdown',
                'privacy' => 'secret',
            ]);
            self::$privateGroups[] = $groupsAPIController->post([
                'name' => uniqid(__CLASS__),
                'description' => uniqid(__CLASS__),
                'format' => 'Markdown',
                'privacy' => 'private',
            ]);
        }
        // Create members
        self::createUsers(8);
        $session->end();
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
        for ($i = 1; $i <= 6; $i++) {
            $user = $usersAPIController->post([
                'name' => self::randomUsername(),
                'email' => "{$className}{$i}$i@example.com",
                'password' => "$%#$&ADSFBNYI*&WBV$i",
                'roles' => [
                    'roleID' => $roleID
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
            "body" => "Test discussion",
            "format" => "Markdown",
            "categoryID" => 2,
            "groupID" => $groupID,
        ])->getBody();

        $discussionID = $discussion['discussionID'] ?? 0;
        return $discussionID;
    }

    /**
     * Create a comment in a group's discussion.
     *
     * @param int $discussionID The discussion were the comment will be created in.
     * @return int $commentID The id of the newly created comment.
     */
    protected function createComment($discussionID) {
        $comment = $this->api()->post("/comments", [
            "body" => "test comment",
            "format" => "Markdown",
            "discussionID" => $discussionID
        ])->getBody();

        $commentID = $comment['commentID'] ?? 0;
        return $commentID;
    }

    /**
     * Test /comments/:discussionID endpoint with a public group and a member.
     */
    public function testPublicGroupMemberCommentID() {
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$userIDs[0], false, false);
        $publicGroupID = self::$groups[0]['groupID'];
        // add user as member to public group.
        $groupModel = static::container()->get('GroupModel');
        $groupModel->resetCachedPermissions();
        $groupModel->addUser($publicGroupID, self::$userIDs[0], 'Member');
        $publicDiscussionID = $this->createDiscussion($publicGroupID);
        $publicCommentID = $this->createComment($publicDiscussionID);
        $result = $this->api()->get($this->baseUrl, ['discussionID' => $publicDiscussionID]);
        $this->assertEquals(200, $result->getStatusCode());
        $requestedDiscussion = $result->getBody();
        $this->assertEquals($publicCommentID, $requestedDiscussion[0]['commentID']);
    }

    /**
     * Test /comments/:discussionID endpoint with a public group and a guest user.
     */
    public function testPublicGroupNotMemberCommentID() {
        $publicGroupID = self::$groups[1]['groupID'];
        $publicDiscussionID = $this->createDiscussion($publicGroupID);
        $publicCommentID = $this->createComment($publicDiscussionID);
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$userIDs[1], false, false);
        $result = $this->api()->get($this->baseUrl, ['discussionID' => $publicDiscussionID]);
        $this->assertEquals(200, $result->getStatusCode());
        $requestedDiscussion = $result->getBody();
        $this->assertEquals($publicCommentID, $requestedDiscussion[0]['commentID']);
    }

    /**
     * Test /comments/:discussionID endpoint with a secret group and a member.
     */
    public function testSuccessSecretGroupCommentID() {
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$userIDs[2], false, false);
        $secretGroupID = self::$secretGroups[0]['groupID'];
        // add user as member to secret group.
        $groupModel = static::container()->get('GroupModel');
        $groupModel->resetCachedPermissions();
        $groupModel->addUser($secretGroupID, self::$userIDs[2], 'Member');
        $secretDiscussionID = $this->createDiscussion($secretGroupID);
        $secretCommentID = $this->createComment($secretDiscussionID);
        $result = $this->api()->get($this->baseUrl, ['discussionID' => $secretDiscussionID]);
        $this->assertEquals(200, $result->getStatusCode());
        $requestedDiscussion = $result->getBody();
        $this->assertEquals($secretCommentID, $requestedDiscussion[0]['commentID']);
    }

    /**
     * Test /comments/:discussionID endpoint with a secret group and a guest user.
     */
    public function testFailSecretGroupCommentID() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You need the Vanilla.Discussions.View permission to do that.');
        $secretGroupID = self::$secretGroups[1]['groupID'];
        $secretDiscussionID = $this->createDiscussion($secretGroupID);
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$userIDs[3], false, false);
        $this->createComment($secretDiscussionID);
    }

    /**
     * Test /comments/:discussionID endpoint with a private group and a member.
     */
    public function testSuccessPrivateGroupCommentID() {
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$userIDs[4], false, false);
        $privateGroupID = self::$privateGroups[0]['groupID'];
        // add user as member to secret group.
        $groupModel = static::container()->get('GroupModel');
        $groupModel->resetCachedPermissions();
        $groupModel->addUser($privateGroupID, self::$userIDs[4], 'Member');
        $privateDiscussionID = $this->createDiscussion($privateGroupID);
        $privateCommentID = $this->createComment($privateDiscussionID);
        $result = $this->api()->get($this->baseUrl, ['discussionID' => $privateDiscussionID]);
        $this->assertEquals(200, $result->getStatusCode());
        $requestedDiscussion = $result->getBody();
        $this->assertEquals($privateCommentID, $requestedDiscussion[0]['commentID']);
    }

    /**
     * Test /comments/:discussionID endpoint with a private group and a guest.
     */
    public function testFailPrivateGroupCommentID() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You need the Vanilla.Discussions.View permission to do that.');
        $privateGroupID = self::$privateGroups[1]['groupID'];
        $privateDiscussionID = $this->createDiscussion($privateGroupID);
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$userIDs[5], false, false);
        $this->createComment($privateDiscussionID);
    }
}
