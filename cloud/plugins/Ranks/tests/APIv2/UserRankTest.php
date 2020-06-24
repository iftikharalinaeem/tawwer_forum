<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use RankModel;

class UserRankTest extends AbstractAPIv2Test {

    /**
     * Setup routine, run before the test class is instantiated.
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['ranks', 'vanilla'];
        parent::setUpBeforeClass();
    }

    /**
     * Test resetting and refreshing a user's rank.
     */
    public function testAutoRank() {
        $user = $this->api()->post('users', [
            'name' => __FUNCTION__,
            'email' => __FUNCTION__.'@example.com',
            'password' => 'password'
        ])->getBody();
        $userID = $user['userID'];

        // Make sure we're actually starting fresh.
        $this->assertNull($user['rankID']);

        // The new rank, with its high level and open criteria, should be the first one the user receives.
        $rank = $this->api()->post('ranks', [
            'name' => __FUNCTION__,
            'userTitle' => __FUNCTION__,
            'level' => 999
        ])->getBody();
        RankModel::refreshCache();

        // Assign a null rank to refresh the user rank with auto-assignment criteria.
        $this->api()->put("users/{$userID}/rank", ['rankID' => null]);

        // Refresh the user.
        $user = $this->api()->get("users/{$userID}")->getBody();

        $this->assertSame($rank['rankID'], $user['rankID']);
    }

    /**
     * Test manually assigning a rank.
     */
    public function testManualRank() {
        $user = $this->api()->post('users', [
            'name' => __FUNCTION__,
            'email' => __FUNCTION__.'@example.com',
            'password' => 'password'
        ])->getBody();
        $userID = $user['userID'];

        // Make sure we're actually starting fresh.
        $this->assertNull($user['rankID']);

        $rank = $this->api()->post('ranks', [
            'name' => __FUNCTION__,
            'userTitle' => __FUNCTION__,
            'level' => 6,
            'criteria' => ['manual' => true]
        ])->getBody();
        RankModel::refreshCache();

        $this->api()->put("users/{$userID}/rank", ['rankID' => $rank['rankID']]);
        $user = $this->api()->get("users/{$userID}")->getBody();
        $this->assertSame($rank['rankID'], $user['rankID']);
    }
}
