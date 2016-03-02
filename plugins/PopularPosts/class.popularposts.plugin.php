<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$PluginInfo['PopularPosts'] = array(
    'Name' => 'Popular posts',
    'Description' => 'Shows popular posts (most viewed) for the selected timeframe.',
    'Version' => '1.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'HasLocale' => false,
    'License' => 'GNU GPL2',
    'SettingsUrl' => '/settings/popularPosts',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Alexandre (DaazKu) Chouinard',
    'AuthorEmail' => 'alexandre.c@vanillaforums.com'
);

/**
 * Class PopularPostsPlugin
 */
class PopularPostsPlugin extends Gdn_Plugin {

    /**
     * Create a method called "popularPosts" on the SettingController.
     *
     * @param $sender Sending controller instance
     */
    public function settingsController_popularPosts_create($sender) {

        $sender->title(sprintf(t('%s settings'), t('Popular Posts')));
        $sender->addSideMenu('settings/popularPosts');

        $sender->Form = new Gdn_Form();

        $this->dispatch($sender, $sender->RequestArgs);
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
            'PopularPosts.MaxAge' => '30',
        ));

        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } else {
            $configurationModel->Validation->applyRule('PopularPosts.MaxAge', 'Required');
            $configurationModel->Validation->applyRule('PopularPosts.MaxAge', 'Integer');

            if ($sender->Form->getFormValue('PopularPosts.MaxAge') > 30) {
                $sender->Form->setFormValue('PopularPosts.MaxAge', 30);
            } else if ($sender->Form->getFormValue('PopularPosts.MaxAge') < 0) {
                $sender->Form->setFormValue('PopularPosts.MaxAge', 0);
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
        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Add-ons', t('Popular Posts'), 'settings/popularPosts', 'Garden.Settings.Manage');
    }
}
