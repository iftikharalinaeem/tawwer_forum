<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Class PreModeratedCategoryPlugin
 */
class PreModeratedCategoryPlugin extends Gdn_Plugin {

    private $discussionModel;

    public function __construct(DiscussionModel $discussionModel) {
        parent::__construct();
        $this->discussionModel = $discussionModel;
    }

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

        $sender->title(sprintf(t('%s Settings'), t('Pre-Moderated Category')));
        $sender->addSideMenu('settings/premoderatedcategory');

        $sender->setData('PluginDescription', $this->getPluginKey('Description'));

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'PreModeratedCategory.IDs' => explode(',', c('PreModeratedCategory.IDs', '')),
            'PreModeratedCategory.Discussions' => c('PreModeratedCategory.Discussions', true),
            'PreModeratedCategory.Comments' => c('PreModeratedCategory.Comments', false)
        ]);

        $sender->Form->setModel($configurationModel, [
            'PreModeratedCategory.IDs' => explode(',', c('PreModeratedCategory.IDs', '')),
            'PreModeratedCategory.Discussions' => c('PreModeratedCategory.Discussions', true),
            'PreModeratedCategory.Comments' => c('PreModeratedCategory.Comments', false)
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
            $sender->Form->setFormValue('PreModeratedCategory.Discussions', c('PreModeratedCategory.Discussions'));
            $sender->Form->setFormValue('PreModeratedCategory.Comments', c('PreModeratedCategory.Comments'));
        }

        $sender->render($sender->fetchViewLocation('settings', '', 'plugins/PreModeratedCategory'));
    }

    /**
     * Send discussions directly to the moderation queue and warn the user about it.
     *
     * @param DiscussionModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionModel_afterValidateDiscussion_handler(\DiscussionModel $sender, array $args) {
        $discussion = $args['DiscussionData'];
        $categoryID = (int)$discussion['CategoryID'];
        $moderateRecord = $this->isModeration($categoryID);

        if ($moderateRecord) {
            $args['IsValid'] = false;
            $args['InvalidReturnType'] = UNAPPROVED;

            LogModel::insert('Pending', 'Discussion', $discussion);
        }
    }

    /**
     * Send comment to moderation queue
     *
     * @param CommentModel $sender
     * @param array $args
     */
    public function commentModel_afterValidateComment_handler(\CommentModel $sender, array $args) {
        $comment = $args['CommentData'];
        $discussionID = $comment['DiscussionID'];
        $discussion = $this->discussionModel->getID($discussionID);
        $categoryID = $discussion->CategoryID;
        $moderateRecord = $this->isModeration($categoryID);

        if ($moderateRecord) {
            $args['IsValid'] = false;
            $args['InvalidReturnType'] = UNAPPROVED;

            LogModel::insert('Pending', 'Comment', $comment);
        }
    }

    /**
     * Get PreModeratedCategory.IDs config
     * @return array
     */
    private function getPreModeratedCategoryIDs(): array {
        return explode(',', c('PreModeratedCategory.IDs'));
    }

    /**
     * Pre moderation criteria
     *
     * @param int $categoryID
     * @return bool
     */
    public function isModeration(int $categoryID) {
        $categories = $this->getPreModeratedCategoryIDs();
        return in_array($categoryID, $categories);
    }
}
