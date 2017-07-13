<?php if (!defined('APPLICATION')) exit();

class UserWarningModule extends Gdn_Module {

    public $UserID;

    public function __construct($Sender = '', $ApplicationFolder = false) {
        $this->_ApplicationFolder = 'plugins/Warnings2';
    }

    /**
     * Returns the component as a string to be rendered to the screen.
     *
     * @return string|empty string
     */
    public function toString() {
        if (!$this->UserID) {
            $this->UserID = Gdn::controller()->data('Profile.UserID');
        }

        if ($this->UserID != Gdn::session()->UserID && !Gdn::session()->checkPermission(['Garden.PersonalInfo.View', 'Moderation.Warnings.View'], false)) {
            return '';
        }

        // Grab the data.
        $UserAlertModel = new UserAlertModel();
        $Alert = $UserAlertModel->getID($this->UserID);
        $this->Data = $Alert;
        if (!$this->data('WarningLevel')) {
            return '';
        }

        $User = Gdn::userModel()->getID($this->UserID);
        $this->setData('Punished', val('Punished', $User));
        $this->setData('Banned', val('Banned', $User));
        $this->setData('Name', val('Name', $User));

        return parent::toString();
    }
}
