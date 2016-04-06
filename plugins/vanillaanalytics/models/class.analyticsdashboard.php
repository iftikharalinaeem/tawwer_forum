<?php
/**
 * AnalyticsDashboard class file.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanillaanalytics
 */

/**
 * A collection of analytics charts and metrics, grouped by panels.
 */
class AnalyticsDashboard implements JsonSerializable {

    /**
     * Slug for a user's private/personal dashboard.
     */
    const DASHBOARD_PERSONAL = 'personal-dashboard';

    /**
     * @var string Unique identifier for this dashboard.
     */
    public $dashboardID;

    /**
     * @var array An associative array of default dashboard configurations.
     */
    protected static $defaults = [];

    /**
     * @var array Collection of panels.
     */
    protected $panels = [];

    /**
     * @var bool Is this a user's personal dashboard?
     */
    protected $personal = false;

    /** @var Gdn_SQLDriver Contains the sql driver for the object. */
    public $sql;

    /**
     * @var string Title for this dashboard.
     */
    protected $title = '';

    /**
     * AnalyticsDashboard constructor.
     *
     * @param bool|integer|string $dashboardID Unique identifier for this dashboard.  False if none.
     */
    public function __construct($title = false, $metrics = [], $charts = []) {
        $this->sql = Gdn::database()->sql();

        if ($title) {
            $dashboardID = strtolower(preg_replace(
                '#[^A-Za-z0-9\-]#',
                '',
                str_replace(' ', '-', $title)
            ));
            $this->setTitle(t($title));
            $this->setDashboardID($dashboardID);
        }

        // Create the default panels: metrics and charts.
        $this->panels['metrics'] = new AnalyticsPanel('metrics');
        $this->panels['charts']  = new AnalyticsPanel('charts');

        if (is_array($metrics) && !empty($metrics)) {
            $this->getPanel('metrics')->addWidget($metrics);
        }

        if (is_array($charts) && !empty($charts)) {
            $this->getPanel('charts')->addWidget($charts);
        }
    }

    /**
     * Save a widget to a custom dashboard.
     *
     * @param string $widgetID
     * @param string $dashboardID
     * @param int $userID
     */
    public function addWidget($widgetID, $dashboardID, $userID) {
        // Check to see if we've already made this association.
        $dashboardWidget = $this->sql->getWhere(
            'AnalyticsDashboardWidget',
            [
                'DashboardID'  => $dashboardID,
                'InsertUserID' => $userID,
                'WidgetID'     => $widgetID
            ]
        )->numRows();

        if ($dashboardWidget > 0) {
            // Nothing to do here.
            return;
        }

        $this->sql->insert(
            'AnalyticsDashboardWidget',
            [
                'DashboardID'  => $dashboardID,
                'DateInserted' => Gdn_Format::toDateTime(),
                'InsertUserID' => $userID,
                'WidgetID'     => $widgetID
            ]
        );
    }

    /**
     * Attempt to fetch an existing dashboard (default or personal).
     *
     * @todo Add database lookups.
     * @param string $dashboardID Unique identifier used to lookup the dashboard.
     * @return bool|AnalyticsDashboard An AnalyticsDashboard on success, false on failure.
     */
    public function getID($dashboardID) {
        $defaults = $this->getDefaults();
        $result = false;

        if ($dashboardID == self::DASHBOARD_PERSONAL) {
            $result = new AnalyticsDashboard(
                'Personal Dashboard',
                [],
                $this->getUserDashboardWidgets(self::DASHBOARD_PERSONAL)
            );
            $result->setPersonal(true);
        }
        elseif (ctype_digit($dashboardID)) {
            // Database lookup
        } elseif (array_key_exists($dashboardID, $defaults)) {
            $result = $defaults[$dashboardID];
        }

        return $result;
    }

    /**
     * Fetch the current dashboard's ID.
     *
     * @return string
     */
    public function getDashboardID() {
        return $this->dashboardID;
    }

