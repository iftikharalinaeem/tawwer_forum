<?php
/**
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
 * The ReconfirmPlugin class forces users to reconfirm the terms of use.
 *
 * Block users from contributing to a forum until they have read the terms of use
 * and updated their password. It is based on the flag ConfirmedTerms in GDN_Users.
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
        if (!gdn::session()->isValid() || gdn::session()->User->Admin == 2) {
            return;
        }

        $userConfirmed = val("ConfirmedTerms", gdn::session()->User, 0);
        // Any authenticated user who has confirmed can browser the forum.
        if ($userConfirmed) {
            return;
        }

        // Override some URLs that will be banned by disallowUrl.
        $allowUrls = array('/entry/confirm', '/profile/notificationspopin', '/messages/popin');
        if ($this->allowUrl($allowUrls)) {
            return;
        }

        // Redirect any url that has a query that begins the following string.
        $disallowUrls = array('/discussion/', '/messages', '/profile', '/activity');
        if ($this->disallowUrl($disallowUrls)) {
            // All authenticated users who have not confirmed are redirected to the confirm page.
            redirect('/entry/confirm');
        }

        return;
    }

    /**
     * Redirect to the confirm page after login if user is unconfirmed.
     *
     */
    public function userModel_afterSignIn_handler() {
        $userConfirmed = val("ConfirmedTerms", gdn::session()->User, 0);
        if (!$userConfirmed) {
            // Break out of jquery to redirect.
            die('<script type="text/javascript">window.location = window.location.protocol + "//" + window.location.host + "/entry/confirm";</script>');
        }
        return;
    }


    /**
     * Parse out which pages should redirect to the reconfirm page.
     *
     * @return bool
     */
    private function disallowUrl($disallowUrls) {
        $queryString = val("QUERY_STRING", gdn::request()->getRequestArguments("server"));

        /** @var $p parsed from $queryString */
        parse_str($queryString);

        foreach ($disallowUrls as $url) {
            if (substr($p, 0, strlen($url)) === $url) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse out which pages should not redirect to the reconfirm page.
     *
     * @return bool
     */
    private function allowUrl($allowUrls) {
        $queryString = val("QUERY_STRING", gdn::request()->getRequestArguments("server"));
        /** @var $p parsed from $queryString */
        parse_str($queryString);
        foreach ($allowUrls as $url) {
            if (substr($p, 0, strlen($url)) === $url) {
                return true;
            }
        }

        return false;
    }


    /**
     * Create a form to capture the user's new password, validate that it doesn't match the existing password.
     * @param $sender
     * @param $args
     */
    public function entryController_confirm_create($sender, $args) {
        // Don't allow guests to fill see this page.
        if (!gdn::session()->isValid()) {
            return;
        }

        $sender->addJsFile('password.js');

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

            if ($sender->Form->errorCount() == 0) {
                //save the password, confirmed terms, etc.
                $sender->UserModel->passwordReset(gdn::session()->UserID, gdn::request()->post('Password'));
                $sender->UserModel->setField(gdn::session()->UserID, "ConfirmedTerms", 1);
                redirect('/');
            }
        }
        $sender->render("confirm", "", "plugins/Reconfirm");
    }

    /**
     * New users will be automatically confirmed because they have to agree to the new terms of use and will have new passwords.
     *
     * @param $sender
     * @param $args
     */

    public function entryController_register_handler($sender, $args) {
        $sender->Form->addHidden('ConfirmedTerms', 1);
    }
}

/**
 * Validate that the new password is different from the existing password.
 *
 * @param $submittedPassword 'New' password supplied by user.
 * @return bool
 * @throws Gdn_UserException
 */
function validateNewPassword ($submittedPassword = null) {
    $user = gdn::userModel()->getByEmail(val('Email', gdn::session()->User));
    $existingPassword = val("Password", $user);
    $passwordHash = new Gdn_PasswordHash();
    $passwordMatch = $passwordHash->checkPassword($submittedPassword, $existingPassword, val('HashMethod', $user), val('Name', $user));
    // Since this is to validate that we are changing the password, if it matches return false.
    return ($passwordMatch === true) ? false : true;
}
