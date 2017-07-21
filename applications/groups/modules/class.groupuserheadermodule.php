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
    protected function GetData() {
        $model = new GroupModel();

        if ($this->GroupID) {
            $this->SetData('Group', $model->GetID($this->GroupID));

            $rows = $model->GetApplicants($this->GroupID, ['UserID' => $this->UserID]);
            $this->SetData('Application', array_pop($rows));
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