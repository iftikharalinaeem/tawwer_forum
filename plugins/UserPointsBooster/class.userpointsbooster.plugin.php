<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/*
 * This plugin was originally done for Xamarin
 * https://vanillaforums.teamwork.com/tasks/3448780
 */
$PluginInfo['UserPointsBooster'] = array(
    'Name' => 'User Points Booster',
    'Description' => 'Allow giving more points to users for certain actions',
    'Version' => '1.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'HasLocale' => false,
    'License' => 'GNU GPL2',
    'SettingsUrl' => '/settings/userpointsbooster',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Alexandre (DaazKu) Chouinard',
    'AuthorEmail' => 'alexandre.c@vanillaforums.com'
);

/**
 * Class UserPointsBoosterPlugin
 */
class UserPointsBoosterPlugin extends Gdn_Plugin {

    /**
     * Plugin setup
     */
    public function setup() {
        touchConfig('UserPointsBooster.PostPoint', 1);
    }

    /**
     * Create a method called "userPointsBooster" on the SettingController.
     *
     * @param SettingsController $sender Sending controller instance
     */
    public function settingsController_userPointsBooster_create($sender) {
        $sender->title(sprintf(t('%s settings'), t('User Point Booster')));
        $sender->addSideMenu('settings/userpointsbooster');
        $sender->Form = new Gdn_Form();

        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');

        $sender->setData('PluginDescription', $this->getPluginKey('Description'));

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(array(
            'UserPointsBooster.PostPoint' => c('UserPointsBooster.PostPoint', 1),
        ));
        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } else {
            $configurationModel->Validation->applyRule('UserPointsBooster.PostPoint', 'Required');
            $configurationModel->Validation->applyRule('UserPointsBooster.PostPoint', 'Integer');

            if ($sender->Form->getFormValue('UserPointsBooster.PostPoint') < 0) {
                $sender->Form->setFormValue('UserPointsBooster.PostPoint', 0);
            }

            if ($sender->Form->save()) {
                $sender->StatusMessage = t('Your changes have been saved.');
            }
        }

        $sender->render($this->getView('configuration.php'));
    }

    /**
     * Add a link to the dashboard menu.
     *
     * @param $sender Sending controller instance.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addLink('Add-ons', t('User Point Booster'), 'settings/userpointsbooster', 'Garden.Settings.Manage');
    }

    /**
     * Adds point for new Discussions
     *
     * @param DiscussionModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, $args) {
        // We do not want to grant any points to edited discussions ;)
        if (!$args['Insert']) {
            return;
        }

        $this->addPostPoints();
    }

    /**
     * Adds point for new Comments
     *
     * @param CommentModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function commentModel_afterSaveComment_handler($sender, $args) {
        // We do not want to grant any points to edited comments ;)
        if (!$args['Insert']) {
            return;
        }

        $this->addPostPoints();
    }

    /**
     * Gives point(s), according to the UserPointsBooster.PostPoint configuration, to the current user.
     */
    protected function addPostPoints() {
        UserModel::givePoints(Gdn::session()->UserID, c('UserPointsBooster.PostPoint'), 'Posts');
    }
}
