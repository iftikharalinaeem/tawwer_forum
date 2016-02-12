<?php

/**
 * A collection of analytics charts and metrics, grouped by panels.
 */
class AnalyticsDashboard implements JsonSerializable {

    /**
     * @var array Collection of panels.
     */
    protected $panels = [];

    /**
     * @var string Unique identifier for this dashboard.
     */
    public $dashboardID;

    /**
     * @var array An associative array of default dashboard configurations.
     */
    protected static $defaults = [];

    /**
     * @var string Title for this dashboard.
     */
    protected $title = '';

    /**
     * AnalyticsDashboard constructor.
     * @param bool $dashboardID
     */
    public function __construct($dashboardID = false) {
        if ($dashboardID) {
            $this->setID($dashboardID);
        }

        // Create the default panels: metrics and charts.
        $this->panels['metrics'] = new AnalyticsPanel('metrics');
        $this->panels['charts']  = new AnalyticsPanel('charts');
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

        if (ctype_digit($dashboardID)) {
            // Database lookup
        } elseif (array_key_exists($dashboardID, $defaults)) {
            $result = $defaults[$dashboardID];
        }

        return $result;
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
                    'metrics' => [],
                    'charts'  => []
                ],
                'Traffic' => [
                    'metrics' => ['total-pageviews', 'total-active-users', 'total-unique-pageviews'],
                    'charts'  => ['pageviews', 'active-users', 'unique-pageviews', 'unique-visits-by-role-type']
                ]
            ];

            foreach ($defaults as $title => $panels) {
                $dashboardID = strtolower(preg_replace(
                    '#[^A-Za-z0-9\-]#',
                    '',
                    str_replace(' ', '-', $title)
                ));
                $dashboard = new AnalyticsDashboard($dashboardID);
                $dashboard->setTitle(t($title));

                if (is_array($panels)) {
                    foreach ($panels as $panelID => $widgets) {
                        if ($currentPanel = $dashboard->getPanel($panelID)) {
                            $currentPanel->addWidget($widgets);
                        }
                    }
                }

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
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize() {
        return [
            'dashboardID' => $this->dashboardID,
            'panels'      => $this->panels,
            'title'       => $this->title
        ];
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

        $sender->addJsFile('dashboard.min.js', 'plugins/vanillaanalytics');

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
    public function setID($dashboardID) {
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
}
