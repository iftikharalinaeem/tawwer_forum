<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace VanillaTests\APIv2;

/**
 * Class AbstractGroupsSubResource
 */
abstract class AbstractGroupsSubResource extends AbstractAPIv2Test {
    /** @var array $userIDs List of userIDs created to this test suite. */
    protected static $userIDs;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::$userIDs = [];
        self::$addons = ['vanilla', 'conversations', 'groups'];
        parent::setupBeforeClass();
        \PermissionModel::resetAllRoles();

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \UsersApiController $usersAPIController */
        $usersAPIController = static::container()->get('UsersAPIController');

        $classParts = explode('\\', __CLASS__);
        $className = $classParts[count($classParts) - 1];
        for ($i = 1; $i <= 3; $i++) {
            $user = $usersAPIController->post([
                'name' => self::randomUsername(),
                'email' => "{$className}{$i}$i@example.com",
                'password' => "$%#$&ADSFBNYI*&WBV$i",
            ]);
            self::$userIDs[] = $user['userID'];
        }

        // Disable email sending.
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get('Config');
        $config->set('Garden.Email.Disabled', true, true, false);

        $session->end();
    }

    /**
     * Create a group.
     *
     * @param string $testName Name of the test function from which the group is created.
     * @param bool $isPublic Whether the group is public or not.
     * @return array The created group.
     */
    protected function createGroup($testName, $isPublic) {
         /** @var \GroupsApiController $groupsAPIController */
        $groupsAPIController = static::container()->get('GroupsApiController');

        $groupTxt = uniqid(__CLASS__." $testName ");
        $group = $groupsAPIController->post([
            'name' => $groupTxt,
            'description' => $groupTxt,
            'format' => 'Markdown',
            'privacy' => $isPublic ? 'public' : 'private',
        ]);

        return (array)$group;
    }

    /**
     * Create an endpoint URL.
     * /groups/:groupID[/:action][/:userID]
     *
     * @param int $groupID
     * @param string|null $action
     * @param int|null $userID
     * @return string
     */
    protected function createURL($groupID, $action = null, $userID=null) {
         $parts = ["/groups/$groupID"];
         if ($action) {
             $parts[] = $action;
         }
         if ($userID) {
             $parts[] = $userID;
         }

         return implode('/', $parts);
    }

    /**
     * Clear the in memory cache of group permissions.
     */
    protected function clearGroupMemoryCache() {
        /** @var \GroupModel $model */
        $model = \Gdn::getContainer()->get(\GroupModel::class);
        $model->resetCachedPermissions();
    }
}
