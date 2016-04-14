<?php
/**
 * KeenIOTracker class file.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */

/**
 * Responsible for managing communication with the keen.io service.
 */
class KeenIOTracker implements TrackerInterface {

    /**
     * Instance of KeenIOClient.
     * @var KeenIOClient
     */
    protected $client;

    /**
     * @var array The widgets and their settings.
     */
    protected $widgets = [
        'total-active-users' => [
            'title' => 'Active Users',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric'
        ],
        'total-pageviews' => [
            'title' => 'Page Views',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric'
        ],
        'total-visits' => [
            'title' => 'Visits',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric'
        ],
        'visits' => [
            'title' => 'Visits',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'chart',
            'chart' => ['labels' => ['Visits']],
            'support' => 'cat01'
        ],
        'total-discussions' => [
            'title' => 'Discussions',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
            'support' => 'cat01'
        ],
        'total-comments' => [
            'title' => 'Comments',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
            'support' => 'cat01'
        ],
        'total-contributors' => [
            'title' => 'Contributors',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
            'support' => 'cat01'
        ],
        'pageviews' => [
            'title' => 'Page Views',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'chart'
        ],
        'active-users' => [
            'title' => 'Active Users',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart'
        ],
        'visits-by-role-type' => [
            'title' => 'Unique Visits By Role Type',
            'rank' => AnalyticsWidget::LARGE_WIDGET_RANK,
            'type' => 'chart',
            'chart' => ['chartType' => 'area']
        ],
        'discussions' => [
            'title' => 'Discussions',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'support' => 'cat01'
        ],
        'comments' => [
            'title' => 'Comments',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'support' => 'cat01'
        ],
        'posts' => [
            'title' => 'Post',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart',
            'support' => 'cat01'
        ],
        'posts-by-type' => [
            'title' => 'Posts By Type',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'chart' => [
                'labelMapping' => [
                    'discussion_add' => 'Discussions',
                    'comment_add' => 'Comments'
                ],
                'chartType' => 'area'
            ],
            'support' => 'cat01'
        ],
        'posts-by-category' => [
            'title' => 'Posts By Category',
            'rank' => AnalyticsWidget::LARGE_WIDGET_RANK,
            'chart' => ['chartType' => 'area'],
            'support' => 'cat01'
        ],
        'posts-by-role-type' => [
            'title' => 'Posts By Role Type',
            'rank' => AnalyticsWidget::LARGE_WIDGET_RANK,
            'chart' => ['chartType' => 'area'],
            'support' => 'cat01'
        ],
        'posts-per-user' => [
            'title' => 'Posts Per User',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart',
            'chart' => ['chartType' => 'area'],
            'callback' => 'divideResult'
        ],
        'contributors' => [
            'title' => 'Contributors',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart',
            'support' => 'cat01'
        ],
        'contributors-by-category' => [
            'title' => 'Contributors By Category',
            'rank' => AnalyticsWidget::LARGE_WIDGET_RANK,
            'type' => 'chart',
            'chart' => ['chartType' => 'area'],
            'support' => 'cat01'
        ],
        'contributors-by-role-type' => [
            'title' => 'Contributors By Role Type',
            'rank' => AnalyticsWidget::LARGE_WIDGET_RANK,
            'type' => 'chart',
            'chart' => ['chartType' => 'area'],
            'support' => 'cat01'
        ],
        'comments-per-discussion' => [
            'title' => 'Comments Per Discussion',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart',
            'chart' => ['chartType' => 'area'],
            'callback' => 'divideResult'
        ],
        'registrations' => [
            'title' => 'New Users',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart'
        ]
    ];

    /**
     * Constructor.
     */
    public function __construct() {
        $this->client = new KeenIOClient(
            'https://api.keen.io/{version}/',
            [
                'orgID' => c('VanillaAnalytics.KeenIO.OrgID'),
                'orgKey' => c('VanillaAnalytics.KeenIO.OrgKey'),
                'projectID' => c('VanillaAnalytics.KeenIO.ProjectID'),
                'readKey' => c('VanillaAnalytics.KeenIO.ReadKey'),
                'writeKey' => c('VanillaAnalytics.KeenIO.WriteKey')
            ]
        );
    }

