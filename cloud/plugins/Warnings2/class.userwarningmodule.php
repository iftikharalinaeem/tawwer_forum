<?php if (!defined('APPLICATION')) exit();

class UserWarningModule extends Gdn_Module {

    public $UserID;

    public function __construct($sender = '', $applicationFolder = false) {
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
        $userAlertModel = new UserAlertModel();
        $alert = $userAlertModel->getID($this->UserID);
        $this->Data = $alert;
        if (!$this->data('WarningLevel')) {
            return '';
        }

        $user = Gdn::userModel()->getID($this->UserID);
        $this->setData('Punished', val('Punished', $user));
        $this->setData('Banned', val('Banned', $user));
        $this->setData('Name', val('Name', $user));

        return parent::toString();
    }
}
