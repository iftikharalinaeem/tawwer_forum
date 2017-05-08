<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Class PreModeratedCategoryPlugin
 */
class PreModeratedCategoryPlugin extends Gdn_Plugin {
    /**
     * Add a link to the dashboard menu.
     *
     * @param object $sender Sending controller instance.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Add-ons', t('Pre-Moderated category'), 'settings/premoderatedcategory', 'Garden.Settings.Manage');
    }

    /**
     * Create the plugin's setting endpoint
     *
     * @param SettingsController $sender Sending controller instance.
     */
    public function settingsController_premoderatedcategory_create($sender) {
        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');

        $sender->title(sprintf(t('%s settings'), t('Pre-Moderated category')));
        $sender->addSideMenu('settings/premoderatedcategory');

        $sender->setData('PluginDescription', $this->getPluginKey('Description'));

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'PreModeratedCategory.IDs' => explode(',', c('PreModeratedCategory.IDs', '')),
        ]);

        $sender->Form->setModel($configurationModel, [
            'PreModeratedCategory.IDs' => explode(',', c('PreModeratedCategory.IDs', '')),
        ]);

        // If we are not seeing the form for the first time
        if ($sender->Form->authenticatedPostBack() !== false) {

            $selectedCategories = $sender->Form->getFormValue('PreModeratedCategory.IDs', []);
            if ($selectedCategories === false) {
                $selectedCategories = [];
            }

            // Save as string
            $sender->Form->setFormValue('PreModeratedCategory.IDs', implode(',', $selectedCategories));

            if ($sender->Form->save() !== false) {
                $sender->informMessage(t('Your changes have been saved.'));
            }

            // Restore as array for display
            $sender->Form->setFormValue('PreModeratedCategory.IDs', $selectedCategories);
        }

        $sender->render($sender->fetchViewLocation('settings', '', 'plugins/PreModeratedCategory'));
    }

    /**
     * Send discussions directly to the moderation queue and warn the user about it.
     *
     * @param DiscussionModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionModel_afterValidateDiscussion_handler($sender, $args) {
        $categoryList = c('PreModeratedCategory.IDs');
        if (!$categoryList) {
            return;
        }

        $categories = explode(',', $categoryList);
        if (!in_array(val('CategoryID', $args['DiscussionData']), $categories)) {
            return;
        }

        $args['IsValid'] = false;
        $args['InvalidReturnType'] = UNAPPROVED;

        LogModel::insert('Pending', 'Discussion', $args['DiscussionData']);
    }
}
