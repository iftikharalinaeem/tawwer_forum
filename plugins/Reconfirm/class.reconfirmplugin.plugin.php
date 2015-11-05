<?php

$PluginInfo['Reconfirm'] = array(
    'Name' => 'Reconfirm',
    'Description' => 'Force users to reset their password and reconfirm the terms of use.',
    'Version' => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '1.0'),
    'RequiredTheme' => false,
    'HasLocale' => true,
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => TRUE,
    'Author' => "Patrick Kelly",
    'AuthorEmail' => 'patrick.k@vanillaforums.com'
);

class ReconfirmPlugin extends Gdn_Plugin {

    /**
     * Setup
     */
    public function setUp() {
        $this->structure();
    }

    public function structure() {
        Gdn::Structure()->Table('User')
            ->Column('ConfirmedTerms', 'int', '0', array('index'))
            ->Set();

//        Gdn::sql()
//            ->update('User u')
//            ->set('u.ConfirmedTerms', 0, false, false)
//            ->put();
    }

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
        if(stristr($selfUrl, "/entry/confirm") || stristr($selfUrl, "/entry/signout") || stristr($selfUrl, "/entry/signin") || stristr($selfUrl, "/home/termsofservice")) {
            return;
        }

        // All authenticated users who have not confirmed are redirected to the confirm page.
        redirect(url('http://vanilla.dev/baz/entry/confirm'));
    }


    public function entryController_confirm_create($sender, $args) {
        $sender->AddJsFile('password.js');
        $sender->Form->addHidden("UserID", gdn::session()->UserID);
        $sender->Form->addHidden("ConfirmedTerms", 1);
        if ($sender->Form->authenticatedPostBack() === true) {
            $sender->UserModel->Validation->applyRule('TermsOfService', 'Required', t('You must agree to the terms of service.'));
            $sender->UserModel->Validation->applyRule('Password', 'Required');
            $sender->UserModel->Validation->applyRule('Password', 'Strength');
            $sender->UserModel->Validation->applyRule('Password', 'Match');

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