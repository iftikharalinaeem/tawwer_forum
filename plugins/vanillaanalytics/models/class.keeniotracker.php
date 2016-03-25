<?php
/**
 * KeenIOTracker class file.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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
     * Constructor.
     */
    public function __construct() {
        $this->client = new KeenIOClient(
            'https://api.keen.io/{version}/',
            [
                'orgID'      => c('VanillaAnalytics.KeenIO.OrgID'),
                'orgKey'     => c('VanillaAnalytics.KeenIO.OrgKey'),
                'projectID'  => c('VanillaAnalytics.KeenIO.ProjectID'),
                'readKey'    => c('VanillaAnalytics.KeenIO.ReadKey'),
                'writeKey'   => c('VanillaAnalytics.KeenIO.WriteKey')
            ]
        );
    }

    /**
     * Add widget configurations to the ongoing list.
     *
     * @todo Add Visits
     * @todo Add Visits by Role Type
     * @param array $widgets Incoming array of charts to add to.
     */
    public function addWidgets(array &$widgets) {
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
                'operator'       => 'gt',
                'property_name'  => 'user.userID',
                'property_value' => 0
            ]);

        $totalActiveUsersWidget = new AnalyticsWidget();
        $totalActiveUsersWidget->setID('total-active-users')
            ->setTitle(t('Active Users'))
            ->setHandler('KeenIOWidget')
            ->setType('metric')
            ->setData([
                'chart' => [
                    'title' => 'Active Users'
                ],
                'query' => $totalActiveUsersQuery
            ]);

        $widgets['total-active-users'] = $totalActiveUsersWidget;

        // Pageviews (metric)
        $totalPageViewQuery = new KeenIOQuery();
        $totalPageViewQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Page Views'))
            ->setEventCollection('page');

        $totalPageViewsWidget = new AnalyticsWidget();
        $totalPageViewsWidget->setID('total-pageviews')
            ->setTitle(t('Page Views'))
            ->setHandler('KeenIOWidget')
            ->setType('metric')
            ->setData([
                'chart' => [
                    'title' => 'Page Views'
                ],
                'query' => $totalPageViewQuery
            ]);

        $widgets['total-pageviews'] = $totalPageViewsWidget;

        // Unique Pageviews (metric)
        $totalUniquePageviewsQuery = new KeenIOQuery();
        $totalUniquePageviewsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Unique Page Views'))
            ->setEventCollection('page')
            ->setTargetProperty('user.sessionID');

        $totalUniquePageviewsWidget = new AnalyticsWidget();
        $totalUniquePageviewsWidget->setID('total-unique-pageviews')
            ->setTitle(t('Unique Page Views'))
            ->setHandler('KeenIOWidget')
            ->setType('metric')
            ->setData([
                'chart' => [
                    'title' => 'Unique Page Views'
                ],
                'query' => $totalUniquePageviewsQuery
            ]);

        $widgets['total-unique-pageviews'] = $totalUniquePageviewsWidget;

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

        $totalDiscussionsWidget = new AnalyticsWidget();
        $totalDiscussionsWidget->setID('total-discussions')
            ->setTitle(t('Discussions'))
            ->setHandler('KeenIOWidget')
            ->setType('metric')
            ->setData([
                'chart' => [
                    'title' => 'Discussions'
                ],
                'query' => $totalDiscussionsQuery
            ])
            ->addSupport('cat01');

        $widgets['total-discussions'] = $totalDiscussionsWidget;

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

        $totalCommentsWidget = new AnalyticsWidget();
        $totalCommentsWidget->setID('total-comments')
            ->setTitle(t('Comments'))
            ->setHandler('KeenIOWidget')
            ->setType('metric')
            ->setData([
                'chart' => [
                    'title' => 'Comments'
                ],
                'query' => $totalCommentsQuery
            ])
            ->addSupport('cat01');

        $widgets['total-comments'] = $totalCommentsWidget;

        // Contributors (metric)
        $totalContributorsQuery = new KeenIOQuery();
        $totalContributorsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Contributors'))
            ->setEventCollection('post')
            ->setTargetProperty('user.userID');

        $totalContributorsWidget = new AnalyticsWidget();
        $totalContributorsWidget->setID('total-contributors')
            ->setTitle(t('Contributors'))
            ->setHandler('KeenIOWidget')
            ->setType('metric')
            ->setData([
                'chart' => [
                    'title' => 'Contributors'
                ],
                'query' => $totalContributorsQuery
            ])
            ->addSupport('cat01');

        $widgets['total-contributors'] = $totalContributorsWidget;

        /**
         * Charts
         */

        // Pageviews (chart)
        $pageViewQuery = new KeenIOQuery();
        $pageViewQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Page Views'))
            ->setEventCollection('page')
            ->setInterval('daily');

        $pageViewsWidget = new AnalyticsWidget();
        $pageViewsWidget->setID('pageviews')
            ->setTitle(t('Page Views'))
            ->setHandler('KeenIOWidget')
            ->setType('chart')
            ->setData([
                'chart' => [
                    'labels' => ['Page Views']
                ],
                'query' => $pageViewQuery
            ]);

        $widgets['pageviews'] = $pageViewsWidget;

        // Active Users (chart)
        $activeUsersQuery = new KeenIOQuery();
        $activeUsersQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Active Users'))
            ->setEventCollection('page')
            ->setTargetProperty('user.userID')
            ->setInterval('daily')
            ->addFilter([
                'operator'       => 'gt',
                'property_name'  => 'user.userID',
                'property_value' => 0
            ]);

        $activeUsersWidget = new AnalyticsWidget();
        $activeUsersWidget->setID('active-users')
            ->setTitle(t('Active Users'))
            ->setHandler('KeenIOWidget')
            ->setType('chart')
            ->setData([
                'chart' => [
                    'labels' => ['Active Users']
                ],
                'query' => $activeUsersQuery
            ]);

        $widgets['active-users'] = $activeUsersWidget;

        // Unique Pageviews (chart)
        $uniquePageviewsQuery = new KeenIOQuery();
        $uniquePageviewsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Unique Page Views'))
            ->setEventCollection('page')
            ->setTargetProperty('user.userID')
            ->setInterval('daily');

        $uniquePageviewsWidget = new AnalyticsWidget();
        $uniquePageviewsWidget->setID('unique-pageviews')
            ->setTitle(t('Unique Page Views'))
            ->setHandler('KeenIOWidget')
            ->setType('chart')
            ->setData([
                'chart' => [
                    'labels' => ['Unique Page Views']
                ],
                'query' => $uniquePageviewsQuery
            ])
            ->addSupport('cat01');

        $widgets['unique-pageviews'] = $uniquePageviewsWidget;

        // Unique Visits by Role Type
        $uniqueVisitsByRoleTypeQuery = new KeenIOQuery();
        $uniqueVisitsByRoleTypeQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Unique Visits by Role Type'))
            ->setEventCollection('page')
            ->setTargetProperty('user.sessionID')
            ->setInterval('daily')
            ->setGroupBy('user.roleType');

        $uniqueVisitsByRoleTypeWidget = new AnalyticsWidget();
        $uniqueVisitsByRoleTypeWidget->setID('unique-visits-by-role-type')
            ->setTitle(t('Unique Visits by Role Type'))
            ->setHandler('KeenIOWidget')
            ->setType('chart')
            ->setData([
                'chart' => ['chartType' => 'area'],
                'query' => $uniqueVisitsByRoleTypeQuery
            ]);

        $widgets['unique-visits-by-role-type'] = $uniqueVisitsByRoleTypeWidget;

        // Discussions
        $discussionsQuery = new KeenIOQuery();
        $discussionsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Discussions'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'type',
                'property_value' => 'discussion_add'
            ]);

        $discussionsWidget = new AnalyticsWidget();
        $discussionsWidget->setID('discussions')
            ->setTitle(t('Discussions'))
            ->setHandler('KeenIOWidget')
            ->setData([
                'chart' => [
                    'labels' => ['Discussions']
                ],
                'query' => $discussionsQuery
            ])
            ->addSupport('cat01');

        $widgets['discussions'] = $discussionsWidget;

        // Comments
        $commentsQuery = new KeenIOQuery();
        $commentsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Comments'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'type',
                'property_value' => 'comment_add'
            ]);

        $commentsWidget = new AnalyticsWidget();
        $commentsWidget->setID('comments')
            ->setTitle(t('Comments'))
            ->setHandler('KeenIOWidget')
            ->setData([
                'chart' => [
                    'labels' => ['Comments']
                ],
                'query' => $commentsQuery
            ])
            ->addSupport('cat01');

        $widgets['comments'] = $commentsWidget;

        // Posts
        $postsQuery = new KeenIOQuery();
        $postsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Posts'))
            ->setEventCollection('post')
            ->setInterval('daily');

        $postsWidget = new AnalyticsWidget();
        $postsWidget->setID('posts')
            ->setTitle(t('Posts'))
            ->setHandler('KeenIOWidget')
            ->setData([
                'chart' => [
                    'labels' => ['Posts']
                ],
                'query' => $postsQuery
            ])
            ->addSupport('cat01');

        $widgets['posts'] = $postsWidget;

        // Posts by type
        $postsByTypeQuery = new KeenIOQuery();
        $postsByTypeQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Posts By Type'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setGroupBy('type');

        $postsByTypeWidget = new AnalyticsWidget();
        $postsByTypeWidget->setID('posts-by-type')
            ->setTitle(t('Posts By Type'))
            ->setHandler('KeenIOWidget')
            ->setData([
                'chart' => [
                    'labelMapping' => [
                        'discussion_add' => 'Discussions',
                        'comment_add' => 'Comments'
                    ],
                    'chartType' => 'area'
                ],
                'query' => $postsByTypeQuery
            ])
            ->addSupport('cat01');

        $widgets['posts-by-type'] = $postsByTypeWidget;

        // Posts by category
        $postsByCategoryQuery = new KeenIOQuery();
        $postsByCategoryQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Posts By Category'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setGroupBy('categoryAncestors.cat01.name');

        $postsByCategoryWidget = new AnalyticsWidget();
        $postsByCategoryWidget->setID('posts-by-category')
            ->setTitle(t('Posts By Category'))
            ->setHandler('KeenIOWidget')
            ->setData([
                'chart' => [
                    'labelMapping' => AnalyticsData::getCategoryMap(),
                    'chartType' => 'area'
                ],
                'query' => $postsByCategoryQuery
            ])
            ->addSupport('cat01');

        $widgets['posts-by-category'] = $postsByCategoryWidget;

        // Posts by role type
        $postsByRoleTypeQuery = new KeenIOQuery();
        $postsByRoleTypeQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Posts By Role Type'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setGroupBy('user.roleType');

        $postsByRoleTypeWidget = new AnalyticsWidget();
        $postsByRoleTypeWidget->setID('posts-by-role-type')
            ->setTitle(t('Posts By Role Type'))
            ->setHandler('KeenIOWidget')
            ->setData([
                'chart' => [
                    'chartType' => 'area'
                ],
                'query' => $postsByRoleTypeQuery
            ])
            ->addSupport('cat01');

        $widgets['posts-by-role-type'] = $postsByRoleTypeWidget;

        // Contributors (chart)
        $contributorsQuery = new KeenIOQuery();
        $contributorsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Contributors'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setTargetProperty('user.userID');

        $contributorsWidget = new AnalyticsWidget();
        $contributorsWidget->setID('contributors')
            ->setTitle(t('Contributors'))
            ->setHandler('KeenIOWidget')
            ->setType('chart')
            ->setData([
                'chart' => [
                    'labels' => ['Contributors']
                ],
                'query' => $contributorsQuery
            ])
            ->addSupport('cat01');

        $widgets['contributors'] = $contributorsWidget;

        // Contributors by category (chart)
        $contributorsByCategoryQuery = new KeenIOQuery();
        $contributorsByCategoryQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Contributors by Category'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setTargetProperty('user.userID')
            ->setGroupBy('categoryAncestors.cat01.name');

        $contributorsByCategoryWidget = new AnalyticsWidget();
        $contributorsByCategoryWidget->setID('contributors-by-category')
            ->setTitle(t('Contributors by Category'))
            ->setHandler('KeenIOWidget')
            ->setType('chart')
            ->setData([
                'chart' => [
                    'chartType' => 'area'
                ],
                'query' => $contributorsByCategoryQuery
            ])
            ->addSupport('cat01');
        $widgets['contributors-by-category'] = $contributorsByCategoryWidget;

        // Contributors by role type (chart)
        $contributorsByRoleTypeQuery = new KeenIOQuery();
        $contributorsByRoleTypeQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Contributors by Role Type'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setTargetProperty('user.userID')
            ->setGroupBy('user.roleType');

        $contributorsByRoleTypeWidget = new AnalyticsWidget();
        $contributorsByRoleTypeWidget->setID('contributors-by-role-type')
            ->setTitle(t('Contributors by Role Type'))
            ->setHandler('KeenIOWidget')
            ->setType('chart')
            ->setData([
                'chart' => [
                    'chartType' => 'area'
                ],
                'query' => $contributorsByRoleTypeQuery
            ])
            ->addSupport('cat01');
        $widgets['contributors-by-role-type'] = $contributorsByRoleTypeWidget;

        // Posts per user (chart)
        $postsPerUserWidget = new AnalyticsWidget();
        $postsPerUserWidget->setID('posts-per-user')
            ->setTitle(t('Posts Per User'))
            ->setHandler('KeenIOWidget')
            ->setType('chart')
            ->setData([
                'chart' => [
                    'chartType' => 'area'
                ],
                'query' => [
                    $postsQuery,
                    $activeUsersQuery
                ]
            ])
            ->setCallback('divideResult');
        $widgets['posts-per-user'] = $postsPerUserWidget;
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
            $controller->addJsFile('keenio.sdk.min.js', 'plugins/vanillaanalytics');
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
     * @param bool $write Configured to write to the tracker?
     * @param bool $read Configured to read from the tracker?
     * @return bool True on configured, false otherwise
     */
    public static function isConfigured($write = true, $read = true) {
        $configured = false;

        // Do we at least have a project ID configured?
        if (c('VanillaAnalytics.KeenIO.ProjectID')) {
            // Do we need either a read or write key and have them configured?
            if ((!$write || c('VanillaAnalytics.KeenIO.WriteKey')) &&
                (!$read || c('VanillaAnalytics.KeenIO.ReadKey'))) {
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
