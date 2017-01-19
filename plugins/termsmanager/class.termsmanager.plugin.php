<?php
/**
 * Terms Manager Plugin.
 *
 * @author Patrick Kelly <patrick.k@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 * @package TermsManager
 */

// Define the plugin:
$PluginInfo['TermsManager'] = [
    'Name' => 'Terms of Use Manager',
    'Description' => 'Stop user from creating accounts until they have agreed to your terms. Record which terms they have agreed to.',
    'Version' => '1.0',
    'MobileFriendly' => true,
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'SettingsUrl' => '/settings/termsmanager',
    'Author' => 'Patrick Kelly',
    'AuthorEmail' => 'patrick.k@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
];

/**
 * This plugin allows Admins to create a Terms of Service (or Code of Conduct, or Privacy Policy) that
 * users are required to agree to before connecting over SSO or creating an account.
 */
class TermsManagerPlugin extends Gdn_Plugin {

    public $databasePrefix;
    public $tablePrefix;

    public function __construct() {
        $this->databasePrefix = Gdn::database()->DatabasePrefix;
        $importModel = new ImportModel();
        $this->tableName = $importModel::TABLE_PREFIX.'TermsOfUse';
    }

    public function setup() {
        $this->structure();
    }

    public function structure() {
        // Add the Terms column to Users to record which version of the Terms were agreed to.
        Gdn::structure()->table('User')->column('Terms', 'int(11)', true)->set();

        // Create the TermsOfUse Table.
        gdn::structure()->table($this->tableName)
            ->primaryKey('TermsOfUseID')
            ->column('Body', 'text', false)
            ->column('Link', 'text', false)
            ->column('Active', 'tinyint(4)', 0)
            ->column('ForceRenew', 'tinyint(4)', 0)
            ->column('DateInserted', 'datetime', true)
            ->set();
    }

    public function settingsController_termsManager_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');

        /* @var Gdn_Form $form */
        $form = new Gdn_Form();
        $sender->Form = $form;

        if ($form->AuthenticatedPostBack()) {
            $form->validateRule('Link', 'function:ValidateWebAddress', 'Please include a valid URL as a link, or leave it blank to use the supplied text.');
            if (!$form->getValue('Body') && !$form->getValue('Link')) {
                $form->validateRule('Link', 'function:ValidateRequired', 'You must include either an external link to a Terms of Use document or include your Terms of Use in the form below.');
            }
            if ($this->save($sender->Form)) {
                $sender->informMessage(t('Saved'));
            }
        }

