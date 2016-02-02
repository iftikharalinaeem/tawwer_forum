<?php
/**
 * VanillaAnalytics plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Analytics
 */

$PluginInfo['VanillaAnalytics'] = array(
    'Name'                 => 'Vanilla Analytics',
    'Description'          => 'Support for transmitting events to analytics services.',
    'Version'              => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'Author'               => 'Ryan Perry',
    'AuthorEmail'          => 'ryan.p@vanillaforums.com',
    'AuthorUrl'            => 'http://vanillaforums.org/profile/initvector'
);

/**
 * Facilitate the tracking of events from Vanilla to one (or more) analytics services.
 */
class VanillaAnalytics extends Gdn_Plugin {

    /**
     * Track the generic 404 response.
     */
    public function gdn_dispatcher_notFound_handler() {
        AnalyticsTracker::getInstance()->trackEvent('error', 'error_notfound');
    }

    /**
     * Adds items to dashboard menu.
     *
     * @param object $sender DashboardController.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $sender->EventArguments['SideMenu']->addLink(
            'Dashboard',
            T('Analytics'),
            'settings/analytics',
            'Garden.Settings.Manage'
        );
    }

    /**
     * Hook in before a view is rendered.
     *
     * @param $sender Controller instance
     */
    public function base_render_before($sender) {
        // Give analytics trackers the ability to add JavaScript files
        AnalyticsTracker::getInstance()->addJsFiles($sender);

        // Allow trackers to add to values in a page's gdn.meta JavaScript array
        AnalyticsTracker::getInstance()->addDefinitions($sender);
    }

    /**
     * Track when a comment is saved.  This includes inserts and updates.
     *
     * @param $sender Current instance of CommentModel
     * @param $args Event arguments, passed from CommentModel, specifically for the event.
     */
    public function commentModel_afterSaveComment_handler($sender, &$args) {
        $type       = val('Insert', $args) ? 'comment_add' : 'comment_edit';
        $collection = $type == 'comment_add' ? 'post' : 'post_modify';

        $data = AnalyticsData::getComment(val('CommentID', $args), $type);

        AnalyticsTracker::getInstance()->trackEvent($collection, $type, $data);
    }

    /**
     * Track when a comment is deleted.
     *
     * @param $sender Current instance of CommentModel
     * @param $args Event arguments, passed from CommentModel, specifically for the event.
     */
    public function commentModel_deleteComment_handler($sender, &$args) {
        $data = AnalyticsData::getComment(val('CommentID', $args));

        AnalyticsTracker::getInstance()->trackEvent('post_modify', 'comment_delete', $data);
    }

    /**
     * Track when a discussion is saved.  This can be used to record an event for inserts or edits.
     *
     * @param $sender Current instance of DiscussionModel
     * @param $args Event arguments, passed from DiscussionModel, specifically for the event.
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, &$args) {
        $type = val('Insert', $args) ? 'discussion_add' : 'discussion_edit';
        $collection = $type == 'discussion_add' ? 'post' : 'post_modify';

        $data = AnalyticsData::getDiscussion(val('DiscussionID', $args));

        AnalyticsTracker::getInstance()->trackEvent($collection, $type, $data);
    }

    /**
     * Track when a discussion is deleted.
     *
     * @param $sender Current instance of DiscussionModel
     * @param $args Event arguments, passed from DiscussionModel, specifically for the event.
     */
    public function discussionModel_deleteDiscussion_handler($sender, &$args) {
        $data = AnalyticsData::getDiscussion(val('DiscussionID', $args));

        AnalyticsTracker::getInstance()->trackEvent('post_modify', 'discussion_delete', $data);
    }

    /**
     * Track when a user successfully registers for the site.
     *
     * @param $sender Current instance of EntryController
     * @param $args Event arguments, passed from EntryController, specifically for the event.
     */
    public function entryController_registrationSuccessful_handler($sender, &$args) {
        $uuid = null;

        // Fetch our tracking cookie.
        $cookieIDsRaw = Gdn::session()->getCookie('-vA', false);

        // Grab the UUID from our cookie data, if available.
        if ($cookieIDs = @json_decode($cookieIDsRaw)) {
            $uuid = val('uuid', $cookieIDs);
        }

        // If we weren't able to recover a UUID from the tracking cookie, generate a enw one.
        if (empty($uuid)) {
            $uuid = AnalyticsData::uuid();
        }

        // Save the new user's UUID attribute
        Gdn::userModel()->saveAttribute(
            Gdn::session()->UserID,
            'UUID',
            $uuid
        );

        AnalyticsTracker::getInstance()->trackEvent('registration', 'registration_success');
    }

