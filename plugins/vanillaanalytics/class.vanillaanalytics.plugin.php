<?php
/**
 * VanillaAnalytics plugin.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */

$PluginInfo['vanillaanalytics'] = array(
    'Name' => 'Vanilla Analytics',
    'Description' => 'Track important trends on your forum and chart them in a customizable dashboard.',
    'Version' => '1.0.2',
    'RequiredApplications' => array('Vanilla' => '2.2.103'),
    'Author' => 'Ryan Perry',
    'AuthorEmail' => 'ryan.p@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.org/profile/initvector'
);

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

        $sender->EventArguments['SideMenu']->addItem(
            'analytics',
            t('Analytics'),
            'Garden.Settings.Manage',
            ['After' => 'Dashboard', 'class' => 'Analytics']
        );

        $personalDashboard = $analyticsDashboardModel->getUserDashboardWidgets(AnalyticsDashboard::DASHBOARD_PERSONAL);
        if (count($personalDashboard) > 0) {
            $sender->EventArguments['SideMenu']->addLink(
                'analytics',
                t('My Dashboard'),
                "settings/analytics/dashboard/" . AnalyticsDashboard::DASHBOARD_PERSONAL,
                'Garden.Settings.Manage'
            );
        }

        foreach ($sectionModel->getDefaults() as $section) {
            foreach ($section->getDashboards() as $dashboard) {
                $sender->EventArguments['SideMenu']->addLink(
                    'analytics',
                    t($dashboard->getTitle()),
                    "settings/analytics/dashboard/{$dashboard->dashboardID}",
                    'Garden.Settings.Manage'
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
     * @param $sender Current instance of CommentModel
     * @param $args Event arguments, passed from CommentModel, specifically for the event.
     */
    public function commentModel_afterSaveComment_handler($sender, &$args) {
        $type = val('Insert', $args) ? 'comment_add' : 'comment_edit';
        $collection = ($type == 'comment_add') ? 'post' : 'post_modify';

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
     * Save a widget to the current user's personal dashboard.
     *
     * @param Gdn_Controller $sender
     * @param array $requestArgs
     */
    public function controller_bookmarkWidget($sender, $requestArgs) {
        list($widgetID, $dashboardID) = $requestArgs;

        $dashboardModel = new AnalyticsDashboard();
        $widgetModel = new AnalyticsWidget();
        $widget = $widgetModel->getID($widgetID);
        $userID = Gdn::session()->UserID;

        if ($widget) {
            if ($widget->isBookmarked()) {
                $dashboardModel->removeWidget(
                    $widgetID,
                    AnalyticsDashboard::DASHBOARD_PERSONAL,
                    $userID
                );
                $bookmarked = false;

                $sender->jsonTarget(
                    "#analytics_widget_{$widgetID}",
                    'removeAnalyticsWidget',
                    'Callback'
                );
                $sender->informMessage(t('Removed widget bookmark'));
            } else {
                $dashboardModel->addWidget(
                    $widgetID,
                    AnalyticsDashboard::DASHBOARD_PERSONAL,
                    $userID
                );
                $bookmarked = true;

                $sender->informMessage(t('Bookmarked widget'));
            }

            $html = anchor(
                t('Bookmark'),
                "/settings/analytics/bookmarkwidget/{$widgetID}",
                'Hijack bookmark'.($bookmarked ? ' bookmarked' : ''),
                array('title' => $widget->getTitle())
            );
            $sender->jsonTarget('!element', $html, 'ReplaceWith');
        } else {
            $sender->informMessage(t('Invalid widget ID'));
        }

        $sender->deliveryMethod(DELIVERY_METHOD_JSON);
        $sender->deliveryTYpe(DELIVERY_TYPE_MESSAGE);
        $sender->render('Blank', 'Utility');
    }

    /**
     * Handle requests for the analytics index in Vanilla's dashboard.
     *
     * @param Gdn_Controller $sender
     */
    public function controller_index($sender) {
        redirect('settings');
    }

    /**
     * Generate data for leaderboards.
     */
    public function controller_leaderboard($sender, $requestArgs) {
        list($widget, $size) = $requestArgs;

        if (empty($widget)) {
            throw new Gdn_UserException('Leaderboard widget required.');
        }

        $size = (int)$size;
        $maxSize = 100;

        if ($size < 1) {
            $size = 10;
        } elseif ($size > $maxSize) {
            $size = $maxSize;
        }

        $defaultWidgets = AnalyticsTracker::getInstance()->getDefaultWidgets();
        $leaderboard = val($widget, $defaultWidgets);
        if (!$leaderboard || $leaderboard->getType() !== 'leaderboard') {
            throw new Gdn_UserException('Invalid leaderboard widget.');
        }

        $sender->title($leaderboard->getTitle());
        $query = val('query', $leaderboard->getData());
        if (!$query) {
            throw new Gdn_UserException('No query available.');
        }

        $response = $query->setTimeframeRelative('this', 30, 'days')->exec();
        if (empty($response)) {
            throw new Gdn_UserException('An error was encountered while querying data.');
        }
        $result = $response->result;

        $detectTypes = [
            'user.userID',
            'discussion.discussionID'
        ];
        $typeID = false;

        $firstResult = current($result);
        foreach ($detectTypes as $currentType) {
            if ($firstResult->$currentType) {
                $typeID = $currentType;
                break;
            }
        }
        if (!$typeID) {
            throw new Gdn_UserException('Unable to determine result type of query.');
        }

        usort($result, function($r1, $r2) use ($typeID) {
            if ($r1->result === $r2->result) {
                return 0;
            } else {
                return $r1->result > $r2->result ? -1 : 1;
            }
        });

        $resultIndexed = [];
        switch ($typeID) {
            case 'discussion.discussionID':
                $recordModel = new DiscussionModel();
                $recordUrl = '/discussion/%d';
                $titleAttribute = 'Name';
                break;
            case 'user.userID':
                $recordModel = Gdn::userModel();
                $recordUrl = '/profile?UserID=%d';
                $titleAttribute = 'Name';
                break;
            default:
                throw new Gdn_UserException('Invalid type ID.');
        }
        foreach ($result as $currentResult) {
            $recordID = $currentResult->$typeID;
            $record = $recordModel->getID($recordID, DATASET_TYPE_ARRAY);
            $record['LeaderRecord'] = [
                'ID' => $recordID,
                'Url' => sprintf($recordUrl, $recordID),
                'Title' =>$record[$titleAttribute]
            ];
            $resultIndexed[$recordID] = $record;
        }

        $sender->setData(
            'Leaderboard',
            array_slice($resultIndexed, 0, $maxSize, true)
        );
        $sender->render($sender->fetchViewLocation('leaderboard', '', 'plugins/vanillaanalytics'));
    }

    /**
     * Handle requests for an analytics dashboard.
     *
     * @param Gdn_Controller $sender
     * @param $requestArgs
     */
    public function controller_dashboard($sender, $requestArgs) {
        list($dashboardID) = $requestArgs;

        if (empty($dashboardID)) {
            redirect('settings');
        }
        $sender->addCssFile('vendors.min.css', 'plugins/vanillaanalytics');

        $sender->addJsFile('vendors/d3.min.js', 'plugins/vanillaanalytics');
        $sender->addJsFile('vendors/c3.min.js', 'plugins/vanillaanalytics');
        $sender->addJsFile('dashboard.min.js', 'plugins/vanillaanalytics');
        $sender->addJsFile('analyticsdashboard.min.js', 'plugins/vanillaanalytics');
        $sender->addJsFile('analyticswidget.min.js', 'plugins/vanillaanalytics');
        $sender->addJsFile('analyticstoolbar.min.js', 'plugins/vanillaanalytics');
        $sender->addJsFile('vendors/jquery-ui.min.js', 'plugins/vanillaanalytics');
        $sender->addJsFile('vendors/moment.min.js', 'plugins/vanillaanalytics');
        $sender->addJsFile('vendors/jquery.comiseo.daterangepicker.min.js', 'plugins/vanillaanalytics');

        $dashboardModel = new AnalyticsDashboard();
        $dashboard = $dashboardModel->getID($dashboardID);

        if ($dashboard) {
            $dashboardModel->render($sender, $dashboard);
        } else {
            redirect('settings');
        }
    }

    /**
     * Sort the widgets in a custom dashboard.
     *
     * @param Gdn_Controller $sender
     * @param array $requestArgs
     * @throws
     */
    public function controller_dashboardSort($sender, $requestArgs) {
        if (!Gdn::request()->isPostBack()) {
            throw new Gdn_UserException('POST required.', 403);
        }

        $transientKey = Gdn::request()->getValueFrom(Gdn_Request::INPUT_POST, 'TransientKey', false);

        // If this isn't a postback then return false if there isn't a transient key.
        if (!$transientKey) {
            throw new Gdn_UserException('No CSRF token provided.', 403);
        }

        if (!Gdn::session()->validateTransientKey($transientKey)) {
            throw new Gdn_UserException('The CSRF token is invalid.', 403);
        }

        list($dashboardID) = $requestArgs;
        $success = true;
        $widgets = Gdn::request()->getValueFrom(Gdn_Request::INPUT_POST, 'Widgets', []);

        foreach ($widgets as $widgetID => $position) {
            try {
                Gdn::sql()->update(
                    'AnalyticsDashboardWidget',
                    ['Sort' => $position],
                    [
                        'DashboardID' => $dashboardID,
                        'WidgetID' => $widgetID
                    ]
                )->put();
            } catch (Exception $e) {
                $success = false;
            }
        }

        $sender->setData('DashboardID', $dashboardID);
        $sender->setData('Widgets', $widgets);
        $sender->setData('Success', $success);

        $sender->deliveryType(DELIVERY_TYPE_DATA);
        $sender->deliveryMethod(DELIVERY_METHOD_JSON);
        $sender->render();
    }

    /**
     * Track when a discussion is saved.  This can be used to record an event for inserts or edits.
     *
     * @param $sender Current instance of DiscussionModel
     * @param $args Event arguments, passed from DiscussionModel, specifically for the event.
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, &$args) {
        $type = val('Insert', $args) ? 'discussion_add' : 'discussion_edit';
        $collection = ($type == 'discussion_add') ? 'post' : 'post_modify';

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
    public function userModel_afterRegister_handler($sender, &$args) {
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
     * @param $sender
     * @param $args
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
     * Add our primary analytics pages.
     *
     * @param Gdn_Controller $sender An instance of the settings controller.
     * @param string $entityType The resource type (e.g. dashboard)
     * @param string $entityID The unique identifier for the resource
     */
    public function settingsController_analytics_create($sender, $entityType = false, $entityID = false) {
        $sender->permission('Garden.Settings.Manage');

        $this->dispatch($sender, $sender->RequestArgs);
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
