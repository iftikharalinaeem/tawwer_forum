<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Groups Application - Group Module
 * 
 * Shows a group box with basic group info.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package groups
 * @since 1.0
 */
class GroupModule extends Gdn_Module {

    /** @var   */
    protected $GroupID;

    /** @var null  */
    protected $Group = null;

    /**
     * GroupModule constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'groups';
    }

    /**
     * Set the GroupID
     *
     * @param string $Name
     * @param mixed $Value
     */
    public function __set($Name, $Value) {
        switch ($Name) {
            case 'GroupID':
                $this->GroupID = $Value;
                break;
        }
    }

    /**
     * Retrieve the group info for this GroupID
     *
     * @return void
     */
    public function GetData($GroupID = null) {

        if (is_null($GroupID))
            $GroupID = $this->GroupID;

        // Callable multiple times
        if (!is_null($this->Group) && $this->Group['GroupID'] == $GroupID) return;

        // Load the group
        $GroupModel = new GroupModel();
        $this->Group = $GroupModel->GetID($GroupID, DATASET_TYPE_ARRAY);

    }

    /**
     * Render group module
     *
     * @return type
     */
    public function ToString() {
        $this->GetData();
        $this->SetData('Group', $this->Group);
        return $this->FetchView();
    }

}