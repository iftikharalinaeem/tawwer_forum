<?php

$PluginInfo['BadgifyComments'] = array(
    'Name' => 'Badgify Comments',
    'ClassName' => 'BadgifyCommentsPlugin',
    'Description' => 'Allows user login to be authenticated on Auth0 SSO.',
    'Version' => '1.0.0',
    'RequiredApplications' => ['Vanilla' => '1.0', 'Reputation' => '1.0'],
    'RequiredTheme' => false,
    'HasLocale' => false,
    'SettingsUrl' => '/settings/BadgifyComments',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true
);

class BadgifyCommentsPlugin extends Gdn_Plugin {

    private $defaultSettings = [
        'Name' => 'Commented in Discussion',
        'Slug' =>'commented-in-discussion',
        'Description' =>'Comment in a specially marked discussion, get a badge!',
        'Points' =>'5',
        'BadgeClass' =>'Cool',
        'BadgeClassLevel' =>'11',

    ];
    /**
     * Hook into flyout menu on discussions.
     * TODO Check if there is already a badge assigned to this discussion
     * TODO Check if the user has permissions to add a badge
     * @param $sender
     * @param $args
     */
    public function base_discussionOptions_handler ($sender, $args) {
        $discussionID = $args['Discussion']->DiscussionID;
        $args['DiscussionOptions']['Add a Badge'] = [
            'Label' => t('Add a Badge'),
            'Url' => "/badge/manage/?discussionID={$discussionID}",
            'Class' => 'Popup'
        ];
    }

    /**
     * Hook into badge creation form and set default fields.
     * TODO Create a PR for the eventHandler in the BadgeController
     * TODO Decide which fields are being set and to what value
     * TODO Decide if we allow the user to change those fields or disable them
     * TODO Query the discussion to get it's content and add it to the form as the description
     * @param $sender
     * @param $args
     */
    public function badgeController_manageBadgeForm_handler ($sender, $args) {
        $formArray = (array) $sender->Form->formData();
        $discussionIDAppend = Gdn::request()->get('discussionID');

        if ($discussionIDAppend) {
            $defaultValues = [
                'Name' => c('Badgify.Default.Name', $this->defaultSettings['Name']),
                'Slug' => c('Badgify.Default.Slug', $this->defaultSettings['Slug']).'-'.$discussionIDAppend,
                'Body' => c('Badgify.Default.Description', $this->defaultSettings['Description']),
                'Points' => c('Badgify.Default.Points', $this->defaultSettings['Points']),
                'Class' => c('Badgify.Default.BadgeClass', $this->defaultSettings['BadgeClass']),
                'Level' => c('Badgify.Default.BadgeClassLevel', $this->defaultSettings['BadgeClassLevel'])
            ];
            $defaults = array_merge($defaultValues, $formArray);
            $sender->Form->setData($defaults);
            $sender->Form->input('Name2', 'Text', ['Disabled' => true]);
            $sender->View = null;
            $sender->render('status', '', 'plugins/ideation');
        }
    }

    /**
     * TODO Create form to save to config
     * @param $sender
     * @param $args
     */
    public function settingsController_badgifyComments_create ($sender, $args) {
        $sender->Permission('Garden.Settings.Manage');

        $cf = new ConfigurationModule($sender);

        $cf->Initialize(
            array(
                'Badgify.Default.Name' => ['Default' => $this->defaultSettings['Name']],
                'Badgify.Default.Slug' => ['Default' => $this->defaultSettings['Slug']],
                'Badgify.Default.Description' => ['Default' => $this->defaultSettings['Description']],
                'Badgify.Default.Points' => ['Default' => 10],
                'Badgify.Default.BadgeClass' => ['Default' => $this->defaultSettings['BadgeClass']],
                'Badgify.Default.BadgeClassLevel' => ['Default' => $this->defaultSettings['BadgeClassLevel']]
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
     */
    public function discussionBadgeExists($discussionID = null) {
        return;
    }

    /**
     * Hook into comment save and give the bandge.
     * @param $sender
     * @param $args
     */
    public function postController_afterSaveComment_handler($sender, $args) {
        $discussionID = val('DiscussionID', $args['FormPostValues']);
        if ($this->discussionBadgeExists($discussionID)) {
            // give the discussion badge.
        }
    }
}