    /**
     * Grab a list of default dashboard configurations.
     *
     * @return array
     */
    public function getDefaults() {
        if (empty(static::$defaults)) {
            $defaults = [
                'Posting' => [
                    'metrics' => ['total-discussions', 'total-comments', 'total-contributors'],
                    'charts'  => ['discussions', 'comments', 'posts', 'posts-by-type', 'posts-by-category',
                        'posts-by-role-type', 'contributors', 'contributors-by-category', 'contributors-by-role-type']
                ],
                'Traffic' => [
                    'metrics' => ['total-pageviews', 'total-active-users', 'total-unique-pageviews'],
                    'charts'  => ['active-users', 'unique-pageviews', 'unique-visits-by-role-type', 'pageviews', 'registrations']
                ]
            ];

            foreach ($defaults as $title => $panels) {
                $metrics = [];
                $charts  = [];

                if (is_array($panels)) {
                    $metrics = array_key_exists('metrics', $panels) && is_array($panels['metrics']) ? $panels['metrics'] : [];
                    $charts = array_key_exists('charts', $panels) && is_array($panels['charts']) ? $panels['charts'] : [];
                }

                $dashboard   = new AnalyticsDashboard($title, $metrics, $charts);
                $dashboardID = $dashboard->getDashboardID();

                static::$defaults[$dashboardID] = $dashboard;
            }
        }

        return static::$defaults;
    }

    /**
     * Grab a panel by its ID.
     *
     * @param string $panelID Target panel ID (e.g. metrics, charts)
     * @return AnalyticsPanel|bool An AnalyticsPanel instance on success, false on failure.
     */
    public function getPanel($panelID) {
        $result = false;

        if (array_key_exists($panelID, $this->panels)) {
            $result = $this->panels[$panelID];
        }

        return $result;
    }

    /**
     * Retrieve all this instance's panels.
     *
     * @return array A collection of AnalyticsPanel objects.
     */
    public function getPanels() {
        return $this->panels;
    }

    /**
     * Get the title for this dashboard.
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Perform a database lookup to grab the widgets associated with a custom dashboard.
     *
     * @param string $dashboardID
     * @param int|null $userID
     * @return array
     */
    public function getUserDashboardWidgets($dashboardID, $userID = null) {
        if ($dashboardID == self::DASHBOARD_PERSONAL && $userID == null) {
            // This is a user-specific dashboard lookup.  We need a user ID.
            if (Gdn::session()->isValid()) {
                $userID = Gdn::session()->UserID;
            } else {
                return [];
            }
        }

        $result = $this->sql
            ->getWhere(
                'AnalyticsDashboardWidget',
                [
                    'DashboardID' => self::DASHBOARD_PERSONAL,
                    'InsertUserID'      => $userID
                ],
                'Sort',
                'asc'
            )
            ->resultArray();

        return array_column($result, 'WidgetID');
    }

    /**
     * Is this a user's personal dashboard?
     * @return bool
     */
    public function isPersonal() {
        return $this->personal;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array
     */
    public function jsonSerialize() {
        return [
            'dashboardID' => $this->dashboardID,
            'panels'      => $this->panels,
            'personal'    => $this->isPersonal(),
            'title'       => $this->title
        ];
    }

    /**
     * Remove a widget from a custom dashboard.
     *
     * @param string $widgetID
     * @param string $dashboardID
     * @param int $userID
     */
    public function removeWidget($widgetID, $dashboardID, $userID) {
        $this->sql->delete(
            'AnalyticsDashboardWidget',
            [
                'DashboardID'  => $dashboardID,
                'InsertUserID' => $userID,
                'WidgetID'     => $widgetID
            ]
        );
    }

    /**
     * Render an analytics dashboard page.
     *
     * @param Gdn_Controller $sender
     * @param AnalyticsDashboard $dashboard
     */
    public function render(Gdn_Controller $sender, AnalyticsDashboard $dashboard) {
        $sender->addSideMenu();

        $sender->setData('Title', sprintf(
            t('Analytics: %1$s'),
            $dashboard->getTitle()
        ));

        $sender->setData(
            'AnalyticsDashboard',
            $dashboard
        );

        $sender->addDefinition(
            'analyticsDashboard',
            $dashboard
        );

        $sender->render(
            'analytics',
            false,
            'plugins/vanillaanalytics'
        );
    }

    /**
     * Set this dashboard's unique identifier.
     *
     * @param string $dashboardID A unique identifier for this instance.
     * @return $this
     */
    public function setDashboardID($dashboardID) {
        $this->dashboardID = $dashboardID;
        return $this;
    }

    /**
     * Set the title for this dashboard.
     *
     * @param string $title New title for this dashboard.
     * @return $this
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * Set the flag to determine if this is a user's personal dashboard.
     *
     * @param bool $personal
     * @return $this
     */
    public function setPersonal($personal) {
        $this->personal = (bool)$personal;
        return $this;
    }
}
