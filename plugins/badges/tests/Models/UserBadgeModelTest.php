<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Badges\Models;

use PHPUnit\Framework\TestCase;
use UserModel;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `UserBadgeModel`.
 */
class UserBadgeModelTest extends TestCase {
    use SiteTestTrait;

    /**
     * @var \UserBadgeModel
     */
    private $model;

    /**
     * @var \BadgeModel
     */
    private $badgeModel;
    /**
     * @var array
     */
    private $manualBadge;

    /**
     * @var int
     */
    private $systemUserID;

    /**
     * @var int
     */
    private $userID;

    /**
     * Get the names of addons to install.
     *
     * @return string[] Returns an array of addon names.
     */
    protected static function getAddons(): array {
        return ['vanilla', 'badges'];
    }

    /**
     * Set up BadgeModel for testing.
     */
    public function setUp(): void {
        $this->backupSession();
        $this->model = $this->container()->get(\UserBadgeModel::class);
        $this->badgeModel = $this->container()->get(\BadgeModel::class);

        $badge = $this->badgeModel->getWhere(['type' => \BadgeModel::TYPE_MANUAL])->firstRow(DATASET_TYPE_ARRAY);

        if ($badge === false) {
            $set = [
                'Name' => 'Test',
                'Slug' => 'test',
                'Type' => \BadgeModel::TYPE_MANUAL,
            ];
            $id = $this->badgeModel->insert($set);
            if ($id === false) {
                throw new \Exception("Could not create manual badge fixture.");
            } else {
                $badge = $this->badgeModel->getWhere(['type' => \BadgeModel::TYPE_MANUAL])->firstRow(DATASET_TYPE_ARRAY);
            }
        }
        $this->manualBadge = $badge;
        $this->userID = self::$siteInfo['adminUserID'];
        $this->systemUserID = self::container()->get(UserModel::class)->getSystemUserID();
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->restoreSession();
    }

    /**
     * Give a user a badge and then get the corresponding user badge row.
     *
     * @param int $userID
     * @param mixed $badge The badge ID, slug, or badge row.
     * @param null $reason
     * @return array
     */
    protected function giveAndGet(int $userID, $badge, $reason = null): array {
        $badgeID = $this->getBadgeID($badge);

        $r = $this->model->give($userID, $badgeID, $reason);
        $this->assertTrue($r);

        $userBadge = $this->model->getByUser($userID, $badgeID);
        $this->assertNotFalse($userBadge);
        return $userBadge;
    }

    /**
     * Giving a badge with no session should not fail.
     *
     * @see https://github.com/vanilla/internal/issues/1101
     */
    public function testGiveNoSession() {
        \Gdn::session()->end();
        $userBadge = $this->giveAndGet($this->userID, 'comment');
        $this->assertEquals($this->systemUserID, $userBadge['InsertUserID']);
    }

    /**
     * Manually giving a badge should be inserted by the session user.
     */
    public function testGiveNotSystemUser() {
        $userBadge = $this->giveAndGet($this->userID, $this->manualBadge, '');
        $this->assertNotEquals($this->systemUserID, $userBadge['InsertUserID']);
    }

    /**
     * Giving a badge without a reason should be given by the system user.
     */
    public function testGiveSystemUser() {
        $userBadge = $this->giveAndGet($this->userID, 'photogenic');
        $this->assertEquals($this->systemUserID, $userBadge['InsertUserID']);
    }

    /**
     * Test the request and decline workflow.
     */
    public function testRequestAndDecline() {
        $badgeID = $this->getBadgeID('comment-100');
        $r = $this->model->request($this->userID, $badgeID, 'test');
        $this->assertTrue($r);
        $userBadge = $this->model->getByUser($this->userID, $badgeID);
        $this->assertEquals($this->userID, $userBadge['InsertUserID']);
        $this->assertEquals(\UserBadgeModel::STATUS_PENDING, $userBadge['Status']);

        $this->model->declineRequest($this->userID, $badgeID);
        $userBadge = $this->model->getByUser($this->userID, $badgeID);

        $this->assertEquals($this->userID, $userBadge['InsertUserID']);
        $this->assertEquals(\UserBadgeModel::STATUS_DECLINED, $userBadge['Status']);
    }

    /**
     * Get a badge ID by slug or badge row.
     *
     * @param mixed $badge
     * @return int
     */
    protected function getBadgeID($badge) {
        if (is_string($badge)) {
            $row = $this->badgeModel->getID($badge, DATASET_TYPE_ARRAY);
            $this->assertNotFalse($row);
            $badgeID = (int)$row['BadgeID'];
        } elseif (is_array($badge)) {
            $badgeID = $badge['BadgeID'];
        } else {
            $badgeID = (int)$badge;
        }
        return $badgeID;
    }
}
