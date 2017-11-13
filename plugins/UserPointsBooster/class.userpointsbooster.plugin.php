<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/*
 * This plugin was originally done for Xamarin
 * https://vanillaforums.teamwork.com/tasks/3448780
 */
/**
 * Class UserPointsBoosterPlugin
 */
class UserPointsBoosterPlugin extends Gdn_Plugin {

    /**
     * Plugin setup
     */
    public function setup() {
        touchConfig('UserPointsBooster.PostPoints', 1);
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
        $configurationModel->setField([
            'UserPointsBooster.PostPoints' => c('UserPointsBooster.PostPoints', 1),
        ]);
        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } else {
            $configurationModel->Validation->applyRule('UserPointsBooster.PostPoints', 'Required');
            $configurationModel->Validation->applyRule('UserPointsBooster.PostPoints', 'Integer');

            if ($sender->Form->getFormValue('UserPointsBooster.PostPoints') < 0) {
                $sender->Form->setFormValue('UserPointsBooster.PostPoints', 0);
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

        $this->addPostPoints(valr('Discussion.CategoryID', $args));
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

        $discussionID = valr('CommentData.DiscussionID', $args);
        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->getID($discussionID);

        $this->addPostPoints(val('CategoryID', $discussion));
    }

    /**
     * Gives point(s), according to the UserPointsBooster.PostPoints configuration, to the current user.
     *
     * @param int $categoryID The category ID in which the post was created.
     */
    protected function addPostPoints($categoryID) {
        CategoryModel::givePoints(Gdn::session()->UserID, c('UserPointsBooster.PostPoints'), 'Posts', $categoryID);
    }
}
