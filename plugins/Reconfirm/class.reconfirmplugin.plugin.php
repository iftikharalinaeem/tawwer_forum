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
        $columnExists = gdn::structure()->table('User')->columnExists('ConfirmedTerms');

        gdn::structure()->table('User')
            ->column('ConfirmedTerms', 'int', '0', array('index'))
            ->set();

        // If ConfirmedTerms already existed, don't update it.
        if($columnExists === false) {
            gdn::sql()
                ->update('User u')
                ->set('u.ConfirmedTerms', 0, false, false)
                ->put();
        }
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

        $user = gdn::session()->User;
        $userConfirmed = val("ConfirmedTerms", $user, 0);
        // Any authenticated user who has confirmed can browser the forum.
        if($userConfirmed) {
            return;
        }

        $selfUrl = gdn::request()->url();
        // Anyone, even unconfirmed users can sign in, sign out or confirm.
        if(stristr($selfUrl, "/entry/confirm") || stristr($selfUrl, "/entry/signout") || stristr($selfUrl, "/entry/signin") || stristr($selfUrl, "/dashboard") || stristr($selfUrl, "/home/termsofservice")) {
            return;
        }

        // All authenticated users who have not confirmed are redirected to the confirm page.
        redirect(url('/entry/confirm'));
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
        if ($sender->Form->authenticatedPostBack() === true) {
            $sender->UserModel->Validation->applyRule('TermsOfService', 'Required', t('You must agree to the terms of service.'));
            $sender->UserModel->Validation->applyRule('Password', 'Required');
            $sender->UserModel->Validation->applyRule('Password', 'Strength');
            $sender->UserModel->Validation->applyRule('Password', 'Match');
            // Add the custom validation function validateNewPassword found below.
            $sender->UserModel->Validation->addRule("NewPassword", "function:validateNewPassword");
            $sender->UserModel->Validation->applyRule('Password', 'NewPassword', "Your password has to be different from the password you already have.");

            $sender->UserModel->Validation->validate(gdn::request()->post());
            $errors = $sender->UserModel->Validation->resultsText();
            if ($errors  && !c('Garden.Embed.Allow')) {
                $sender->Form->addError('Please try again.');
                $sender->Form->addError($errors);
            }

            // Check the user.
            if ($sender->Form->errorCount() == 0) {
                $rowID = $sender->UserModel->save(gdn::request()->post());
                redirect(gdn::request()->domain());
            }
        }
        $sender->render("confirm", "", "plugins/Reconfirm");
    }
}

/**
 * Validate that the new password is different from the existing password.
 *
 * @param $value
 * @return bool
 * @throws Gdn_UserException
 */
function validateNewPassword ($value) {
    $sessionUser = gdn::session()->User;
    $user = gdn::userModel()->getByEmail(val('Email', $sessionUser));
    $submittedPassword = $value;
    $existingPassword = val("Password", $user);
    $passwordHash = new Gdn_PasswordHash();
    $passwordMatch = $passwordHash->checkPassword($submittedPassword, $existingPassword, val('HashMethod', $user), val('Name', $user));
    return ($passwordMatch === true) ? false : true; // Since this is to validate that we are changing the password, if it matches return false.
}
