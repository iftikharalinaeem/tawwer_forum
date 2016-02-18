<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/*
 * This plugin was originally done for Xamarin
 * https://vanillaforums.teamwork.com/tasks/3448780
 */
$PluginInfo['UserPointBooster'] = array(
    'Name' => 'User Point Booster',
    'Description' => 'Allow to give more points to users for certain actions',
    'Version' => '1.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'HasLocale' => false,
    'License' => 'GNU GPL2',
    'SettingsUrl' => '/settings/userPointBooster',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Alexandre (DaazKu) Chouinard',
    'AuthorEmail' => 'alexandre.c@vanillaforums.com'
);

/**
 * Class UserPointBoosterPlugin
 */
class UserPointBoosterPlugin extends Gdn_Plugin {

    /**
     * Plugin setup
     */
    public function setup() {
        touchConfig('UserPointBooster.PostPointValue', 1);
    }

    /**
     * Define what to do for the /index page of this plugin.
     *
     * @param $sender Sending controller instance
     */
    public function controller_index($sender) {
        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');

        $sender->setData('PluginDescription', $this->getPluginKey('Description'));

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(array(
            'UserPointBooster.PostPointValue' => c('UserPointBooster.PostPointValue', 1),
        ));
        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } else {
            $configurationModel->Validation->applyRule('UserPointBooster.PostPointValue', 'Required');
            $configurationModel->Validation->applyRule('UserPointBooster.PostPointValue', 'Integer');

            if ($sender->Form->getFormValue('UserPointBooster.PostPointValue') < 0) {
                $sender->Form->setFormValue('UserPointBooster.PostPointValue', 0);
            }

            if ($sender->Form->save()) {
                $sender->StatusMessage = t('Your changes have been saved.');
            }
        }

        $sender->render($this->getView('configuration.php'));
    }

    /**
     * Create a method called "userPointBooster" on the SettingController.
     *
     * @param $sender Sending controller instance
     */
    public function settingsController_userPointBooster_create($sender) {
        $sender->title(sprintf(t('%s settings'), t('User Point Booster')));
        $sender->addSideMenu('settings/userPointBooster');
        $sender->Form = new Gdn_Form();
        $this->dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Add a link to the dashboard menu.
     *
     * @param $sender Sending controller instance.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addLink('Add-ons', t('User Point Booster'), 'settings/userPointBooster', 'Garden.Settings.Manage');
    }

    /**
     * Hook on AfterSaveDiscussion event.
     *
     * Adds point for new Discussions
     *
     * @param $sender Sending controller instance.
     * @param $args Event arguments.
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, $args) {

        // We do not want to grant any points to edited discussions ;)
        if (!$args['Insert']) {
            return;
        }

        $this->addPostPoint();
    }

    /**
     * Hook on AfterSaveComment event.
     *
     * Adds point for new Comments
     *
     * @param $sender Sending controller instance.
     * @param $args Event arguments.
     */
    public function commentModel_afterSaveComment_handler($sender, $args) {

        // We do not want to grant any points to edited comments ;)
        if (!$args['Insert']) {
            return;
        }

        $this->addPostPoint();
    }

    /**
     * Gives point(s), according to the per points per post configuration, to the current user.
     */
    protected function addPostPoint() {
        UserModel::givePoints(Gdn::session()->UserID, c('UserPointBooster.PostPointValue'), 'Posts');
    }
}
