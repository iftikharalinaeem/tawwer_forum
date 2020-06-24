<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use OnlineModel;
use OnlinePlugin;
use UserModel;

/**
 * Test the Online addon's API v2 capabilities.
 */
class OnlineTest extends AbstractAPIv2Test {

    /** @var OnlineModel */
    private static $onlineModel;

    /** @var OnlinePlugin */
    private static $onlinePlugin;

    /** @var UserModel */
    private static $userModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        parent::setUp();
        echo null;
    }

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['vanilla', 'online'];
        parent::setUpBeforeClass();

        /** @var OnlineModel $onlineModel */
        self::$onlineModel = self::container()->get(OnlineModel::class);
        /** @var OnlinePlugin $userModel */
        self::$onlinePlugin = self::container()->get(OnlinePlugin::class);
        /** @var UserModel $userModel */
        self::$userModel = self::container()->get(UserModel::class);
        OnlinePlugin::setCachingRequired(false);
    }

    /**
     * Add online user data for testing.
     *
     * @return array
     */
    private function addOnline(): array {
        // Reset the table.
        self::$onlineModel->SQL->truncate('Online');

        // Grab users. Insert them into the Online table.
        $users = self::$userModel->get('UserID', 'asc', 5, '')->resultArray();
        $rows = [];
        foreach ($users as $user) {
            $row = [
                'UserID' => $user['UserID'],
                'Name' => $user['Name'],
                'Timestamp' => date('Y-m-d H:i:s')
            ];
            $rows[] = $row;
            self::$onlineModel->insert($row);
        }

        // Bust the function's internal cache.
        self::$onlinePlugin->getAllOnlineUsers(true);

        return $rows;
    }

    /**
     * Provide test data for setting a user's hidden status.
     *
     * @return array
     */
    public function providePutHidden(): array {
        $data = [
            [true],
            [false]
        ];
        return $data;
    }

    /**
     * Test index of online users.
     */
    public function testOnlineIndex() {
        $users = $this->addOnline();
        $response = $this->api()->get('online');

        $this->assertEquals(200, $response->getStatusCode());
        $responseBody = $response->getBody();
        $expectedUserIDs = array_column($users, 'UserID');
        $actualUserIDs = array_column($responseBody, 'userID');

        // Make sure everyone we expect is "online".
        $this->assertEmpty(array_diff($expectedUserIDs, $actualUserIDs));
        // Make sure nobody's "online" that wasn't explicitly added.
        $this->assertEmpty(array_diff($actualUserIDs, $expectedUserIDs));
    }

    /**
     * Test getting total counts of users.
     */
    public function testOnlineCounts() {
        $users = $this->addOnline();
        $usersOnline = count($users);
        $response = $this->api()->get('online/counts');

        $this->assertEquals(200, $response->getStatusCode());
        $responseBody = $response->getBody();
        $this->assertEquals($usersOnline, $responseBody['users']);
    }

    /**
     * Test setting a user's "Private Mode" flag.
     *
     * @param bool $hidden
     * @dataProvider providePutHidden
     */
    public function testPutHidden(bool $hidden) {
        $user = self::$userModel->get()->firstRow(\DATASET_TYPE_ARRAY);
        $userID = $user['UserID'];

        $this->api()->put("users/{$userID}/hidden", ['hidden' => $hidden]);
        $userResponse = $this->api()->get("users/{$userID}")->getBody();
        $this->assertEquals($hidden, $userResponse['hidden']);

        $userAttribute = self::$userModel->getAttribute($userID, OnlinePlugin::PRIVATE_MODE_ATTRIBUTE, null);
        $this->assertEquals($hidden, $userAttribute);
    }
}
