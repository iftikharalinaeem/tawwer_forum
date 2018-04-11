<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use OnlineModel;
use OnlinePlugin;

/**
 * Test the Online addon's API v2 capabilities.
 */
class OnlineTest extends AbstractAPIv2Test {

    /**
     * {@inheritdoc}
     */
    public function setUp() {
        parent::setUp();
        echo null;
    }

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        self::$addons = ['vanilla', 'online'];
        parent::setUpBeforeClass();
        OnlinePlugin::setCachingRequired(false);
    }

    /**
     * Add online user data for testing.
     *
     * @return int
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    private function addOnline() {
        /** @var OnlineModel $onlineModel */
        $onlineModel = self::container()->get(OnlineModel::class);

        $rows = [
            ['UserID' => 1, 'Name' => 'System'],
            ['UserID' => 2, 'Name' => 'travis']
        ];

        foreach ($rows as $row) {
            $onlineModel->insert($row + ['Timestamp' => date('Y-m-d H:i:s')]);
        }

        $result = count($rows);
        return $result;
    }

    /**
     * Test getting total counts of users.
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function testOnlineCounts() {
        $usersOnline = $this->addOnline();
        $response = $this->api()->get('online/counts');

        $this->assertEquals(200, $response->getStatusCode());
        $responseBody = $response->getBody();
        $this->assertEquals($usersOnline, $responseBody['users']);
    }

    /**
     * Test setting a user's "Private Mode" flag.
     */
    public function testPatchPrivateMode() {
        $userID = 1; // System

        $modes = [true, false];
        foreach ($modes as $mode) {
            $this->api()->patch("users/{$userID}/privatemode", ['privateMode' => $mode]);
            $patchResponse = $this->api()->get("users/{$userID}/privatemode")->getBody();
            $this->assertEquals($mode, $patchResponse['privateMode']);
        }
    }
}
