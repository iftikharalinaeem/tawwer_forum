<?php

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
        // Pageviews (chart)
        $pageViewQuery = new KeenIOQuery();
        $pageViewQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Pageviews'))
            ->setEventCollection('page')
            ->setInterval('daily');

        $pageViewsWidget = new AnalyticsWidget();
        $pageViewsWidget->setID('pageviews')
            ->setTitle(t('Pageviews'))
            ->setHandler('KeenIOWidget')
            ->setType('chart')
            ->setData([
                'chart' => [
                    'labels' => ['Pageviews']
                ],
                'query' => $pageViewQuery
            ]);

        $widgets['pageviews'] = $pageViewsWidget;

        // Pageviews (metric)
        $totalPageViewQuery = new KeenIOQuery();
        $totalPageViewQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Pageviews'))
            ->setEventCollection('page');

        $totalPageViewsWidget = new AnalyticsWidget();
        $totalPageViewsWidget->setID('total-pageviews')
            ->setTitle(t('Pageviews'))
            ->setHandler('KeenIOWidget')
            ->setType('metric')
            ->setData([
                'chart' => [
                    'title' => 'Pageviews'
                ],
                'query' => $totalPageViewQuery
            ]);

        $widgets['total-pageviews'] = $totalPageViewsWidget;

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

        // Unique Pageviews (chart)
        $uniquePageviewsQuery = new KeenIOQuery();
        $uniquePageviewsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Unique Pageviews'))
            ->setEventCollection('page')
            ->setTargetProperty('user.userID')
            ->setInterval('daily');

        $uniquePageviewsWidget = new AnalyticsWidget();
        $uniquePageviewsWidget->setID('unique-pageviews')
            ->setTitle(t('Unique Pageviews'))
            ->setHandler('KeenIOWidget')
            ->setType('chart')
            ->setData([
                'chart' => [
                    'labels' => ['Unique Pageviews']
                ],
                'query' => $uniquePageviewsQuery
            ]);

        $widgets['unique-pageviews'] = $uniquePageviewsWidget;

        // Unique Pageviews (metric)
        $totalUniquePageviewsQuery = new KeenIOQuery();
        $totalUniquePageviewsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Unique Pageviews'))
            ->setEventCollection('page')
            ->setTargetProperty('user.userID');

        $totalUniquePageviewsWidget = new AnalyticsWidget();
        $totalUniquePageviewsWidget->setID('total-unique-pageviews')
            ->setTitle(t('Unique Pageviews'))
            ->setHandler('KeenIOWidget')
            ->setType('metric')
            ->setData([
                'chart' => [
                    'title' => 'Unique Pageviews'
                ],
                'query' => $totalUniquePageviewsQuery
            ]);

        $widgets['total-unique-pageviews'] = $totalUniquePageviewsWidget;

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
                'chart' => [
                    'chartType' => 'area'
                ],
                'query' => $uniqueVisitsByRoleTypeQuery
            ]);

        $widgets['unique-visits-by-role-type'] = $uniqueVisitsByRoleTypeWidget;
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
