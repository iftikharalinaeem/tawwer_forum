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
    public $GroupID = NULL;

    /** @var null  */
    public $UserID = NULL;

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
    protected function GetData() {
        $Model = new GroupModel();

        if ($this->GroupID) {
            $this->SetData('Group', $Model->GetID($this->GroupID));

            $Rows = $Model->GetApplicants($this->GroupID, array('UserID' => $this->UserID));
            $this->SetData('Application', array_pop($Rows));
        }
    }

    /**
     * @return string
     */
    public function ToString() {
        if (!$this->GroupID)
            $this->GroupID = Gdn::Controller()->Data('Group.GroupID');
        if (!$this->UserID)
            $this->UserID = Gdn::Session()->UserID;

        if (!$this->GroupID || !$this->GroupID)
            return '';

        $this->GetData();
        return parent::ToString();
    }
}