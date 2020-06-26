<?php
/**
 * VanillaAnalytics plugin.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */

use Garden\Container\Container;
use Vanilla\Analytics\KeenClient;

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
     * Track adding articles in Knowledge.
     *
     * @param array $article
     */
    public function afterArticleCreate_handler(array $article) {
        AnalyticsTracker::getInstance()->trackEvent(
            "article",
            "article_add",
            ["article" => AnalyticsData::filterArticle($article)]
        );
    }

    /**
     * Track when an article receives a reaction.
     *
     * @param array $article
     * @param mixed $reaction
     */
    public function afterArticleReact_handler(array $article, string $reaction) {
        AnalyticsTracker::getInstance()->trackEvent(
            "article_reaction",
            "article_reaction_add",
            [
                "article" => AnalyticsData::filterArticle($article),
                "reaction" => $reaction
            ]
        );
    }

    /**
     * Track articles updates in Knowledge.
     *
     * @param array $article
     */
    public function afterArticleUpdate_handler(array $article) {
        AnalyticsTracker::getInstance()->trackEvent(
            "article_modify",
            "article_edit",
            ["article" => AnalyticsData::filterArticle($article)]
        );
    }

    /**
     * Update the container configuration.
     *
     * @param Container $container
     */
    public function container_init(Container $container) {
        $container->rule(\Vanilla\Analytics\Client::class)
            ->setClass(KeenClient::class);
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
            'permission' => 'Analytics.Data.View',
            'section' => 'Analytics',
            'title' => 'Analytics',
            'description' => 'Visualize Your Community',
            'url' => '/analytics/dashboard/traffic'
        ];

        $nav->registerSection($section);

        $sectionModel = new AnalyticsSection();
        $analyticsDashboardModel = new AnalyticsDashboard();

        $nav->addGroupToSection('Analytics', t('Analytics'), 'analytics');

        $nav->addLinkToSectionIf(
            'Analytics.Data.View',
            'Analytics',
            t('My Analytics'),
            'analytics/dashboard/'.AnalyticsDashboard::DASHBOARD_PERSONAL,
            'analytics.my-analytics'
        );


        foreach ($sectionModel->getDefaults() as $section) {
            foreach ($section->getDashboards() as $dashboard) {
                $nav->addLinkToSectionIf(
                    'Analytics.Data.View',
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
        AnalyticsTracker::getInstance()->trackEvent('registration', 'registration_success');
    }


    /**
     * Hook into requests for /settings/analyticstick.json.
     *
     * @param Gdn_Statistics $sender
     * @param array $args
     */
    public function gdn_statistics_analyticsTick_handler(Gdn_Statistics $sender, array $args) {
        // Avoid potentially dodging the cache during a GET request.
        if (Gdn::request()->getMethod() === Gdn_Request::METHOD_GET) {
            return;
        }

        // Nuke existing UUIDs in user rows.
        if (Gdn::session()->isValid() && array_key_exists('UUID', Gdn::session()->User->Attributes)) {
            Gdn::userModel()->saveAttribute(Gdn::session()->UserID, 'UUID', null);
        }

        // Send some information that will be helpful for page view tracking.
        $controller = Gdn::controller();
        if ($controller) {
            $controller->setData('clientIP', anonymizeIP(Gdn::request()->ipAddress()));
            $controller->setData('dateTime', AnalyticsData::getDateTime());
        }

        AnalyticsTracker::getInstance()->refreshCookies();
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
    public function qnaPlugin_afterAccepted_handler($sender, $args) {
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

    /**
     * Hook in when a new user session is detected.
     */
    public function userModel_visit_handler() {
        // Make sure all identifiers are up-to-date.
        AnalyticsTracker::getInstance()->resetSessionIDs();
    }
}
