<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$PluginInfo['PopularPosts'] = array(
    'Name' => 'Popular posts',
    'Description' => 'Shows popular posts',
    'Version' => '1.0',
    //'RequiredApplications' => array('Vanilla' => '????'), // TODO Ask how to determine plugin version
    /*'RequiredTheme' => false,*/
    'HasLocale' => false,
    'License' => 'GNU GPL2',
    'SettingsUrl' => '/plugin/popularPosts',
    'SettingsPermission' => 'Garden.Settings.Manage',
    /*'MobileFriendly' => true,*/
    'Author' => 'Alexandre (DaazKu) Chouinard',
    'AuthorEmail' => 'alexandre.c@vanillaforums.com'
);

/**
 * Class PopularPostsPlugin
 */
class PopularPostsPlugin extends Gdn_Plugin {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Create a method called "popularPosts" on the PluginController
     *
     * @param $sender Sending controller instance
     */
    public function pluginController_popularPosts_create($sender) {

        $sender->title('Popular posts plugin');
        $sender->addSideMenu('plugin/popularPosts');

        // If your sub-pages use forms, this is a good place to get it ready
        $sender->Form = new Gdn_Form();

        $this->dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Always document every method.
     *
     * @param $sender
     */
    public function controller_index($sender) {
        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('PluginDescription',$this->getPluginKey('Description'));

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(array(
            'Plugin.PopularPosts.MaxAge' => '30',
        ));

        // Set the model on the form.
        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $sender->Form->setData($configurationModel->Data);
        } else {
            $configurationModel->Validation->applyRule('Plugin.PopularPosts.MaxAge', 'Required');
            $configurationModel->Validation->applyRule('Plugin.PopularPosts.MaxAge', 'Integer');

            if ($sender->Form->getFormValue('Plugin.PopularPosts.MaxAge') > 30) {
                $sender->Form->setFormValue('Plugin.PopularPosts.MaxAge', 30);
            } else if ($sender->Form->getFormValue('Plugin.PopularPosts.MaxAge') < 0) {
                $sender->Form->setFormValue('Plugin.PopularPosts.MaxAge', 0);
            }

            $saved = $sender->Form->save();
            if ($saved) {
                $sender->StatusMessage = t("Your changes have been saved.");
            }
        }

        // GetView() looks for files inside plugins/PluginFolderName/views/ and returns their full path. Useful!
        $sender->render($this->getView('configuration.php'));
    }

    /**
     * Add a link to the dashboard menu
     *
     * By grabbing a reference to the current SideMenu object we gain access to its methods, allowing us
     * to add a menu link to the newly created /plugin/Example method.
     *
     * @param $sender Sending controller instance
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Add-ons', 'Popular Posts', 'plugin/popularPosts', 'Garden.Settings.Manage');
    }
}