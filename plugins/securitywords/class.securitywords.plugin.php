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


    // Properties //


    /**
     * @var bool $notifyUserOfRequiredModeration Notify users or not.
     */
    protected $notifyUserOfRequiredModeration;

    /**
     * @var array $securityWords Array of security words.
     */
    protected $securityWords;

    /**
     * @var array $SecurityWordsRegexPatterns Array of regex patterns for words.
     */
    protected $securityWordsRegexPatterns;


    // Methods //


    /**
     * The constructor.
     */
    public function __construct() {
        $this->notifyUserOfRequiredModeration = C('Plugins.securitywords.notifyuser', false);
    }

    /**
     * Check new activity post for security words.
     *
     * @param activityModel $sender The activity model.
     */
    public function activityModel_beforeSave_handler($sender) {
        $eventData =& $sender->EventArguments;
        $activity = (array) $eventData['Activity'];
        $mainBody = $activity['Story'];

        if ($this->hasSecurityWords($mainBody)) {
            $this->logPostForModeration('Activity', $activity);
        }
    }

    /**
     * Check activity comment post for security words.
     *
     * @param activityModel $sender The activity model.
     */
    public function activityModel_beforeSaveComment_handler($sender) {
        $eventData =& $sender->EventArguments;
        $activityComment = (array) $eventData['Comment'];
        $mainBody = $activityComment['Body'];

        if ($this->hasSecurityWords($mainBody)) {
            $this->logPostForModeration('ActivityComment', $activityComment);
        }
    }

    /**
     * Adds menu option to the left in dashboard.
     *
     * @param Controller &$sender The controller for the given context.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->AddItem('Moderation', T('Moderation'));
        $menu->AddLink('Moderation', T('Security Words'), '/settings/securitywords', 'Garden.Settings.Manage');
    }

    /**
     * Get the security words from config and split into array of words.
     *
     * @return array
     */
    public function getSecurityWords() {
        $securityWords = array();

        if (!count($this->securityWords)) {
            $securityWordsString = C('Plugins.securitywords.words');
            $securityWordsSplit = explode(';', $securityWordsString);

            if (is_array($securityWordsSplit)) {

                // Filter out empties.
                $securityWordsSplit = array_filter($securityWordsSplit);

                // Trim leading and trailing spaces. Lowercase every word.
                $securityWordsSplit = array_map(function ($word) {
                    return trim(strtolower($word));
                }, $securityWordsSplit);
            }

            if (count($securityWordsSplit)) {
                $securityWords = $this->securityWords = $securityWordsSplit;
            }
        } else {
            $securityWords = $this->securityWords;
        }

        return $securityWords;
    }

    /**
     * Get the list of security words as regex patterns, ready for matching.
     *
     * @return array
     */
    public function getSecurityWordsRegexPatterns() {
        $securityWordsRegexPatterns = array();

        if (!count($this->securityWordsRegexPatterns)) {

            // Get the array of security words, already cleaned.
            $securityWords = $this->getSecurityWords();

            // Generate an array of regex patterns to match against body text.
            $securityWordsParsed = array_map(function ($word) {
                return '/\b' . preg_quote($word, '/') . '\b/is';
            }, $securityWords);

            if (count($securityWordsParsed)) {
                $securityWordsRegexPatterns = $this->securityWordsRegexPatterns = $securityWordsParsed;
            }
        } else {
            $securityWordsRegexPatterns = $this->securityWordsRegexPatterns;
        }

        return $securityWordsRegexPatterns;
    }

    /**
     * Search for security words within a given body of text.
     *
     * @param string $text A body of text.
     *
     * @return bool
     */
    public function hasSecurityWords($text) {
        $hasSecurityWords = false;
        $securityWordsRegexPatterns = $this->getSecurityWordsRegexPatterns();
        $text = trim(strtolower($text));

        // Check for security words in provided text.
        foreach ($securityWordsRegexPatterns as $wordBoundary) {
            if (preg_match($wordBoundary, $text)) {
                // A match exists, quit the loop.
                $hasSecurityWords = true;
                break;
            }
        }

        return $hasSecurityWords;
    }

    /**
     * Send post to moderation queue, and optionally notify user.
     *
     * @param string $recordType Typically corresponds with name of DB table.
     * @param array $recordRow A row of data for the given record type.
     */
    public function logPostForModeration($recordType, $recordRow) {
        // The Insert method of the LogModel states that "You can pass an
        // additional _New element to tell the logger what the new data is."
        // This doesn't seem to do anything, though.
        $recordRow['_New'] = 'SecurityWords';
        LogModel::Insert('Moderate', $recordType, $recordRow);

        // Let user know that their post was sent to the moderation queue.
        if ($this->notifyUserOfRequiredModeration) {
            Gdn::Controller()->InformMessage(
                T('Post queued for moderation due to the use of security words.'),
                array(
                    'CssClass' => 'Dismissable',
                    'id' => 'mod'
                )
            );
        }
    }

    /**
     * Check discussion comment post for security words.
     *
     * @param postController $sender The post controller.
     */
    public function postController_afterCommentSave_handler($sender) {
        $eventData =& $sender->EventArguments;
        $discussionComment = (array) $eventData['Comment'];
        $mainBody = $discussionComment['Body'];

        if ($this->hasSecurityWords($mainBody)) {
            $this->logPostForModeration('Comment', $discussionComment);
        }
    }

    /**
     * Check new discussion post for security words.
     *
     * @param postController $sender The post controller.
     */
    public function postController_afterDiscussionSave_handler($sender) {
        $eventData =& $sender->EventArguments;
        $discussion = (array) $eventData['Discussion'];
        $mainBody = $discussion['Body'];
        $mainTitle = $discussion['Name'];

        if ($this->hasSecurityWords($mainTitle)
        || $this->hasSecurityWords($mainBody)) {
            $this->logPostForModeration('Discussion', $discussion);
        }
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
            ),
            'Plugins.securitywords.notifyuser' => array(
                'LabelCode' => 'Notify user that post is in moderation queue.',
                'Control' => 'CheckBox',
                'Description' => 'Notify users when their posts are sent to the
                                  moderation queue due to the use of "security
                                  words."'
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
            'Plugins.securitywords.words' => 'area51;mkultra;911',
            'Plugins.securitywords.notifyuser' => false
        ));
    }
}
