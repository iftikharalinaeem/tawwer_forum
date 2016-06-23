<?php
/**
 * RoleTrackerModel
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Class RoleTrackerModel
 */
class RoleTrackerModel extends Gdn_Model {

    /**
     * @var $roleModel RoleModel
     */
    private $roleModel;

    /**
     * 'FieldName' => keepForSave
     */
    protected static $roleTrackerFields = [
        'RoleID' => true,
        'Name' => false,
        'IsTracked' => true,
        'TrackerTagID' => false,
    ];

    /**
     * Class constructor.
     */
    public function __construct(RoleModel $roleModel) {
        parent::__construct('RoleTracker');
        $this->roleModel = $roleModel;
    }

    /**
     * Return an instance of RoleTrackerModel
     *
     * @return RoleTrackerModel roleTrackerModel
     */
    public static function instance() {
        static $roleTrackingModel = null;

        if ($roleTrackingModel === null) {
            $roleTrackingModel = new RoleTrackerModel(new RoleModel());
        }

        return $roleTrackingModel;
    }

    /**
     * Filters out non tracked roles.
     *
     * @param array $roles Roles to be filtered.
     * @return array Tracked roles.
     */
    public static function filterRoles($roles) {
        return array_filter(
            $roles,
            function($role) {
                return (bool)$role['IsTracked'];
            }
        );
    }

    /**
     * Get every public role with relevant data for this model.
     *
     * Ignore roles that do not support signin (guest, etc).
     *
     * @return array Public roles.
     */
    public function getPublicRoles($refreshCache = false) {
        static $roles = null;

        if ($refreshCache || $roles === null) {
            $roles = array_filter(
                $this->roleModel->getByPermission('Garden.SignIn.Allow')->resultArray(),
                'RoleModel::filterPersonalInfo'
            );

            $roles = $this->filterRoleFields($roles);
        }

        return $roles;
    }

    /**
     * Get every tracked role.
     *
     * @params bool $refreshCache Force a cache refresh if true
     * @return array Tracked roles.
     */
    public function getTrackedRoles($refreshCache = false) {
        static $trackedRoles = null;

        if ($refreshCache || $trackedRoles === null) {
            $roles = self::filterRoles($this->getPublicRoles($refreshCache));
        }

        return $roles;
    }

    /**
     * Get tracked roles of a specific user.
     *
     * @param int $userID The user identifier
     * @return array The tracked roles, if any.
     */
    public function getUserTrackedRoles($userID) {
        return self::filterRoles(
            $this->roleModel->getByUserID($userID)->resultArray()
        );
    }

    /**
     * Take the roles and flatten the structure to be used in a Form.
     *
     * @param bool $forSave filters out every fields that are not required when saving.
     * @return array The form data.
     */
    public function getFormData($forSave) {
        $formData = [];

        $roles = $this->getPublicRoles();
        if ($forSave) {
            $roles = $this->filterFieldsForSave($roles);
        }

        foreach ($roles as $roleID => $role) {
            foreach ($role as $fieldName => $fieldValue) {
                $formData[$roleID.'_'.$fieldName] = $fieldValue;
            }
        }

        return $formData;
    }

    /**
     * Take Form data and convert it into a "per role" structure.
     *
     * @param array $formData
     * @return array
     */
    public function convertFormData($formData) {
        $roleData = [];

        // Keep only what is good
        $validFormData = $this->getFormData(true);
        $formData = array_intersect_key($formData, $validFormData);

        foreach ($formData as $roleIDFieldName => $fieldValue) {
            $pos = strpos($roleIDFieldName, '_');
            if ($pos && strlen($roleIDFieldName) > $pos + 1) {
                $roleID = substr($roleIDFieldName, 0, $pos);
                $fieldName = substr($roleIDFieldName, $pos + 1);

                if (!isset($roles[$roleID])) {
                    $roles[$roleID] = [];
                }

                $roleData[$roleID][$fieldName] = $fieldValue;
            }
        }

        return $roleData;
    }

    /**
     * Save data received from a Form.
     *
     * @param array $formPostValues The data to save.
     * @param bool $settings Unused
     *
     * @return bool Returns true on success, false otherwise.
     */
    public function save($formPostValues, $settings = false) {

        $rolesData = $this->convertFormData($formPostValues);
        $roles = $this->getPublicRoles();
        $success = true;

        $database = $this->roleModel->Database;
        $database->beginTransaction();

        foreach ($rolesData as $roleID => $roleData) {
            if ($roles[$roleID]['IsTracked'] == $roleData['IsTracked']) {
                continue;
            }

            // If we check a role as tracked for the first time we need to create a Tracker tag for it.
            $trackerTagIdExist = !empty($roles[$roleID]['TrackerTagID']);
            if ($roleData['IsTracked'] && !$trackerTagIdExist) {
                $newTag = [
                    'Name' => TagModel::tagSlug($roles[$roleID]['Name']),
                    'FullName' => $roles[$roleID]['Name'],
                    'Type' => 'Tracker',
                    'CategoryID' => -1,
                    'InsertUserID' => Gdn::session()->UserID,
                    'DateInserted' => Gdn_Format::toDateTime(),
                    'CountDiscussions' => 0
                ];
                $tagID = $database->sql()->options('Ignore', true)->insert('Tag', $newTag);

                $success = $success && $tagID;
                $roleData['TrackerTagID'] = $tagID;
            }

            $success = $success && $this->roleModel->update($roleData, ['RoleID' => $roleID]);
        }

        $success ? $database->commitTransaction() : $database->rollbackTransaction();

        return $success;
    }

    /**
     * Remove fields from roles data that are not used by this model.
     *
     * @param array $roles List of roles.
     * @return array
     */
    protected function filterRoleFields($roles) {
        $rolesData = [];
        foreach ($roles as $role) {
            $rolesData[$role['RoleID']] = array_intersect_key($role, self::$roleTrackerFields);
        }
        return $rolesData;
    }

    /**
     * Filter out unwanted fields for the purpose of saving.
     *
     * @param $roles Roles' data
     * @return array Filtered roles' data
     */
    protected function filterFieldsForSave($roles) {
        $rolesData = [];
        foreach ($roles as $roleID => $roleData) {
            $rolesData[$roleID] = array_intersect_key(
                $roleData,
                array_filter(
                    self::$roleTrackerFields
                )
            );
        }
        return $rolesData;
    }
}
