<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Class GroupUserHeaderModule
 */
class GroupUserHeaderModule extends Gdn_Module {

    /** @var null  */
    public $GroupID = null;

    /** @var null  */
    public $UserID = null;

    /**
     * GroupUserHeaderModule constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'groups';
    }

    /**
     *
     */
    protected function getData() {
        $model = new GroupModel();

        if ($this->GroupID) {
            $this->setData('Group', $model->getID($this->GroupID));

            $rows = $model->getApplicants($this->GroupID, ['UserID' => $this->UserID]);
            $this->setData('Application', array_pop($rows));
        }
    }

    /**
     * @return string
     */
    public function toString() {
        if (!$this->GroupID)
            $this->GroupID = Gdn::controller()->data('Group.GroupID');
        if (!$this->UserID)
            $this->UserID = Gdn::session()->UserID;

        if (!$this->GroupID || !$this->GroupID)
            return '';

        $this->getData();
        return parent::toString();
    }
}