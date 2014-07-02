<?php if (!defined('APPLICATION')) {
    exit();
}

/**
 * Plugin definition.
 */
$PluginInfo['securitywords'] = array(
    'Name' => 'Security Words',
    'Description' => 'When anyone posts a discussion, comment, or activity that contains one of the security words, that post will be added to the moderation queue.',
    'Version' => '1.0.0',
    'MobileFriendly' => true,
    'Author' => 'Dane MacMillan',
    'AuthorEmail' => 'work@danemacmillan.com',
    'AuthorUrl' => 'https://danemacmillan.com/vanilla-forums',
    'SettingsUrl' => '/settings/securitywords',
    'SettingsPermission' => 'Garden.Settings.Manage'
);

/**
 * Class SecurityWordsPlugin.
 *
 * Whenever a post (new discussion, discussion comment, activity, activity
 * comment) is added to the forum, check it against the list of security words
 * provided in the dashboard. If a match or matches exist, allow the post to
 * continue displaying, but add it to the moderation queue and notify moderators
 * of this post. Give moderators an option to toggle this notification on or
 * off.
 */
class SecurityWordsPlugin extends Gdn_Plugin {

    // Constants //


    // Properties //


    // Methods //


    /**
     * The constructor.
     */
    public function __construct() {

    }

    /**
     * Check new activity post for security words.
     *
     * @param activityModel $sender The activity model.
     * @param event $args The event arguments.
     */
    public function activityModel_beforeSave_handler($sender, $args) {
        $eventData =& $args;
        $mainBody = $eventData['Activity']['Story'];
    }

    /**
     * Check activity comment post for security words.
     *
     * @param activityModel $sender The activity model.
     * @param event $args The event arguments.
     */
    public function activityModel_beforeSaveComment_handler($sender, $args) {
        $eventData =& $args;
        $mainBody = $eventData['Comment']['Body'];
    }

    /**
     * Adds menu option to the left in dashboard.
     *
     * @param Controller &$sender The controller for the given context.
     */
    public function base_getAppSettingsMenuItems_handler(&$sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->AddItem('Moderation', T('Moderation'));
        $menu->AddLink('Moderation', T('Security Words'), '/settings/securitywords', 'Garden.Settings.Manage');
    }

    /**
     * Check discussion comment post for security words.
     *
     * @param postController $sender The post controller.
     * @param event $args The event arguments.
     */
    public function postController_afterCommentSave_handler($sender, $args) {
        $eventData =& $args;
        $discussionComment = (array) $eventData['Comment'];
        $mainBody = $discussionComment['Body'];
    }

    /**
     * Check new discussion post for security words.
     *
     * @param postController $sender The post controller.
     * @param event $args The event arguments.
     */
    public function postController_afterDiscussionSave_handler($sender, $args) {
        $eventData =& $args;
        $discussion = (array) $eventData['Discussion'];
        $mainBody = $discussion['Body'];
        $mainTitle = $discussion['Name'];
    }

    /**
     * Create Security Words settings page.
     *
     * @param settingsController $sender The controller.
     * @param array $args Optional arguments to pass.
     */
    public function settingsController_securitywords_create($sender, $args = array()) {
        $sender->Permission('Garden.Settings.Manage');

        $cf = new ConfigurationModule($sender);

        $cf->Initialize(array(
            'Plugins.securitywords.words' => array(
                'LabelCode' => 'Security Words',
                'Control' => 'TextBox',
                'Description' => 'Provide a semicolon-separated list of words
                                  that will trigger the need for moderation
                                  against a post with those security words.',
                'Options' => array(
                    'MultiLine' => true
                )
            )
        ));

        $sender->AddSideMenu();
        $sender->SetData('Title', T('Security Words'));
        $cf->RenderAll();
    }

    /**
     * Run when the plugin is enabled.
     */
    public function setup() {
        // If no previous config, populate it with example security words.
        TouchConfig(array(
            'Plugins.securitywords.words' => 'area51;mkultra;911'
        ));
    }
}
