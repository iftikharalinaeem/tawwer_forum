<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

class AutoRoleByEmailPlugin extends Gdn_Plugin {
    /**
     * Add 'Domains' box to Edit Role page.
     */
    public function roleController_beforeRolePermissions_handler($sender) {
        echo '<li class="form-group">
            <div class="label-wrap">'.
                $sender->Form->label('Domains', 'Domains').
                wrap(t('RoleDomainInfo', "Assign new confirmed users to this role if their email is from one of these domains (space-separated)."), 'div', ['class' => 'info']).
            '</div>'.
            $sender->Form->textBoxWrap('Domains', ['MultiLine' => true]).
        '</li>';
    }

    /**
     * One time on enable.
     */
    public function setup() {
        removeFromConfig('EnabledPlugins.AutoRoleByEmail');
        $this->structure();
    }

    /**
     * Add 'Domains' column to Role table.
     */
    public function structure() {
        Gdn::structure()->table('Role')
            ->column('Domains', 'text', null)
            ->set();
    }

    /**
     * Handle UserModel::setField()'s event.
     *
     * @param UserModel $model
     * @param array $args Event's arguments.
     */
    public function userModel_afterSetField_handler(UserModel $model, array $args) {
        if (!empty($args['Fields']['Confirmed'])) {
            $user = $model->getID($args['UserID'], DATASET_TYPE_ARRAY);
            $this->giveRolesByEmail($model, $user);
        }
    }

    /**
     * Handle UserModel::insertInternal()'s event.
     *
     * This event is also called from UserModel::save() when a user is "created".
     *
     * @param UserModel $model
     * @param array $args Event's arguments.
     */
    public function userModel_afterInsertUser_handler(UserModel $model, array $args) {
        $user = $model->getID($args['InsertUserID'], DATASET_TYPE_ARRAY);
        if (!empty($user['Confirmed'])) {
            $this->giveRolesByEmail($model, $user);
        }
    }

    /**
     * Handle UserModel::save()'s event.
     *
     * @param UserModel $model
     * @param array $args Event's arguments.
     */
    public function userModel_afterSave_handler(UserModel $model, array $args) {
        $isInsert = !isset($args['FormPostValues']['UserID']);
        // Give roles when a user is updated and Confirmed was part of the update.
        // Inserts are managed in afterInsertUser.
        if (!$isInsert && !empty($args['FormPostValues']['ConfirmEmail'])) {
            $currentUser = (int)Gdn::session()->UserID;
            $user = $model->getID($args['UserID'], DATASET_TYPE_ARRAY);
            $isCurrentUser = $user['UserID'] === $currentUser;
            if (!empty($user['Confirmed']) && $isCurrentUser) {
                $this->giveRolesByEmail($model, $user);
            }
        }
    }

    /**
     * Give roles to user based on its email.
     *
     * @param UserModel $userModel
     * @param array $user
     * @return array
     */
    private function giveRolesByEmail(UserModel $userModel, $user) {
        // Get new user's email domain
        $parts = explode('@', $user['Email']);

        if (count($parts) !== 2) {
            return;
        }
        $domain = strtolower($parts[1]);

        // Any roles assigned?
        $roleModel = new RoleModel();
        $roleIDsToGive = [];
        $roleData = $roleModel->SQL->getWhereLike('Role', ['Domains' => $domain])->result(DATASET_TYPE_ARRAY);
        foreach ($roleData as $result) {
            $domainList = explode(' ', $result['Domains']);
            if (in_array($domain, $domainList)) {
                // Add the role to the user
                $roleIDsToGive[] = $result['RoleID'];
            }
        }

        if (!$roleIDsToGive) {
            return;
        }

        // Give the new roles.
        $currentRoles = $userModel->getRoles($user['UserID'])->resultArray();
        $currentRoleIDs = array_column($currentRoles, 'RoleID');
        $userModel->saveRoles($user['UserID'], array_unique(array_merge($currentRoleIDs, $roleIDsToGive)), false);
    }
}