    /**
     * Builds objects from the widgets in the analytics array. Queries must be appended to the items in the widgets
     * array before objects can be made from them. Use registerQueries() for this.
     *
     * @param string $id The slug-type ID of the widget.
     * @return AnalyticsWidget|null The Analytics widget object.
     */
    protected function buildWidget($id) {
        $widget = val($id, $this->widgets, []);
        if (empty($widget) || !val('query', $widget)) {
            return null;
        }

        if (!$chart = val('chart', $widget, [])) {
            if (val('type', $widget) != 'metric') {
                $chart = ['labels' => val('title', $widget)];
            }
        }

        if (!val('title', $chart)) {
            $chart['title'] = val('title', $widget);
        }

        // Override default chart 'Result' label in c3
        $chart['labelMapping']['Result'] = val('title', $widget);

        $widgetObj = new AnalyticsWidget();
        $widgetObj->setID($id)
            ->setTitle(t(val('title', $widget, '')))
            ->setHandler('KeenIOWidget')
            ->setRank(val('rank', $widget, 1))
            ->setData([
                'chart' => $chart,
                'query' => val('query', $widget)
            ]);

        if ($type = val('type', $widget)) {
            $widgetObj->setType(val('type', $widget));
        }

        if ($support = val('support', $widget)) {
            $widgetObj->addSupport($support);
        }

        if ($callback = val('callback', $widget)) {
            $widgetObj->setCallback($callback);
        }

        return $widgetObj;

    }

    /**
     * Add widget object configurations to the ongoing list.
     *
     * @todo Add Visits
     * @todo Add Visits by Role Type
     * @param array $widgets Incoming array of charts to add to.
     */
    public function addWidgets(array &$widgets) {
        $this->registerQueries();
        foreach($this->widgets as $id => $widget) {
            if ($widgetObj = $this->buildWidget($id)) {
                $widgets[$id] = $widgetObj;
            }
        }
    }


