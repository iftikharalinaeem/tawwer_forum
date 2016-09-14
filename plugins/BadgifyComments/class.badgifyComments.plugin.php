<?php

$PluginInfo['BadgifyComments'] = array(
    'Name' => 'Badgify Comments',
    'ClassName' => 'BadgifyCommentsPlugin',
    'Description' => 'Allows user login to be authenticated on Auth0 SSO.',
    'Version' => '1.0.0',
    'RequiredApplications' => ['Vanilla' => '1.0', 'Reputation' => '1.0'],
    'SettingsUrl' => '/settings/BadgifyComments',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true
);

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
        Gdn::structure()->table('Badge')
            ->column('BadgeDiscussion', 'int', '0', array('index'))
            ->set();

        touchConfig('Badgify.Default.Name', 'Commented in Discussion');
        touchConfig('Badgify.Default.Slug', 'commented-in-discussion');
        touchConfig('Badgify.Default.Description', 'Commented in a discussion flagged by admin to give badges.');
        touchConfig('Badgify.Default.Points', '2');
        touchConfig('Badgify.Default.BadgeClass', 'Commenter');
        touchConfig('Badgify.Default.BadgeClassLevel', '1');

    }


    /**
     * Hook into flyout menu on discussions.
     *
     * @param $sender
     * @param $args
     */
    public function base_discussionOptions_handler ($sender, $args) {
        $discussionID = valr('Discussion.DiscussionID', $args);

        // If there isn't already a badge assigned to this discussion, add link to flyout menu.
        if (!$this->discussionBadgeExists($discussionID) && Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            $args['DiscussionOptions']['Add a Badge'] = [
                'Label' => t('Add a Badge'),
                'Url' => "/badge/manage/?discussionID={$discussionID}",
                'Class' => 'Popup'
            ];
        }
    }


    /**
     * Hook into badge creation form and set default fields.
     *
     * @param $sender
     * @param $args
     */
    public function badgeController_manageBadgeForm_handler($sender, $args) {
        $formArray = (array) $sender->Form->formData();
        $discussionID = Gdn::request()->get('discussionID');
        if ($discussionID) {
            $defaultValues = [
                'Name' => c('Badgify.Default.Name'),
                'Slug' => c('Badgify.Default.Slug').'-'.$discussionID,
                'Body' => c('Badgify.Default.Description'),
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


    /**
     * Create a settings page in the dashboard where the default values for discussion badges.
     *
     * @param $sender
     * @param $args
     */
    public function settingsController_badgifyComments_create ($sender, $args) {
        $sender->Permission('Garden.Settings.Manage');

        $cf = new ConfigurationModule($sender);

        $cf->Initialize(
            array(
                'Badgify.Default.Name',
                'Badgify.Default.Slug',
                'Badgify.Default.Description',
                'Badgify.Default.Points',
                'Badgify.Default.BadgeClass',
                'Badgify.Default.BadgeClassLevel'
            )
        );

        $sender->AddSideMenu();
        $sender->SetData('Title', T('Badgification Settings'));
        $cf->RenderAll();
    }


    /**
     * Query the badge table to find out if a badge already exists for this discussion.
     *
     * @param null $discussionID
     *
     * @return int $discussionID
     */
    public function discussionBadgeExists($discussionID = null) {
        if ($discussionID) {
            $badge = reset(Gdn::sql()->select()->from('Badge')->where(['BadgeDiscussion' => $discussionID])->get()->resultArray());
            if ($badge) {
                return $badge;
            }
        }
        return false;
    }


    /**
     * Hook into comment save and give the bandge.
     * @param $sender
     * @param $args
     */
    public function commentModel_afterSaveComment_handler($sender, $args) {
        $discussionID = valr('FormPostValues.DiscussionID', $args);
        $userID = valr('FormPostValues.InsertUserID', $args);
        $badge = $this->discussionBadgeExists($discussionID);
        if ($badge) {
            $userBadgeModel = new UserBadgeModel();
            $userBadgeModel->Give($userID, val('BadgeID', $badge), val('Body', $badge));
        }
    }


    /**
     * Add a CSS class to any discussion that has a badge attached to it.
     *
     * @param $sender
     * @param $args
     */
    public function discussionsController_beforeDiscussionName_handler($sender, $args) {
        if ($this->discussionBadgeExists(valr('Discussion.DiscussionID', $args))) {
            $args['CssClass'] .= ' Badgified';
        }
    }
}
