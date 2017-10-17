<?php
/**
 * Terms Manager Plugin.
 *
 * @author Patrick Kelly <patrick.k@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 * @package TermsManager
 */

/**
 * This plugin allows Admins to create a Terms of Service (or Code of Conduct, or Privacy Policy) that
 * users are required to agree to before connecting over SSO or creating an account.
 */
class TermsManagerPlugin extends Gdn_Plugin {

    /**
     * Execute the structure() whenever the plugin is turned on.
     */
    public function setup() {
        $this->structure();
    }


    /**
     * Create a table to store custom terms data.
     *
     * @throws Exception
     */
    public function structure() {
        // Add the Terms column to Users to record which version of the Terms were agreed to.
        Gdn::structure()->table('User')->column('Terms', 'int', true)->set();

        // Create the TermsOfUse Table.
        Gdn::structure()->table('TermsOfUse')
            ->primaryKey('TermsOfUseID')
            ->column('Body', 'text', false)
            ->column('Link', 'text', false)
            ->column('ShowInPopup', 'tinyint', 1)
            ->column('Active', 'tinyint', 0)
            ->column('ForceRenew', 'tinyint', 0)
            ->column('DateInserted', 'datetime', true)
            ->set();
    }


    /**
     * Settings form in dashboard to save the custom terms, activate and disactivate.
     *
     * @param SettingsController $sender
     * @param array $args
     * @throws Gdn_UserException
     */
    public function settingsController_termsManager_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');

        $form = new Gdn_Form();
        $sender->Form = $form;

        if ($form->authenticatedPostBack()) {
            $form->validateRule('Link', 'function:ValidateWebAddress', t('Please include a valid URL as a link, or leave it blank to use the supplied text.'));
            if (!$form->getValue('Body') && !$form->getValue('Link')) {
                $form->validateRule('Link', 'function:ValidateRequired', t('You must include either an external link to a Terms of Use document or include your Terms of Use in the form below.'));
            }
            if ($this->save($sender->Form)) {
                $sender->informMessage(t('Saved'));
            }
        }

