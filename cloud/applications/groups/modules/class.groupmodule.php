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
    public $GroupID;

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
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        switch ($name) {
            case 'GroupID':
                $this->GroupID = $value;
                break;
        }
    }

    /**
     * Retrieve the group info for this GroupID
     *
     * @return void
     */
    public function getData($groupID = null) {

        if (is_null($groupID))
            $groupID = $this->GroupID;

        // Callable multiple times
        if (!is_null($this->Group) && $this->Group['GroupID'] == $groupID) return;

        // Load the group
        $groupModel = new GroupModel();
        $this->Group = $groupModel->getID($groupID, DATASET_TYPE_ARRAY);

    }

    /**
     * Render group module
     *
     * @return type
     */
    public function toString() {
        $this->getData();
        $this->setData('Group', $this->Group);
        return $this->fetchView();
    }

}
