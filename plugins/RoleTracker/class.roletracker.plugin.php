<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$PluginInfo['RoleTracker'] = [
    'Name' => 'Role Tracker',
    'Description' => 'Highlight and track posts made by users in selected roles.',
    'Version' => '1.0',
    'RequiredApplications' => ['Vanilla' => '2.2.111'],
    'RequiredPlugins' => ['Tagging' => '1.8.12'], // TODO Bump that when it is possible
    'HasLocale' => false,
    'License' => 'GNU GPL2',
    'SettingsUrl' => '/settings/roletracker',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Alexandre (DaazKu) Chouinard',
    'AuthorEmail' => 'alexandre.c@vanillaforums.com'
];

/**
 * Class RoleTracker
 */
class RoleTracker extends Gdn_Plugin {
    /**
     * Setup is called when the plugin is enabled. It prepares the config and db.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Update the DB structure. Called on /utility/update and when the plugin is enabled
     */
    public function structure() {
        require(__DIR__.'/structure.php');
    }

    #######################################
    ## Plugin's functions
    #######################################
    /**
     * Return an instance of RoleTrackerModel
     *
     * @return RoleTrackerModel roleTrackerModel
     */
    protected function getRoleTrackerModel() {
        static $roleTrackingModel;

        if ($roleTrackingModel === null) {
            $roleTrackingModel = new RoleTrackerModel(new RoleModel(), TagModel::instance());
        }

        return $roleTrackingModel;
    }

    #######################################
    ## Plugin's hooks
    #######################################
    /**
     * Add a link to the dashboard menu.
     *
     * @param object $sender Sending controller instance.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Add-ons', t('Role Tracker'), 'settings/roletracker', 'Garden.Settings.Manage');
    }

    /**
     * Create a method called "roletracker" on the SettingsController.
     *
     * @param SettingsController $sender Sending controller instance
     */
    public function settingsController_roleTracker_create($sender) {
        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');

        $sender->title(sprintf(t('%s settings'), t('RoleTracker')));
        $sender->addSideMenu('settings/roletracker');

        $sender->setData('PluginDescription', $this->getPluginKey('Description'));

        $sender->Form = new Gdn_Form();

        $roleTrackerModel = $this->getRoleTrackerModel();
        $formData = $roleTrackerModel->getFormData(false);
        $sender->Form->setModel($roleTrackerModel, $formData);

        $sender->setData('Roles', $roleTrackerModel->getRoles());

        // If we are not seeing the form for the first time
        if ($sender->Form->authenticatedPostBack() !== false) {
            if ($sender->Form->save() !== false) {
                $sender->informMessage(t('Your changes have been saved.'));
            }
        }

        $sender->render($this->getView('settings.php'));
    }

    /**
     * Add Types to TagModel
     *
     * @param TagModel $sender Sending controller instance
     */
    public function tagModel_types_handler($sender) {
        $sender->addType('Tracker', array(
            'key' => 'Tracker',
            'name' => 'Tracker',
            'plural' => 'Trackers',
            'addtag' => false,
            'default' => false
        ));
    }

}
