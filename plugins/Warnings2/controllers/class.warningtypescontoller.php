<?php

/**
 * Class WarningsController
 */
class WarningTypesController extends PluginController {
    /**
     * Instantiate objects
     */
    public function __construct() {
        parent::__construct();
    }

    public function add() {
        $this->edit(false);
    }

    public function edit($warningID) {
        // Prevent non-admins from accessing this page
        $this->permission('Garden.Settings.Manage');

        $warningTypeModel = new WarningTypeModel();
        $this->Form = new Gdn_Form();
        $warningType = false;

        if ($warningID && ctype_digit($warningID)) {
            $warningTypeModel = new WarningTypeModel();
            $warningType = $warningTypeModel->getID($warningID, DATASET_TYPE_ARRAY);

            $this->Form->addHidden('WarningTypeID', val('WarningTypeID', $warningType));
            $this->setData('WarningType', $warningType);
        }

        $this->Form->setModel($warningTypeModel, $warningType);

        // If we are not seeing the form for the first time
        if ($this->Form->authenticatedPostBack() !== false) {
            if ($this->Form->save() !== false) {
                $this->informMessage(t('Your changes have been saved.'));
                $this->RedirectUrl = url('settings/warnings');
            }
        }

        $this->render();
    }

    public function delete($warningID, $action) {
        // Prevent non-admins from accessing this page
        $this->permission('Garden.Settings.Manage');

        if (!$warningID && ctype_digit($warningID)) {
            return;
        }

        $this->setData('WarningID', $warningID);

        if ($action) {
            if ($action === 'delete') {
                $warningTypeModel = new WarningTypeModel();
                $warningTypeModel->deleteID($warningID);
            }

            redirect('settings/warnings');
        } else {
            $this->Form = new Gdn_Form();
            $this->render();
        }
    }
}
