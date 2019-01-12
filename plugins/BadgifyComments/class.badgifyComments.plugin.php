<?php

/**
 * This plugin will allow admins to designate certain discussions so that whenver
 * users comment in the discussion they receive a badge.
 *
 * Class BadgifyCommentsPlugin
 */
class BadgifyCommentsPlugin extends Gdn_Plugin {

    /**
     * Set default settings, add a field to the badge table to link badges to discussions.
     *
     * @throws Exception
     */
    public function setup() {
        $this->structure();
    }


    /**
     * Configure db and config file to create default badges and store DiscussionIDs in the Badge table.
     * Add a column to the Badge table to flag badges so that instead of being awarded automatically they
     * generate a request for badge.
     *
     * @throws Exception
     */
    public function structure() {
        Gdn::structure()->table('Badge')
            ->column('BadgeDiscussion', 'int', '0', ['index'])
            ->set();

        \Gdn::config()->touch('Badgify.Default.Name', 'Commented in Discussion');
        \Gdn::config()->touch('Badgify.Default.Slug', 'commented-in-discussion');
        \Gdn::config()->touch('Badgify.Default.Description', 'Commented in a discussion flagged by admin to give badges. "%s"');
        \Gdn::config()->touch('Badgify.Default.Points', '2');
        \Gdn::config()->touch('Badgify.Default.BadgeClass', 'Commenter');
        \Gdn::config()->touch('Badgify.Default.BadgeClassLevel', '1');
        \Gdn::config()->touch('Badgify.Default.AwardManually', 'checked');
    }


    /**
     * Hook into flyout menu on discussions.
     *
     * @param DiscussionController $sender
     * @param array $args
     */
    public function base_discussionOptions_handler($sender, $args) {
        $discussionID = valr('Discussion.DiscussionID', $args);

        // If there isn't already a badge assigned to this discussion, add link to flyout menu.
        if (!$this->getDiscussionBadge($discussionID) && Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            $args['DiscussionOptions']['Add a Badge'] = [
                'Label' => t('Add a Badge'),
                'Url' => "/badge/manage?discussionid={$discussionID}",
                'Class' => 'Popup'
            ];
        }
    }


    /**
     * Hook into badge creation form and set default fields.
     *
     * @param BadgeController $sender
     * @param array $args
     */
    public function badgeController_manageBadgeForm_handler($sender, $args) {
        if ($sender->Form->authenticatedPostBack()) {
            $formArray = $sender->Form->formValues();
            $awardManually = $sender->Form->getFormValue('AwardManually');
            if ($awardManually) {
                $sender->BadgeModel->removeFilterField('Attributes');
                $sender->Form->formValues($formArray + ['Attributes' => ['AwardManually' => true]]);
            }
        } else {
            $formArray = (array) $sender->Form->formData();
            // check for lowercase discussionID because Garden Request lowercases all get vars.
            $discussionID = Gdn::request()->get('discussionid');
            $discussion = DiscussionModel::instance();
            $discussions = $discussion->getID($discussionID);
            if ($discussionID) {
                $defaultValues = [
                    'Name' => c('Badgify.Default.Name'),
                    'Slug' => c('Badgify.Default.Slug').'-'.$discussionID,
                    'Body' => sprintf(c('Badgify.Default.Description'), val('Name', $discussions)),
                    'Points' => c('Badgify.Default.Points'),
                    'Class' => c('Badgify.Default.BadgeClass'),
                    'Level' => c('Badgify.Default.BadgeClassLevel')
                ];
                $defaults = array_merge($defaultValues, $formArray);

                // Add a hidden field to save the DiscussionId to the badge.
                $sender->Form->addHidden('BadgeDiscussion', $discussionID);
                $sender->Form->setData($defaults);
            }
        }
    }

    /**
     * Add a checkbox to the Badge Creation form to mark if the badge should be given out automatically or generate a request for a badge.
     *
     * @param BadgeController $sender
     */
    public function badgeController_badgeFormFields_handler($sender) {
            echo wrap($sender->Form->labelwrap('Award Manually', 'AwardManually').
                $sender->Form->inputwrap('AwardManually', 'Checkbox', ['value' => 1, 'checked' => c('Badgify.Default.AwardManually')]), 'li', ['class' => 'form-group']);
    }


    /**
     * Create a settings page in the dashboard where the default values for discussion badges.
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_badgifyComments_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');

        $configurationModule = new ConfigurationModule($sender);

        $configurationModule->initialize([
            'Badgify.Default.Name',
            'Badgify.Default.Slug',
            'Badgify.Default.Description',
            'Badgify.Default.Points',
            'Badgify.Default.BadgeClass',
            'Badgify.Default.BadgeClassLevel'
        ]);

        $sender->addSideMenu();
        $sender->setData('Title', t('Badgification Settings'));
        $configurationModule->renderAll();
    }


    /**
     * Get all the badge data if a badge has been assigned to this discussion.
     *
     * @param int $discussionID
     * @return array|bool $badge A badge that is associated with this discussion, if not false.
     */
    private function getDiscussionBadge($discussionID) {
        if ($discussionID) {
            $badge = Gdn::sql()->select()->from('Badge')->where(['BadgeDiscussion' => $discussionID])->get()->firstRow();
            if ($badge) {
                return $badge;
            }
        }
        return false;
    }


    /**
     * Hook into comment save and give the badge.
     *
     * @param CommentModel $sender
     * @param array $args
     */
    public function commentModel_afterSaveComment_handler($sender, $args) {
        $discussionID = valr('FormPostValues.DiscussionID', $args);
        // pass the insertUserID, if it doesn't exist pass the updateUserID and allow the userBadgeModel()->give() decide to give it or not.
        $userID = valr('FormPostValues.InsertUserID', $args, valr('FormPostValues.UpdateUserID', $args));
        $badge = $this->getDiscussionBadge($discussionID);
        // When the user saves a comment check if there is a badge associated with it.
        if ($badge && $userID) {
            $attributes = dbdecode(val('Attributes', $badge));
            $userBadgeModel = new UserBadgeModel();
            if (!val('AwardManually', $attributes)) {
                // If the badge is not flagged as AwardManually, give the badge.
                $userBadgeModel->give($userID, val('BadgeID', $badge), val('Body', $badge));
            } else {
                // Otherwise generate a request for a badge.
                $userBadgeModel->request($userID, val('BadgeID', $badge), val('Body', $badge));
            }
        }
    }


    /**
     * Add a CSS class to any discussion that has a badge attached to it.
     *
     * @param VanillaController $sender
     * @param array $args
     */
    public function base_beforeDiscussionName_handler($sender, $args) {
        $discussionBadge = $this->getDiscussionBadge(valr('Discussion.DiscussionID', $args));
        if ($discussionBadge) {
            $args['CssClass'] .= ' Badgified';
            if (val('AwardManually', $discussionBadge)) {
                $args['CssClass'] .= ' ManualBadge';
            }
        }
    }
}
