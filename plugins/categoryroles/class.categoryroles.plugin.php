<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 */

$PluginInfo['categoryroles'] = [
    'Name' => 'Category Roles',
    'Description' => 'Grant user permissions from select roles, per-category.',
    'Version' => '1.0',
    'RequiredApplications' => ['Vanilla' => '2.3'],
    'Author' => 'Ryan Perry',
    'AuthorEmail' => 'ryan.p@vanillaforums.com',
    'MobileFriendly' => true,
    'License' => 'Proprietary'
];

/**
 * Facilitate the assignment of role permissions, per-category, for users.
 */
class CategoryRolesPlugin extends Gdn_Plugin {

    /**
     * Get the default category permissions for a role.
     *
     * @param int $roleID
     * @return array
     */
    private function getDefaultCategoryPermissions($roleID) {
        // Keep track of permissions we've already looked up to avoid looking them up again.
        static $defaults = [];

        // Do we need to bother looking these up?
        if (!isset($defaults[$roleID])) {
            // Grab the role's default category permissions.
            $rows = Gdn::permissionModel()->getWhere([
                'RoleID' => $roleID,
                'JunctionTable' => 'Category',
                'JunctionColumn' => 'PermissionCategoryID',
                'JunctionID' => -1
            ])->resultArray();

            if (!empty($rows)) {
                // We should only have one row.  Pull it off the top.
                $defaults[$roleID] = current($rows);
            } else {
                // Something bad happened here.  Default to an empty result.
                $defaults[$roleID] = [];
            }
        }

        return $defaults[$roleID];
    }

    /**
     * Perform database structure updates.
     */
    public function structure() {
        Gdn::structure()
            ->table('CategoryRole')
            ->column('UserID', 'int', false, ['index', 'primary'])
            ->column('RoleID', 'int', false, 'primary')
            ->column('CategoryID', 'int', false, 'primary')
            ->set();
    }

    /**
     * Run initial setup routines.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Hook into the retrieval of a user's permissions.
     *
     * @param UserModel $sender
     * @param array $args
     */
    public function userModel_loadPermissions_handler($sender, $args) {
        $userID = $args['UserID'];
        $permissions = $args['Permissions'];

        // Don't bother trying to get category:role associations for a guest.
        if ($userID === 0) {
            return;
        }

        // Incoming permissions have to be an object instance.  Otherwise, log the anomaly and bail.
        if (!is_a($permissions, 'Vanilla\Permissions')) {
            Logger::error('$permissions not an instance of Vanilla\Permissions');
            return;
        }

        // Grab the per-category role entries.
        $categoryRoleModel = new CategoryRoleModel();
        $categoryRoles = $categoryRoleModel->getWhere(['UserID' => $userID])->resultArray();

        // Nothing to do here.
        if (count($categoryRoles) === 0) {
            return;
        }

        // Iterate through our per-category role permissions.
        foreach ($categoryRoles as $row) {
            // Fetch the default (JunctionID = -1) permissions for the current role.
            $categoryPermissions = $this->getDefaultCategoryPermissions($row['RoleID']);

            // Nothing to do here.
            if (empty($categoryPermissions)) {
                continue;
            }

            // Revise the permission row to indicate the target category as the junction ID.
            $categoryPermissions['JunctionID'] = $row['CategoryID'];

            // We can easily merge Permission objects.  New up an instance and load the current permissions.
            $newPermissions = new Vanilla\Permissions();
            $newPermissions->compileAndLoad([$categoryPermissions]);
            $permissions->merge($newPermissions);

            // No longer needed.  Free up some memory.
            unset($newPermissions);
        }
    }
}