        $form->setData($this->getTerms());
        // Set up the form.
        $formFields['Link'] = ['LabelCode' => t('Link to Terms of Use'), 'Description' => t('External link to a \'Terms\' document.'), 'Control' => 'TextBox'];
        $formFields['Body'] = ['LabelCode' => t('Terms of Use Text'), 'Description' => 'If you have a link to internal document in \'Link to Terms of User\' above, \'Terms of Use Text\' will be ignored. Remove the link if you want to use this text.', 'Control' => 'TextBox', 'Options' => ['MultiLine' => true, 'rows' => 10, 'columns' => 100]];
        $formFields['Active'] = ['LabelCode' => t('Enable Terms of Use'), 'Control' => 'CheckBox'];
        $formFields['ForceRenew'] = ['LabelCode' => t('Force Review'), 'Description' => t('If you have updated your \'Terms\' you can force users to agree to the new terms when logging in.'), 'Control' => 'CheckBox'];
        $formFields['ShowInPopup'] = ['LabelCode' => t('Show in Popup'), 'Description' => t('Display the terms in a popup.'), 'Control' => 'CheckBox'];
        $sender->setData('_Form', $formFields);
        $sender->setData('Title', sprintf(t('%s Settings'), t('Terms of Use Management')));
        $sender->render('settings', '', 'plugins/termsmanager');
    }


    /**
     * Insert checkbox and messaging into entry/connect view to agree to the custom terms on sign in.
     *
     * @param EntryController $sender
     * @param array $args
     */
    public function entryController_afterPassword_handler($sender, $args) {
        $validationResults = $sender->Form->validationResults();

        // This only shows the custom terms after the form has been submitted because you don't know
        // until then if the user has previously agreed to the custom terms on an earlier login.
        if ($sender->Form->isPostBack() && val('Terms', $validationResults)) {
            $this->addTermsCheckBox($sender, 'div');
        }
    }


    /**
     * Validate custom terms and optionally remove validation for default terms when registering.
     *
     * @param EntryController $sender
     * @param array $args
     */
    public function entryController_registerValidation_handler($sender, $args) {
        $terms = $this->getTerms();
        // If disabled abort.
        if (!val('Active', $terms)) {
            return;
        }
        $this->addTermsValidation($sender, false);
        // If the custom terms are active and the client has not specified showing both
        // custom and default, do not validate default because it has been hidden in $this->addTermsCheckBox()
        if (!c('TermsManager.ShowDefault')) {
            $sender->UserModel->Validation->unapplyRule('TermsOfService', true);
        }
    }


    /**
     * Inject the custom terms in the connect page when connecting over SSO
     *
     * @param EntryController $sender
     * @param array $args
     */
    public function entryController_registerBeforePassword_handler($sender, $args) {
        // The register page has two events, use the 'RegisterFormBeforeTerms', use this for the connect page in SSO
        if ($sender->Request->path() === 'entry/register') {
            return;
        }

        // Set these values so that Connect view will show the checkbox.
        $sender->setData('AllowConnect', true);
        $sender->setData('NoConnectName', false);
        $sender->Form->setFormValue('Connect', true);
        if ($sender->Form->isPostBack()) {
            $sender->Form->setFormValue('ConnectName', true);
        }
        $this->addTermsCheckBox($sender);
    }


    /**
     * Inject the custom terms checkbox at the bottom of the Register form.
     *
     * @param EntryController $sender
     * @param array $args
     */
    public function entryController_registerFormBeforeTerms_handler($sender, $args) {
        $this->addTermsCheckBox($sender);
    }


    /**
     * Add validation for custom terms on Sign In and update the user's record if they agree.
     *
     * @param EntryController $sender
     * @param array $args
     */
    public function entryController_signIn_handler($sender, $args) {
        $this->addTermsValidation($sender);
        if ($sender->Form->getFormValue('Terms')) {
            $email = $sender->Form->getFormValue('Email');
            $user = Gdn::userModel()->getByEmail($email);
            if (!$user) {
                $user = Gdn::userModel()->getByUsername($email);
            }
            Gdn::userModel()->save(['UserID' => val('UserID', $user), 'Terms' => $sender->Form->getFormValue('Terms')]);
        }
    }


    /**
     * Add validation for the custom terms after connecting over SSO.
     * If the admin wants to force users to agree to custom terms on registration or when signing back in
     * we still need to check if there is a connect name or provide a way for users to create them.
     *
     * @param EntryController $sender
     * @param array $args
     */
    public function entryController_afterConnectData_handler($sender, $args) {
        // Check if the user already exists
        $userModel = Gdn::userModel();
        $auth = $userModel->getAuthentication($sender->Form->getFormValue('UniqueID'), $sender->Form->getFormValue('Provider'));
        $userID = val('UserID', $auth);
        $user = $userModel->getID($userID, 'array');

        if (!$sender->Form->getFormValue('Terms')) {

            if (!val('Name', $user) && !$sender->Form->getFormValue('Name')) {
                // If no name is being passed over SSO and this user does not already exist, pass data to
                // the connect view to display a "ConnectName" form field.
                $sender->setData('NoConnectName', false);
                $sender->setData('AllowConnect', true);
                $connectName = $sender->Form->getFormValue('ConnectName');
            } else {
                // If a name has been passed over SSO or this user already exists set the conditions in the
                // in the connect view to not show the strings telling the user he already exists etc.
                $sender->setData('NoConnectName', true);
                $sender->setData('AllowConnect', true);
                $sender->setData('ExistingUsers', [$user]);
                Gdn::locale()->setTranslation('ConnectAccountExists', ' ');
                Gdn::locale()->setTranslation('ConnectRegisteredName', ' ');
            }

            $sender->Form->setFormValue('ConnectName', $connectName);
        }

        // If there is no name, exiting user or connectname created, show the ConnectName form field in connect view.
        if (!$sender->Form->getFormValue('Name') && !$sender->Form->getFormValue('ConnectName') && !val('Name', $user)) {
            $sender->setData('AllowConnect', false);
            $sender->setData('NoConnectName', false);
            $sender->Form->setFormValue('ConnectName');
        }

        $this->addTermsValidation($sender, true);
    }

    /**
     * Check if a user has agreed to custom terms and validate accordingly.
     *
     * @param $sender Sender object passed where ever it is called.
     * @param bool $sso
     */
    private function addTermsValidation($sender, $sso = false) {
        $terms = $this->getTerms();

        // If disabled abort.
        if (!val('Active', $terms)) {
            return;
        }

        // If Admin wants users to opt in again when the custom terms have changed.
        if (val('ForceRenew', $terms)) {
            $email = $sender->Form->getFormValue('Email');
            $user = Gdn::userModel()->getByEmail($email);
            if (!$user) {
                $user = Gdn::userModel()->getByUsername($email);
            }

            // If the user has already opted-in
            if (val('Terms', $user) === val('TermsOfUseID', $terms)) {
                return;
            }
        }

        // "Manually" flag SSO connections because the form is not being posted back.
        if ($sender->Form->isPostBack() || $sso) {
            $sender->Form->validateRule('Terms', 'ValidateRequired', t('You must agree to the terms of service.'));
        }
    }


    /**
     * Insert the link to read the custom terms and the checkbox to agree.
     *
     * @param $sender
     * @param string $wrapTag
     */
    private function addTermsCheckBox($sender, $wrapTag = 'li') {
        $terms = $this->getTerms();

        // If disabled abort.
        if (!val('Active', $terms)) {
            return;
        }

        $sender->Head->addCss('/plugins/termsmanager/design/termsmanager.css');
        // If client has not specified that they would like to show both default and custom terms, hide custom terms with CSS
        if (!c('TermsManager.ShowDefault')) {
            $sender->Head->addString('<style type="text/css">.DefaultTermsLabel {display: none !important}</style>');
        }

        // If Admin wants users to opt in again, if the Terms have changed.
        if (val('ForceRenew', $terms)) {
            $email = $sender->Form->getFormValue('Email');
            $user = Gdn::userModel()->getByEmail($email);
            if (!$user) {
                $user = Gdn::userModel()->getByUsername($email);
            }

            if (val('Terms', $user) === val('TermsOfUseID', $terms)) {
                return;
            }
        }

        $messageClass = 'InfoMessage';
        if ($sender->Form->isPostBack() && !$sender->Form->getValue('Terms')) {
            $messageClass = 'AlertMessage';
        }

        $validationMessage = (val('Terms', $user) && val('ForceRenew', $terms)) ? t('<h2>We have recently updated our Terms of Use. You must agree to the code of conduct.</h2>') : '';
        $link = val('Link', $terms) ? val('Link', $terms) : '/vanilla/terms';

        $linkAttribute = ($link === '/vanilla/terms') ? ['class' =>'Popup'] : ['target' => '_blank'];
        $anchor = (val('ShowInPopup', $terms)) ? anchor('Click here to read.', $link, $linkAttribute) : '';
        $message = t('You must read and understand the provisions of the forums code of conduct before participating in the forums.');
        echo wrap('<div class="DismissMessage '.$messageClass.'">'.$validationMessage.$message.' '.$anchor.'</div>', $wrapTag);

        if (!val('ShowInPopup', $terms)) {
            $body = Gdn_Format::text(val('Body', $terms));
            echo wrap('<label class="inline-terms-label">'.t('Terms of Service').'</label><div class="inline-terms-body">'.$body.'</div>', $wrapTag);
        }
        echo wrap($sender->Form->checkBox('Terms', t('TermsLabel', 'By checking this box, I acknowledge I have read and understand, and agree to the forums code of conduct.'), ['value' => val('TermsOfUseID', $terms)]), $wrapTag);
    }


    /**
     * Create a page for displaying the custom terms in a modal popup.
     *
     * @param VanillaController $sender
     * @param array $args
     */
    public function vanillaController_terms_create($sender, $args) {
        $terms = $this->getTerms();
        $termsBody = t('Terms of service body text.', val('Body', $terms));
        $body = Gdn_Format::text($termsBody);
        $sender->setData('Body', $body);
        $sender->render('terms', '', 'plugins/termsmanager');
    }


    /**
     * Save the custom terms to the db, do not increment unless either the link or the body has changed.
     *
     * @param Gdn_Form $form
     * @return bool
     */
    private function save($form) {
        $latestTerms = $this->getTerms();
        $formValues = $form->formValues();
        $updateFields = [
            'Body' => $form->getValue('Body'),
            'Link' => $form->getValue('Link'),
            'ForceRenew' => (int)$form->getValue('ForceRenew'),
            'ShowInPopup' => (int)$form->getFormValue('ShowInPopup'),
            'Active' => (int)$form->getValue('Active'),
            'DateInserted' => date('Y-m-j H:i:s')
        ];

        if (val('Body', $latestTerms) === val('Body', $formValues) && val('Link', $latestTerms) === val('Link', $formValues)) {
            return Gdn::sql()
                ->update('TermsOfUse')
                ->set($updateFields)
                ->where('TermsOfUseID', val('TermsOfUseID', $latestTerms))
                ->put();
        } else {
            return Gdn::sql()->insert('TermsOfUse', $updateFields);
        }
    }


    /**
     * Get the most recent custom terms from the db.
     *
     * @return array
     */
    private function getTerms() {
        return Gdn::sql()
            ->get('TermsOfUse', 'TermsOfUseID', 'DESC', 1)
            ->firstRow();
    }
}
