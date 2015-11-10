<?php
/**
 * Reconfirm
 * @author Patrick Kelly <patrick.k@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

$PluginInfo['Reconfirm'] = array(
    'Name' => 'Reconfirm',
    'Description' => 'Force users to reset their password and reconfirm the terms of use.',
    'Version' => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'RequiredTheme' => false,
    'HasLocale' => true,
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true,
    'Author' => "Patrick Kelly",
    'AuthorEmail' => 'patrick.k@vanillaforums.com'
);

/**
 * Class ReconfirmPlugin
 */
class ReconfirmPlugin extends Gdn_Plugin {

    /**
     * Setup
     *
     */
    public function setUp() {
        $this->structure();
    }

    /**
     * Create a row in GDN_User to flag users who have reconfirmed terms of use, default it to 0.
     *
     * @throws Exception
     */
    public function structure() {

        gdn::structure()->table('User')
            ->column('ConfirmedTerms', 'tinyint', 0, array('index'))
            ->set();
        return;
    }

    /**
     * Redirect users who have not reconfirmed to the confirm page.
     *
     * @param object $sender
     * @param object $args
     * @return null;
     */
    public function gdn_dispatcher_beforeControllerMethod_handler($sender, $args) {
        // Any unauthenticated user can browse the forum.
        if(!gdn::session()->isValid()) {
            return;
        }

        $userConfirmed = val("ConfirmedTerms", gdn::session()->User, 0);
        // Any authenticated user who has confirmed can browser the forum.
        if($userConfirmed) {
            return;
        }

        // Anyone, even unconfirmed users can visit these pages.
        $urlWhiteList = array(
            "/entry/confirm",
            "/entry/signout",
            "/entry/signin",
            "/dashboard",
            "/home/termsofservice"
        );

        foreach ($urlWhiteList as $url) {
            if (stripos(gdn::request()->url(), $url) !== FALSE) {
                return;
            }
        }

        // All authenticated users who have not confirmed are redirected to the confirm page.
        redirect('/entry/confirm');
    }

    /**
     * Create a form to capture the user's new password, validate that it doesn't match the existing password.
     * @param $sender
     * @param $args
     */
    public function entryController_confirm_create($sender, $args) {
        $sender->addJsFile('password.js');
        $sender->Form->addHidden("UserID", gdn::session()->UserID);
        $sender->Form->addHidden("ConfirmedTerms", 1);
        if ($sender->Form->authenticatedPostBack()) {
            $sender->UserModel->Validation->applyRule('TermsOfService', 'Required', t('You must agree to the terms of service.'));
            $sender->UserModel->Validation->applyRule('Password', 'Required');
            $sender->UserModel->Validation->applyRule('Password', 'Strength');
            $sender->UserModel->Validation->applyRule('Password', 'Match');
            // Add the custom validation function validateNewPassword found below.
            $sender->UserModel->Validation->addRule("NewPassword", "function:validateNewPassword");
            $sender->UserModel->Validation->applyRule('Password', 'NewPassword', "Your password has to be different from the password you already have.");

            $sender->UserModel->Validation->validate(gdn::request()->post());
            $errors = $sender->UserModel->Validation->resultsText();
            if ($errors) {
                $sender->Form->addError('Please try again.');
                $sender->Form->addError($errors);
            }

            // Check the user.
            if ($sender->Form->errorCount() == 0) {
                $sender->UserModel->save(gdn::request()->post());
                redirect('/');
            }
        }
        $sender->render("confirm", "", "plugins/Reconfirm");
    }
}

/**
 * Validate that the new password is different from the existing password.
 *
 * @param $submittedPassword 'New' password supplied by user.
 * @return bool
 * @throws Gdn_UserException
 */
function validateNewPassword ($submittedPassword=null) {
    $user = gdn::userModel()->getByEmail(val('Email', gdn::session()->User));
    $existingPassword = val("Password", $user);
    $passwordHash = new Gdn_PasswordHash();
    $passwordMatch = $passwordHash->checkPassword($submittedPassword, $existingPassword, val('HashMethod', $user), val('Name', $user));
    // Since this is to validate that we are changing the password, if it matches return false.
    return ($passwordMatch === true) ? false : true;
}