    /**
     * Each valid widget in needs a query associated with it.
     * This builds the KeenIOQueries and adds them to the widgets array.
     */
    protected function registerQueries() {
        /**
         * Metrics
         */

        // Active Users (metric)
        $totalActiveUsersQuery = new KeenIOQuery();
        $totalActiveUsersQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Active Users'))
            ->setEventCollection('page')
            ->setTargetProperty('user.userID')
            ->addFilter([
                'operator' => 'gt',
                'property_name' => 'user.userID',
                'property_value' => 0
            ]);

        $this->widgets['total-active-users']['query'] = $totalActiveUsersQuery;

        // Pageviews (metric)
        $totalPageViewQuery = new KeenIOQuery();
        $totalPageViewQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Page Views'))
            ->setEventCollection('page');
        $this->widgets['total-pageviews']['query'] = $totalPageViewQuery;


        // Visits (metric)
        $totalVisitsQuery = new KeenIOQuery();
        $totalVisitsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Visits'))
            ->setEventCollection('page')
            ->setTargetProperty('user.sessionID');

        $this->widgets['total-visits']['query'] = $totalVisitsQuery;

        // Discussions (metric)
        $totalDiscussionsQuery = new KeenIOQuery();
        $totalDiscussionsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Discussions'))
            ->setEventCollection('post')
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'type',
                'property_value' => 'discussion_add'
            ]);

        $this->widgets['total-discussions']['query'] = $totalDiscussionsQuery;

        // Comments (metric)
        $totalCommentsQuery = new KeenIOQuery();
        $totalCommentsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Comments'))
            ->setEventCollection('post')
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'type',
                'property_value' => 'comment_add'
            ]);

        $this->widgets['total-comments']['query'] = $totalCommentsQuery;

        // Contributors (metric)
        $totalContributorsQuery = new KeenIOQuery();
        $totalContributorsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Contributors'))
            ->setEventCollection('post')
            ->setTargetProperty('user.userID');

        $this->widgets['total-contributors']['query'] = $totalContributorsQuery;

        /**
         * Charts
         */

        // Pageviews (chart)
        $pageViewQuery = new KeenIOQuery();
        $pageViewQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Page Views'))
            ->setEventCollection('page')
            ->setInterval('daily');

        $this->widgets['pageviews']['query'] = $pageViewQuery;

        // Active Users (chart)
        $activeUsersQuery = new KeenIOQuery();
        $activeUsersQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Active Users'))
            ->setEventCollection('page')
            ->setTargetProperty('user.userID')
            ->setInterval('daily')
            ->addFilter([
                'operator' => 'gt',
                'property_name' => 'user.userID',
                'property_value' => 0
            ]);

        $this->widgets['active-users']['query'] = $activeUsersQuery;

        // Visits (chart)
        $visitsQuery = new KeenIOQuery();
        $visitsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Visits'))
            ->setEventCollection('page')
            ->setTargetProperty('user.sessionID')
            ->setInterval('daily');

        $widgets['visits']['query'] = $visitsQuery;

        // Visits by Role Type
        $visitsByRoleTypeQuery = new KeenIOQuery();
        $visitsByRoleTypeQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Visits by Role Type'))
            ->setEventCollection('page')
            ->setTargetProperty('user.sessionID')
            ->setInterval('daily')
            ->setGroupBy('user.roleType');

        $this->widgets['visits-by-role-type']['query'] = $visitsByRoleTypeQuery;

        // Discussions
        $discussionsQuery = new KeenIOQuery();
        $discussionsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Discussions'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'discussion_add'
            ]);

        $this->widgets['discussions']['query'] = $discussionsQuery;

        // Comments
        $commentsQuery = new KeenIOQuery();
        $commentsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Comments'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'comment_add'
            ]);

        $this->widgets['comments']['query'] = $commentsQuery;

        // Posts
        $postsQuery = new KeenIOQuery();
        $postsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Posts'))
            ->setEventCollection('post')
            ->setInterval('daily');

        $this->widgets['posts']['query'] = $postsQuery;

        // Posts by type
        $postsByTypeQuery = new KeenIOQuery();
        $postsByTypeQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Posts By Type'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setGroupBy('type');

        $this->widgets['posts-by-type']['query'] = $postsByTypeQuery;

        // Posts by category
        $postsByCategoryQuery = new KeenIOQuery();
        $postsByCategoryQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Posts By Category'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setGroupBy('categoryAncestors.cat01.name');

        $this->widgets['posts-by-category']['chart']['labelMapping'] = AnalyticsData::getCategoryMap();
        $this->widgets['posts-by-category']['query'] = $postsByCategoryQuery;

        // Posts by role type
        $postsByRoleTypeQuery = new KeenIOQuery();
        $postsByRoleTypeQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Posts By Role Type'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setGroupBy('user.roleType');

        $this->widgets['posts-by-role-type']['query'] = $postsByRoleTypeQuery;

        // Contributors (chart)
        $contributorsQuery = new KeenIOQuery();
        $contributorsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Contributors'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setTargetProperty('user.userID');
        $this->widgets['contributors']['query'] = $contributorsQuery;


        // Contributors by category (chart)
        $contributorsByCategoryQuery = new KeenIOQuery();
        $contributorsByCategoryQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Contributors by Category'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setTargetProperty('user.userID')
            ->setGroupBy('categoryAncestors.cat01.name');

        $this->widgets['contributors-by-category']['query'] = $contributorsByCategoryQuery;

        // Contributors by role type (chart)
        $contributorsByRoleTypeQuery = new KeenIOQuery();
        $contributorsByRoleTypeQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Contributors by Role Type'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setTargetProperty('user.userID')
            ->setGroupBy('user.roleType');

        $this->widgets['contributors-by-role-type']['query'] = $contributorsByRoleTypeQuery;

        // Posts per user (chart)
        $this->widgets['posts-per-user']['query'] = [$postsQuery, $activeUsersQuery];

        // Comments per discussion (chart)
        $this->widgets['comments-per-discussion']['query'] = [$commentsQuery, $discussionsQuery];

        // Registrations
        $registrationsQuery = new KeenIOQuery();
        $registrationsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Registrations'))
            ->setEventCollection('registration')
            ->setInterval('daily');

        $this->widgets['registrations']['query'] = $registrationsQuery;
    }

    /**
     * Add CSS files to the current page.
     *
     * @param Gdn_Controller $controller Instance of the current page's controller.
     * @param bool $inDashboard Is the current page a dashboard page?
     */
    public function addCssFiles(Gdn_Controller $controller, $inDashboard = false) {
    }

    /**
     * Add values to the gdn.meta JavaScript array on the page.
     *
     * @param Gdn_Controller Instance of the current page's controller.
     * @param bool $inDashboard Is the current page a dashboard page?
     */
    public function addDefinitions(Gdn_Controller $controller, $inDashboard = false) {
        $controller->addDefinition('keenio.projectID', $this->client->getProjectID());
        $controller->addDefinition('keenio.writeKey', $this->client->getWriteKey());

        if ($inDashboard) {
            $controller->addDefinition('keenio.readKey', $this->client->getReadKey());
        }
    }

    /**
     * Add JavaScript files to the current page.
     *
     * @param Gdn_Controller Instance of the current page's controller.
     * @param bool $inDashboard Is the current page a dashboard page?
     */
    public function addJsFiles(Gdn_Controller $controller, $inDashboard = false) {
        if (!AnalyticsTracker::getInstance()->trackingDisabled() || $inDashboard) {
            $controller->addJsFile('vendors/keen.min.js', 'plugins/vanillaanalytics');
        }

        if (!AnalyticsTracker::getInstance()->trackingDisabled()) {
            $controller->addJsFile('keenio.min.js', 'plugins/vanillaanalytics');
        }

        if ($inDashboard) {
            $controller->addJsFile('keeniowidget.min.js', 'plugins/vanillaanalytics');
        }
    }

    /**
     * Overwrite and append default key/value pairs to incoming array.
     *
     * @link https://keen.io/docs/api/#data-enrichment
     * @param array $defaults List of default data pairs for all events.
     * @return array
     */
    public function addDefaults(array $defaults = array()) {
        $additionalDefaults = [
            'keen' => [
                'addons' => [
                    [
                        'name' => 'keen:ip_to_geo',
                        'input' => [
                            'ip' => 'ip'
                        ],
                        'output' => 'ipGeo'
                    ],
                    /**
                     * url_parser doesn't work without a domain name.  Since we don't currently use domain name, we're
                     * going to ditch keen's URL parser addon.
                    [
                        'name' => 'keen:url_parser',
                        'input' => [
                            'url' => 'url'
                        ],
                        'output' => 'urlParsed'
                    ]
                     */
                ]
            ]
        ];

        $defaults = array_merge($defaults, $additionalDefaults);

        if (!empty($defaults['referrer'])) {
            $defaults['keen']['addons'][] = [
                'name' => 'keen:referrer_parser',
                'input' => [
                    'referrer_url' => 'referrer',
                    'page_url' => 'url'
                ],
                'output' => 'referrerParsed'
            ];
        }

        if (!empty($defaults['userAgent'])) {
            $defaults['keen']['addons'][] = [
                'name' => 'keen:ua_parser',
                'input' => [
                    'ua_string' => 'userAgent'
                ],
                'output' => 'userAgentParsed'
            ];
        }

        return $defaults;
    }

    /**
     * Record an event using the keen.io API.
     *
     * @param string $collection Grouping for the current event.
     * @param string $type Name/type of the event being tracked.
     * @param array $details A collection of details about the event.
     * @return array Body of response from keen.io
     */
    public function event($collection, $type, array $details = []) {
        $details['type'] = $type;

        return $this->client->addEvent($collection, $details);
    }


    /**
     * Detect if tracker is configured for use.
     *
     * @param bool $disableWrite Disable writing to the tracker?
     * @param bool $disableRead Disable reading from the tracker?
     * @return bool True on configured, false otherwise
     */
    public static function isConfigured($disableWrite = false, $disableRead = false) {
        $configured = false;

        // Do we at least have a project ID configured?
        if (c('VanillaAnalytics.KeenIO.ProjectID')) {
            // Do we need either a read or write key and have them configured?
            if (($disableWrite || c('VanillaAnalytics.KeenIO.WriteKey')) &&
                ($disableRead || c('VanillaAnalytics.KeenIO.ReadKey'))) {
                $configured = true;
            }
        }

        return $configured;
    }

    /**
     * Setup routine, called when plug-in is enabled.
     */
    public function setup()
    {
        if (!c('VanillaAnalytics.KeenIO.ProjectID')) {
            // Attempt to grab all the necessary data for creating a project with keen.io
            $defaultProjectUser = c('VanillaAnalytics.KeenIO.DefaultProjectUser');
            $site = class_exists('Infrastructure') ? Infrastructure::site('name') : c('Garden.Domain', null);
            $orgID = c('VanillaAnalytics.KeenIO.OrgID');
            $orgKey = c('VanillaAnalytics.KeenIO.OrgKey');

            // All of these pieces are essential for creating a project.  Fail without them.
            if (!$orgID) {
                throw new Gdn_UserException('Empty value for VanillaAnalytics.KeenIO.OrgID');
            }
            if (!$orgKey) {
                throw new Gdn_UserException('Empty value for VanillaAnalytics.KeenIO.OrgKey');
            }
            if (!$defaultProjectUser) {
                throw new Gdn_UserException('Empty value for VanillaAnalytics.KeenIO.DefaultProjectUser');
            }

            // Build the keen.io client and attempt to create a new project
            $keenIOConfig = [
                'orgID' => $orgID,
                'orgKey' => $orgKey
            ];
            $keenIOClient = new KeenIOClient(null, $keenIOConfig);

            $project = $keenIOClient->addProject(
                $site,
                [
                    [
                        'email' => $defaultProjectUser
                    ]
                ]
            );

            // If we were successful, save the details.  If not, trigger an error.
            if ($project) {
                saveToConfig('VanillaAnalytics.KeenIO.ProjectID', $project->id);
                saveToConfig('VanillaAnalytics.KeenIO.ReadKey', $project->apiKeys->readKey);
                saveToConfig('VanillaAnalytics.KeenIO.WriteKey', $project->apiKeys->writeKey);
            } else {
                throw new Gdn_UserException('Unable to create project on keen.io');
            }
        }
    }
}
