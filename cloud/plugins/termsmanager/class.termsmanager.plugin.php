<?php
/**
 * Terms Manager Plugin.
 *
 * @author Patrick Kelly <patrick.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 * @package TermsManager
 */

use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\FormatService;

/**
 * This plugin allows Admins to create a Terms of Service (or Code of Conduct, or Privacy Policy) that
 * users are required to agree to before connecting over SSO or creating an account.
 */
class TermsManagerPlugin extends Gdn_Plugin {

    private $formatService;

    public function __construct() {
        $this->formatService = Gdn::getContainer()->get(FormatService::class);
    }

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
     * Add a small CSS file to style the display of the Terms.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     *
     */
    public function base_render_before($sender, $args) {
        $sender->addCssFile('/plugins/termsmanager/design/termsmanager.css');
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

        $form->setData($this->getActiveTerms());
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
        $terms = $this->getActiveTerms();
        // If disabled abort.
        if (!$terms) {
            return;
        }
        if ($this->addTermsValidation($sender, false)) {
            $sender->UserModel->Validation->applyRule('Terms', 'Required', t('You must agree to the terms of service.'));
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
        if ($this->addTermsValidation($sender)) {
            $sender->Form->validateRule('Terms', 'ValidateRequired', t('You must agree to the terms of service.'));
        }
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
        $terms = $this->getActiveTerms();

        // If disabled or not configured abort.
        if (!$terms) {
            return;
        }

        // Check if the user already exists
        $userModel = Gdn::userModel();
        $auth = $userModel->getAuthentication($sender->Form->getFormValue('UniqueID'), $sender->Form->getFormValue('Provider'));
        $connectedUserID = $auth['UserID'] ?? false;
        $existingUser = false;
        $user = [];
        if ($connectedUserID) {
            $user = $userModel->getID($connectedUserID, 'array');
        } else {
            $user = (array) $userModel->getByEmail($sender->Form->getFormValue('Email'));
        }

        $existingName = $user['Name'] ?? '';
        $connectName = '';
        $sender->setData('HidePassword', true);
        if (!$sender->Form->getFormValue('Terms')) {

            if (!val('Name', $user) && !$sender->Form->getFormValue('Name')) {
                // If no name is being passed over SSO and this user does not already exist, pass data to
                // the connect view to display a "ConnectName" form field.
                $connectName = $sender->Form->getFormValue('ConnectName');
                $sender->setData('HideName', false);
                $sender->setData('HidePassword', true);

            } else {
                // If a name has been passed over SSO or this user already exists set the conditions in the
                // in the connect view to not show the strings telling the user he already exists etc.
                $sender->setData('ExistingUsers', [$user]);
                $sender->setData('HideName', true);
                $sender->Form->setFormValue('Name', $existingName);
                $sender->Form->addHidden('UserSelect', $user['UserID']);

                // Because we are interrupting the connect process, the Password Field will be presented.
                // If the forum is configured for AutoConnect or if the user has already connected
                // over SSO, do not show the Password Field on the connect form.
                if (!$auth || ($auth && !c('Garden.Registration.AutoConnect'))) {
                    $sender->setData('HidePassword', true);
                }
                Gdn::locale()->setTranslation('ConnectAccountExists', ' ');
                Gdn::locale()->setTranslation('ConnectRegisteredName', ' ');
            }

            $sender->Form->setFormValue('ConnectName', $connectName);

            if (!$sender->Form->validationResults() && $sender->Form->getFormValue('Terms') && $user['UserID']) {
                // If a the user exists, and they have submitted the terms form, update the User table.
                Gdn::userModel()->save(['UserID' => val('UserID', $user), 'Terms' => $sender->Form->getFormValue('Terms')]);
                return;
            }
        }

        $sender->Form->addHidden('LatestTerms', val('Terms', $user));
        if ($this->addTermsValidation($sender, true)) {
            $sender->Form->validateRule('Terms', 'ValidateRequired', t('You must agree to the terms of service.'));
        }
    }

    /**
     * Check if a user has agreed to custom terms and validate accordingly.
     *
     * @param $sender Sender object passed where ever it is called.
     * @param bool $sso
     */
    private function addTermsValidation($sender, $sso = false) {
        $terms = $this->getActiveTerms();

        // If disabled or not configured abort.
        if (!$terms) {
            return false;
        }

        // Find out if it is an existing user.
        $email = $sender->Form->getFormValue('Email');
        $user = Gdn::userModel()->getByEmail($email);
        if (!$user) {
            $user = Gdn::userModel()->getByUsername($email);
        }

        // If it is an existing user and Admin who has agreed to most recent terms and Admin is not
        // forcing to users to re-opt-in, don't validate checking the terms.
        $isUpToDate = (val('Terms', $user) === val('TermsOfUseID', $terms));
        $forceRenew = val('ForceRenew', $terms);
        if ($isUpToDate) {
            return false;
        }

        if (!$isUpToDate && !$forceRenew && val('UserID', $user)) {
            return false;
        }

        // "Manually" flag SSO connections because the form is not being posted back.
        if ($sender->Form->isPostBack() || $sso) {
            return true;
        }
    }


    /**
     * Insert the link to read the custom terms and the checkbox to agree.
     *
     * @param $sender
     * @param string $wrapTag
     */
    private function addTermsCheckBox($sender, $wrapTag = 'li') {
        $terms = $this->getActiveTerms();

        // If disabled or not configured abort.
        if (!$terms) {
            return;
        }

        // Find out if this is an existing user connecting.
        $email = $sender->Form->getFormValue('Email');
        $user = Gdn::userModel()->getByEmail($email);
        if (!$user) {
            $user = Gdn::userModel()->getByUsername($email);
        }

        // If it is an existing user and Admin who has agreed to most recent terms and Admin is not
        // forcing to users to re-opt-in, don't validate checking the terms.
        $isUpToDate = (val('Terms', $user) === val('TermsOfUseID', $terms));
        $forceRenew = val('ForceRenew', $terms);

        if ($isUpToDate) {
            return;
        }

        // is and existing user whose Terms hasn't been checked but ForceRenew is off.
        if (!$isUpToDate && !$forceRenew && val('UserID', $user)) {
            return;
        }

        // If it is an existing user and Admin who has agreed to most recent terms and Admin is not
        // forcing to users to re-opt-in, don't present a checkbox and the terms.
        if (val('Terms', $user) === val('TermsOfUseID', $terms) && val('ForceRenew', $terms)) {
            return;
        }

        $messageClass = 'InfoMessage';
        if ($sender->Form->isPostBack() && !$sender->Form->getValue('Terms')) {
            $messageClass = 'AlertMessage';
        }

        $validationMessage = (val('Terms', $user) && val('ForceRenew', $terms)) ? t('<h2>We have recently updated our Terms of Use. You must agree to the code of conduct.</h2>') : '';
        if ($terms->ShowInPopup || $terms->Link) {
            $link = $terms->Link ? $terms->Link : '/vanilla/terms';
            $linkAttribute = ($link === '/vanilla/terms') ? ['class' =>'Popup'] : ['target' => '_blank'];
            $anchor = $terms->ShowInPopup || $link ? anchor('Click here to read.', $link, $linkAttribute) : '';
            $message = t('You must read and understand the provisions of the forums code of conduct before participating in the forums.');
            echo wrap('<div class="DismissMessage '.$messageClass.'">'.$validationMessage.$message.' '.$anchor.'</div>', $wrapTag, ['class' => 'managed-terms-message']);
        } else {
            $termsBody = t('Terms of service body text.', val('Body', $terms));
            $body = $this->formatService->renderHTML($termsBody, HtmlFormat::FORMAT_KEY);
            echo wrap('<label class="inline-terms-label">'.t('Terms of Service').'</label><div class="inline-terms-body">'.$body.'</div>', $wrapTag, ['class' => 'managed-terms-row']);
        }
        echo wrap($sender->Form->checkBox('Terms', t('TermsLabel', 'By checking this box, I acknowledge I have read and understand, and agree to the forums code of conduct.'), ['value' => val('TermsOfUseID', $terms)]), $wrapTag, ['class' => 'managed-terms-checkbox-row']);
    }


    /**
     * Create a page for displaying the custom terms in a modal popup.
     *
     * @param VanillaController $sender
     * @param array $args
     */
    public function vanillaController_terms_create($sender, $args) {
        $terms = $this->getActiveTerms();
        if ($terms) {
            $termsBody = t('Terms of service body text.', val('Body', $terms));
        } else {
            $termsBody = t('Terms on disabled or not configured');
        }
        $body = $this->formatService->renderHTML($termsBody, HtmlFormat::FORMAT_KEY);
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
        $latestTerms = $this->getActiveTerms();
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
     * @return object|bool An Object of most recent row in the Terms of Use table or FALSE.
     */
    private function getActiveTerms() {
         $terms = Gdn::sql()
            ->get('TermsOfUse', 'TermsOfUseID', 'DESC', 1)
            ->firstRow();

        if (!$terms) {
            return false;
        }

        if (!$terms->Active || (!$terms->Body && !$terms->Link)) {
            return false;
        }
         return $terms;
    }
}