        $form->setData($this->getTerms());
        // Set up the form.
        $formFields['Link'] = ['LabelCode' => 'Link to Terms of Use', 'Description' => 'External link to a \'Terms\' document.', 'Control' => 'TextBox'];
        $formFields['Body'] = ['LabelCode' => 'Terms of Use Text', 'Description' => 'If you have a link to internal document in \'Link to Terms of User\' above, this will be ignored. Remove the link if you want to use this text.', 'Control' => 'TextBox', 'Options' => ['MultiLine' => true, 'rows' => 10, 'columns' => 100]];
        $formFields['Active'] = ['LabelCode' => 'Enable Terms of Use', 'Control' => 'CheckBox'];
        $formFields['ForceRenew'] = ['LabelCode' => 'Force Review', 'Description' => 'If you have updated your \'Terms\' you can force users to agree to the new terms when logging in.', 'Control' => 'CheckBox'];
        $sender->setData('_Form', $formFields);
        $sender->setData('Title', sprintf(t('%s Settings'), 'Terms of Use Management'));
        $sender->render('settings', '', 'plugins/termsmanager');
    }



    /**
     * Insert checkbox and messaging into entry/connect view to agree to the Terms and Conditions on sign up.
     *
     * @param EntryController $sender
     * @param array $args
     */
    public function entryController_afterPassword_handler($sender, $args) {
        $validationResults = $sender->Form->validationResults();
        if ($sender->Form->isPostBack() && val('Terms', $validationResults)) {
            $this->addTermsCheckBox($sender, 'div');
        }
    }

    public function entryController_registerBeforePassword_handler($sender, $args) {
        // The register page has two events, use the 'RegisterFormBeforeTerms', use this for the connect page in SSO
        if ($sender->Request->path() === 'entry/register') {
            return;
        }

        $sender->setData("AllowConnect", true);
        $sender->setData('NoConnectName', false);
        $sender->Form->setFormValue('Connect', true);
        if ($sender->Form->isPostBack()) {
            $sender->Form->setFormValue('ConnectName', true);
        }
        $this->addTermsCheckBox($sender);
    }

    public function entryController_registerFormBeforeTerms_handler($sender, $args) {
        $this->addTermsCheckBox($sender);
    }

    /**
     * Add validation for the Terms and Conditions opt-in on sign up.
     *
     * @param Gdn_Controller $sender
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
     * Add validation for the Terms when connecting over SSO.
     *
     * @param $sender
     * @param $args
     */
    public function entryController_AfterConnectData_handler($sender, $args) {
        $sender->setData("AllowConnect", true);
        $sender->setData('NoConnectName', false);
        $sender->Form->setFormValue('Connect', true);
        if (!$sender->Form->isPostBack()) {
            $sender->Form->setFormValue('ConnectName', true);
        }
        $this->addTermsValidation($sender, true);
    }


    /**
     * Check if a user has agreed to the terms and validate accordingly.
     *
     * @param $sender
     * @param bool|false $sso
     */
    private function addTermsValidation($sender, $sso = false) {
        $terms = $this->getTerms();

        if (!val('Active', $terms)) {
            return;
        }

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

        if ($sender->Form->isPostBack() || $sso) {
            $sender->Form->validateRule('Terms', 'ValidateRequired', t('You must agree to the code of conduct'));
        }
    }


    /**
     * Insert the link to read the Terms and the checkbox to agree to terms.
     *
     * @param $sender
     * @param string $wrapTag
     */
    private function addTermsCheckBox($sender, $wrapTag = 'li') {
        $terms = $this->getTerms();
        if (!val('Active', $terms)) {
            return;
        }

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
        $linkAttribute = ($link === '/vanilla/terms') ? ' class=\'Popup\'' : ' target=\'_blank\'';
        $message = sprintf(t('TermsMessage', 'YOU MUST READ AND UNDERSTAND THE <a href="%1$s" %2$s>PROVISIONS OF THE FORUMS CODE OF CONDUCT</a> BEFORE PARTICIPATING IN THE FORUMS.'), $link, $linkAttribute);
        echo wrap("<div class='DismissMessage {$messageClass}'>".$validationMessage.$message."</div>", $wrapTag);
        echo wrap($sender->Form->CheckBox('Terms', t('TermsLabel', 'BY CHECKING THIS BOX, I ACKNOWLEDGE I HAVE READ AND UNDERSTAND, AND AGREE TO THE FORUMS CODE OF CONDUCT.'), array('value' => val('TermsOfUseID', $terms))), $wrapTag);
    }


    /**
     * Create a page for displaying the Terms in a modal popup.
     *
     * @param VanillaControler $sender
     * @param $args
     */

    public function vanillaController_terms_create($sender, $args) {
        $terms = $this->getTerms();
        $sender->setData('Body', val('Body', $terms));
        $sender->render('terms', '', 'plugins/termsmanager');
    }


    /**
     * Save the Terms to the db, do not increment unless either the link or the body has changed.
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
            'Active' => (int)$form->getValue('Active'),
            'DateInserted' => date('Y-m-j H:i:s')
        ];
        if (val('Body', $latestTerms) === val('Body', $formValues) && val('Link', $latestTerms) === val('Link', $formValues)) {
            Gdn::sql()
                ->update($this->tableName)
                ->set($updateFields)
                ->where('TermsOfUseID', val('TermsOfUseID', $latestTerms))
                ->put();
        } else {
            Gdn::sql()
                ->insert($this->tableName, $updateFields);
        }
        return true;
    }


    /**
     * Get the most recent Terms from the db.
     *
     * @return array
     */
    private function getTerms() {
        $latestTerms = Gdn::sql()
            ->get($this->tableName, 'TermsOfUseID', 'DESC', 1)
            ->resultArray();
        return $latestTerms[0];
    }
}
