<?php
/**
 *
 */
class RoleTrackerModel {

    /**
     * @var $roleModel RoleModel
     */
    private $roleModel;

    /**
     * @var $roleModel RoleModel
     */
    private $tagModel;

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
    public function __construct(RoleModel $roleModel, TagModel $tagModel) {
        $this->roleModel = $roleModel;
        $this->tagModel = $tagModel;
    }

    /**
     * @param $roles
     * @return array
     */
    public static function filterFieldsForSave($roles) {
        $rolesData = [];
        foreach($roles as $roleID => $roleData) {
            $rolesData[$roleID] = array_intersect_key(
                $roleData,
                array_filter(
                    self::$roleTrackerFields
                )
            );
        }
        return $rolesData;
    }

    /**
     * @param $roles
     * @return array
     */
    public static function filterOutNonTrackedRole($roles) {
        return array_filter(
            $roles,
            function($role) {
                return (bool)$role['IsTracked'];
            }
        );
    }

    /**
     * @return array
     */
    public function getPublicRoles($refreshCache = false) {
        static $roles;

        if ($refreshCache || $roles === null) {
            $roles = array_filter(
                $this->roleModel->get()->resultArray(),
                'RoleModel::filterPersonalInfo'
            );

            $roles = $this->filterOutUnusedRoleFields($roles);
        }

        return $roles;
    }

    /**
     * @return array
     */
    public function getTrackedRoles($refreshCache = false) {
        static $trackedRoles;

        if ($refreshCache || $trackedRoles === null) {
            $roles = self::filterOutNonTrackedRole($this->getPublicRoles($refreshCache));
        }

        return $roles;
    }

    /**
     * @param $roles
     * @return array
     */
    protected function filterOutUnusedRoleFields($roles) {
        $rolesData = [];
        foreach($roles as $role) {
            $rolesData[$role['RoleID']] = array_intersect_key($role, self::$roleTrackerFields);
        }
        return $rolesData;
    }

    /**
     * @param $userID
     * @return array
     */
    public function getUserTrackedRoles($userID) {
        return self::filterOutNonTrackedRole(
            $this->roleModel->getByUserID($userID)->resultArray()
        );
    }

    /**
     *
     *
     * @param bool $forSave
     * @return array
     */
    public function getFormData($forSave) {
        $formData = [];

        $roles = $this->getPublicRoles();
        if ($forSave) {
            $roles = self::filterFieldsForSave($roles);
        }

        foreach($roles as $roleID => $role) {
            foreach($role as $fieldName => $fieldValue) {
                $formData[$roleID.'_'.$fieldName] = $fieldValue;
            }
        }

        return $formData;
    }

    /**
     *
     *
     * @param array $formData
     * @return array
     */
    public function formDataToRoleData($formData) {
        $roleData = [];

        // Keep only what is good
        $validFormData = $this->getFormData(true);
        $formData = array_intersect_key($formData, $validFormData);

        foreach($formData as $roleIDFieldName => $fieldValue) {
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
     * Save received from a Form.
     *
     * @param array $formPostValues The data to save.
     * @return bool Returns true on success, false otherwise.
     */
    public function save($formPostValues) {
        // Get current data
        $rolesData = $this->formDataToRoleData($formPostValues);
        $roles = $this->getPublicRoles();
        $success = true;

        $this->roleModel->Database->beginTransaction();
            foreach($rolesData as $roleID => $roleData) {
                if ($roles[$roleID]['IsTracked'] == $roleData['IsTracked']) {
                    continue;
                }

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
                    $tagID = $this->roleModel->Database->sql()->options('Ignore', true)->insert('Tag', $newTag);

                    $success = $success && $tagID;
                    $roleData['TrackerTagID'] = $tagID;
                }

                $success = $success && $this->roleModel->update($roleData, ['RoleID' => $roleID]);
            }
        $success ? $this->roleModel->Database->commitTransaction() : $this->roleModel->Database->rollbackTransaction();

        return $success;
    }

    public function validationResults() {
        return [];
    }
}
