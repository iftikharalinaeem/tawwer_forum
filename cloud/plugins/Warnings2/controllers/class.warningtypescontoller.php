<?php

/**
 * Class WarningsController
 */
class WarningTypesController extends PluginController {
    /**
     * Add endpoint.
     */
    public function add() {
        $this->edit(false);
    }

    /**
     * Edit endpoint.
     *
     * @param string $warningTypeID WarningType ID
     */
    public function edit($warningTypeID) {
        // Prevent non-admins from accessing this page
        $this->permission('Garden.Settings.Manage');

        $warningTypeModel = new WarningTypeModel();
        $this->Form = new Gdn_Form();
        $warningType = false;

        if ($warningTypeID && ctype_digit($warningTypeID)) {
            $warningTypeModel = new WarningTypeModel();
            $warningType = $warningTypeModel->getID($warningTypeID, DATASET_TYPE_ARRAY);

            $this->Form->addHidden('WarningTypeID', val('WarningTypeID', $warningType));
            $this->setData('WarningType', $warningType);
        }

        $this->Form->setModel($warningTypeModel, $warningType);

        // If we are not seeing the form for the first time
        if ($this->Form->authenticatedPostBack() !== false) {
            if ($this->Form->save() !== false) {
                $this->informMessage(t('Your changes have been saved.'));
                $this->setRedirectTo('settings/warnings');
            }
        }

        $this->render('addedit');
    }

    /**
     * Delete endpoint.
     *
     * @param string $warningTypeID WarningType ID
     */
    public function delete($warningTypeID) {
        // Prevent non-admins from accessing this page
        $this->permission('Garden.Settings.Manage');

        if (!$warningTypeID && ctype_digit($warningTypeID)) {
            return;
        }

        $this->setData('WarningTypeID', $warningTypeID);

        $this->Form = new Gdn_Form();
        $postback = $this->Form->authenticatedPostBack();
        if ($postback) {
            $warningTypeModel = new WarningTypeModel();
            $warningTypeModel->deleteID($warningTypeID);

            redirectTo('settings/warnings');
        } else {
            $this->Form = new Gdn_Form();
            $this->render();
        }
    }
}