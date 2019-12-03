<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

/**
 * Class AbstractBadgesSubResource
 *
 * @package VanillaTests\APIv2
 */
abstract class AbstractBadgesSubResource extends AbstractAPIv2Test {

    /** @var array $userIDs List of userIDs created to this test suite. */
    protected static $userIDs;

    protected static $customBadgeCounter = 0;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::$userIDs = [];
        self::$addons = ['vanilla', 'badges'];
        parent::setupBeforeClass();

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = static::container()->get('UsersAPIController');

        $classParts = explode('\\', __CLASS__);
        $className = $classParts[count($classParts) - 1];
        for ($i = 1; $i <= 5; $i++) {
            $user = $usersAPIController->post([
                'name' => self::randomUsername(),
                'email' => "{$className}{$i}@example.com",
                'password' => "$%#$&ADSFBNYI*&WBV$i",
            ]);
            self::$userIDs[] = $user['userID'];
        }

        static::setupBeforeClassAPIHook();

        $session->end();
    }

    /**
     * Allow sub class to API calls during the setup.
     */
    public static function setupBeforeClassAPIHook() {}

    /**
     * Create a badge.
     *
     * @param string $testName Name of the test function from which the badge is created.
     * @param bool $isEnabled Whether the badge is enabled or not.
     * @return array The created badge.
     */
    protected function createBadge($testName, $isEnabled) {
        /** @var \BadgesApiController $badgesAPIController */
        $badgesAPIController = static::container()->get('BadgesAPIController');

        $i = self::$customBadgeCounter++;

        $classParts = explode('\\', __CLASS__);
        $className = $classParts[count($classParts) - 1];
        $name = "Badge{$i}";
        $slug = strtolower($name);

        $badge = $badgesAPIController->post([
            'name' => $name,
            'key' => $slug,
            'body' => "$className $testName {$i}",
            'points' => $i,
            'class' => $slug,
            'classLevel' => $i,
            'enabled' => $isEnabled,
        ]);


        return $badge;
    }

    /**
     * Create an endpoint URL.
     * /badges/:id[/:action][/:userID]
     *
     * @param int $badgeID
     * @param string|null $action
     * @param int|null $userID
     * @return string
     */
    protected function createURL($badgeID, $action = null, $userID = null) {
         $parts = ["/badges/$badgeID"];
         if ($action) {
             $parts[] = $action;
         }
         if ($userID) {
             $parts[] = $userID;
         }

         return implode('/', $parts);
    }
}