    /**
     * Hook in early, before a request is dispatched to the target controller.
     *
     * @param $sender
     * @param $args
     */
    public function gdn_dispatcher_beforeDispatch_handler($sender, $args) {
        $setCookie = true;
        $trackingCookieRaw = Gdn::session()->getCookie('-vA');

        // Determine if we need to set a tracking cookie.
        if ($trackingCookie = @json_decode($trackingCookieRaw)) {
            $uuid      = val('uuid', $trackingCookie);
            $sessionID = val('sessionID', $trackingCookie);

            /**
             * Don't send the cookie to the user again if they meet the following:
             * 1. A UUID and session ID are already set
             * 2. The user isn't logged in or, if they are, their cookie's UUID matches the one we have on record
             */
            if ($uuid && $sessionID && (!Gdn::session()->isValid() || AnalyticsData::getUserUuid() == $uuid)) {
                $setCookie = false;
            }
        }

        if ($setCookie) {
            Gdn::session()->setCookie(
                '-vA',
                json_encode(AnalyticsTracker::getInstance()->trackingIDs()),
                strtotime('+2 years')
            );
        }
    }

    /**
     * Track when a user signs into the site.
     *
     * @param $sender Current instance of EntryController
     * @param $args Event arguments, passed from EntryController, specifically for the event.
     */
    public function gdn_session_start_handler($sender, $args) {
        AnalyticsTracker::getInstance()->trackEvent('session', 'session_start');
    }

    /**
     * Track when a user signs out of the site.
     *
     * @todo Add siteDuration (time between sign-in and sign-out)
     * @param $sender Current instance of EntryController
     * @param $args Event arguments, passed from EntryController, specifically for the event.
     */
    public function gdn_session_end_handler($sender, $args) {
        AnalyticsTracker::getInstance()->trackEvent('session', 'session_end');
    }

    /**
     * Track when a user performs a reaction.
     *
     * @param $sender Current instance of ReactionsPlugin
     * @param $args Event arguments, passed from ReactionsPlugin or ReactionModel, specifically for the event.
     */
    public function reactionsPlugin_reaction_handler($sender, &$args) {
        $reactionData = val('ReactionData', $args);

        $discussionID = 0;
        $recordID = val('RecordID', $args);
        $recordType = strtolower(val('RecordType', $reactionData));
        $recordUser = AnalyticsData::getUser(0);
        $urlCode = val('ReactionUrlCode', $args);

        $reactionType = ReactionModel::reactionTypes($urlCode);

        switch ($recordType) {
            case 'comment':
                $commentModel = new CommentModel();
                $commentDetails = $commentModel->getID($recordID);
                if ($commentDetails) {
                    $discussionID = $commentDetails->DiscussionID;
                    $recordUser = AnalyticsData::getUser($commentDetails->InsertUserID);
                }
                break;
            case 'discussion':
                $discussionModel = new DiscussionModel();
                $discussionDetails = $discussionModel->getID($recordID);
                if ($discussionDetails) {
                    $discussionID = $discussionDetails->DiscussionID;
                    $recordUser = AnalyticsData::getUser($discussionDetails->InsertUserID);
                }
                break;
            default:
        }

        // Grabbing the relevant information from the ReactionData event argument
        $data = [
            'reaction' => [
                'discussionID' => (int)$discussionID,
                'reactionType' => val('Name', $reactionType, null),
                'recordType'   => $recordType,
                'recordID'     => (int)$recordID,
                'recordUser'   => $recordUser,
                'urlCode'      => strtolower($urlCode),
                'tagID'        => (int)val('TagID', $reactionData),
                'total'        => (int)val('Total', $reactionData)
            ]
        ];

        $event = val('Total', $reactionData) > 0 ? 'reaction_add' : 'reaction_delete';

        AnalyticsTracker::getInstance()->trackEvent('reaction', $event, $data);
    }

    /**
     * Add our primary analytics page.
     * @param $sender
     */
    public function settingsController_analytics_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->addSideMenu();
        $sender->setData('Title', t('Vanilla Analytics'));
        $sender->render(
            'analytics',
            false,
            'plugins/vanillaanalytics'
        );
    }

    /**
     * Setup routine to run when the plug-in is enabled.
     *
     * @throws Gdn_UserException
     */
    public function setup() {
        $this->structure();
    }

    /**
     * @throws Gdn_UserException
     */
    public function structure() {
        analyticsTracker::getInstance()->setup();
    }
}
