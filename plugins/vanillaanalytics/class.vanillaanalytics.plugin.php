<?php
/**
 * VanillaAnalytics plugin.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */

$PluginInfo['vanillaanalytics'] = [
    'Name' => 'Vanilla Analytics',
    'Description' => 'Track important trends on your forum and chart them in a customizable dashboard.',
    'Version' => '1.0.4',
    'RequiredApplications' => array('Vanilla' => '2.2.103'),
    'Author' => 'Ryan Perry',
    'AuthorEmail' => 'ryan.p@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.org/profile/initvector'
];

/**
 * Facilitate the tracking of events from Vanilla to one (or more) analytics services.
 */
class VanillaAnalyticsPlugin extends Gdn_Plugin {

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
        if (c('VanillaAnalytics.DisableDashboard', false)) {
            return;
        }

        $sectionModel = new AnalyticsSection();
        $analyticsDashboardModel = new AnalyticsDashboard();

        Logger::event('analytics_menu', Logger::INFO, 'Sections', $sectionModel->getDefaults());
    }

    /**
     * Adds items to dashboard menu.
     *
     * @param DashboardNavModule $sender
     */
    public function dashboardNavModule_init_handler($sender) {
        if (c('VanillaAnalytics.DisableDashboard', false)) {
            return;
        }

        /** @var DashboardNavModule $nav */
        $nav = $sender;

        $section = [
            'permission' => 'Garden.Settings.Manage',
            'section' => 'Analytics',
            'title' => 'Analytics',
            'description' => 'Visualize Your Community',
            'url' => '/analytics/dashboard/traffic'
        ];

        $nav->registerSection($section);

        $sectionModel = new AnalyticsSection();
        $analyticsDashboardModel = new AnalyticsDashboard();

        Logger::event('analytics_menu', Logger::INFO, 'Sections', $sectionModel->getDefaults());
        $nav->addGroupToSection('Analytics', t('Analytics'), 'analytics');

        $nav->addLinkToSectionIf(
            'Garden.Settings.Manage',
            'Analytics',
            t('My Analytics'),
            'analytics/dashboard/'.AnalyticsDashboard::DASHBOARD_PERSONAL,
            'analytics.my-analytics'
        );


        foreach ($sectionModel->getDefaults() as $section) {
            foreach ($section->getDashboards() as $dashboard) {
                $nav->addLinkToSectionIf(
                    'Garden.Settings.Manage',
                    'Analytics',
                    t($dashboard->getTitle()),
                    'analytics/dashboard/'.urlencode($dashboard->dashboardID),
                    "analytics.{$dashboard->dashboardID}"
                );
            }
        }
    }

    /**
     * Hook in before a view is rendered.
     *
     * @param $sender Controller instance
     */
    public function base_render_before($sender) {
        // Give analytics trackers the ability to add JavaScript files.
        AnalyticsTracker::getInstance()->addJsFiles($sender);

        // Allow trackers to add to values in a page's gdn.meta JavaScript array.
        AnalyticsTracker::getInstance()->addDefinitions($sender);

        // Allow trackers to add CSS files to the page.
        AnalyticsTracker::getInstance()->addCssFiles($sender);
    }

    /**
     * Track when a comment is saved.  This includes inserts and updates.
     *
     * @param CommentModel $sender Current instance of CommentModel
     * @param array $args Event arguments, passed from CommentModel, specifically for the event.
     */
    public function commentModel_afterSaveComment_handler($sender, $args) {
        $type = val('Insert', $args) ? 'comment_add' : 'comment_edit';
        $collection = ($type == 'comment_add') ? 'post' : 'post_modify';

        $data = AnalyticsData::getComment(val('CommentID', $args), $type);

        AnalyticsTracker::getInstance()->trackEvent($collection, $type, $data);
    }

    /**
     * Track when a comment is deleted.
     *
     * @param CommentModel $sender Current instance of CommentModel
     * @param array $args Event arguments, passed from CommentModel, specifically for the event.
     */
    public function commentModel_deleteComment_handler($sender, $args) {
        $data = AnalyticsData::getComment(val('CommentID', $args));

        AnalyticsTracker::getInstance()->trackEvent('post_modify', 'comment_delete', $data);
    }

    /**
     * Track when a discussion is saved.  This can be used to record an event for inserts or edits.
     *
     * @param DiscussionModel $sender Current instance of DiscussionModel
     * @param array $args Event arguments, passed from DiscussionModel, specifically for the event.
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, $args) {
        $type = val('Insert', $args) ? 'discussion_add' : 'discussion_edit';
        $collection = ($type == 'discussion_add') ? 'post' : 'post_modify';

        $data = AnalyticsData::getDiscussion(val('DiscussionID', $args));

        AnalyticsTracker::getInstance()->trackEvent($collection, $type, $data);
    }

    /**
     * Track when a discussion is deleted.
     *
     * @param DiscussionModel $sender Current instance of DiscussionModel
     * @param array $args Event arguments, passed from DiscussionModel, specifically for the event.
     */
    public function discussionModel_deleteDiscussion_handler($sender, $args) {
        $data = AnalyticsData::getDiscussion(val('DiscussionID', $args));

        AnalyticsTracker::getInstance()->trackEvent('post_modify', 'discussion_delete', $data);
    }

    /**
     * Track when a user successfully registers for the site.
     *
     * @param UserModel $sender Current instance of EntryController
     * @param array $args Event arguments, passed from EntryController, specifically for the event.
     */
    public function userModel_afterRegister_handler($sender, $args) {
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
        Gdn::userModel()->saveAttribute($args['UserID'], 'UUID', $uuid);

        AnalyticsTracker::getInstance()->trackEvent('registration', 'registration_success');
    }

    /**
     * Hook in early, before a request is dispatched to the target controller.
     *
     * @param Gdn_Dispatcher $sender
     * @param array $args
     */
    public function gdn_dispatcher_beforeDispatch_handler($sender, $args) {
        $setCookie = true;
        $trackingCookieRaw = Gdn::session()->getCookie('-vA');

        // Determine if we need to set a tracking cookie.
        if ($trackingCookie = @json_decode($trackingCookieRaw)) {
            $uuid = val('uuid', $trackingCookie);
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
     * @param Gdn_Session $sender
     * @param array $args
     */
    public function gdn_session_start_handler($sender, $args) {
        AnalyticsTracker::getInstance()->trackEvent('session', 'session_start');
    }

    /**
     * Track when a user signs out of the site.
     *
     * @todo Add siteDuration (time between sign-in and sign-out)
     * @param Gdn_Session $sender Current instance of EntryController
     * @param array $args Event arguments, passed from EntryController, specifically for the event.
     */
    public function gdn_session_end_handler($sender, $args) {
        AnalyticsTracker::getInstance()->trackEvent('session', 'session_end');
    }

    /**
     * Track when an answer is accepted on a question.
     *
     * @param QnAPlugin $sender Instance of the QnAPlugin.
     * @param array $args Arguments for the current event.
     */
    public function qnaPlugin_AfterAccepted_handler($sender, $args) {
        $activity = val('Activity', $args);
        if ($activity) {
            $data = AnalyticsData::getComment(val('RecordID', $activity), false);

            AnalyticsTracker::getInstance()->trackEvent('qna', 'answer_accepted', $data);
        }
    }

    /**
     * Track when a user performs a reaction.
     *
     * @param ReactionsPlugin $sender Current instance of ReactionsPlugin
     * @param array $args Event arguments, passed from ReactionsPlugin or ReactionModel, specifically for the event.
     */
    public function reactionsPlugin_reaction_handler($sender, $args) {
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
                'reactionClass' => val('Class', $reactionType, null),
                'reactionType' => val('Name', $reactionType, null),
                'recordType' => $recordType,
                'recordID' => (int)$recordID,
                'recordUser' => $recordUser,
                'urlCode' => strtolower($urlCode),
                'tagID' => (int)val('TagID', $reactionData),
                'total' => (int)val('Total', $reactionData)
            ]
        ];

        $event = val('Total', $reactionData) > 0 ? 'reaction_add' : 'reaction_delete';

        AnalyticsTracker::getInstance()->trackEvent('reaction', $event, $data);
    }

    /**
     * Track when a user receive points.
     *
     * @param UserModel $sender Sending instance
     * @param array $args Event's arguments
     */
    public function userModel_givePoints_handler($sender, $args) {

        $timestamp = val('Timestamp', $args, false);
        if (!$timestamp) {
            $time = 'now';
        } else {
            $tmp = new DateTime();
            $tmp->setTimestamp($timestamp);
            $time = $tmp->format('Y-m-d H:i:s');
        }

        $givenPoints = (int)val('GivenPoints', $args, 0);
        $data = [
            'point' => [
                'categoryID' => val('CategoryID', $args, 0),
                'source' => val('Source', $args, null),
                'user' => AnalyticsData::getUser($args['UserID']),
                'given' => [
                    'points' => $givenPoints,
                    'date' => AnalyticsData::getDateTime($time),
                ],
            ]
        ];

        $event = $givenPoints > 0 ? 'user_point_add' : 'user_point_remove';

        AnalyticsTracker::getInstance()->trackEvent('point', $event, $data);
    }

    /**
     * Setup routine to run when the plug-in is enabled.
     *
     * @throws Gdn_UserException
     */
    public function setup() {
        // On enable, the plug-in's classes aren't available in the autoloader.
        $modelPath = PATH_PLUGINS . '/vanillaanalytics/models';
        require_once("{$modelPath}/interface.trackerinterface.php");
        require_once("{$modelPath}/class.analyticsdata.php");
        require_once("{$modelPath}/class.analyticstracker.php");
        require_once("{$modelPath}/class.analyticswidget.php");
        require_once("{$modelPath}/class.keenioclient.php");
        require_once("{$modelPath}/class.keenioquery.php");
        require_once("{$modelPath}/class.keeniotracker.php");

        $this->structure();
    }

    /**
     * Perform any necessary database or configuration updates.
     *
     * @throws Gdn_UserException
     */
    public function structure() {
        Gdn::structure()
            ->table('AnalyticsDashboardWidget')
            ->column('DashboardID', 'varchar(32)', null, ['index', 'index.DashboardSort'])
            ->column('WidgetID', 'varchar(32)', false)
            ->column('Sort', 'int', '0', ['index', 'index.DashboardSort'])
            ->column('InsertUserID', 'int', false, 'index')
            ->column('DateInserted', 'datetime')
            ->set();

        AnalyticsTracker::getInstance()->setup();
    }
}
