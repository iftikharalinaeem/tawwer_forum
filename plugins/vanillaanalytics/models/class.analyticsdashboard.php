<?php

/**
 * A collection of analytics charts and metrics, grouped by panels.
 */
class AnalyticsDashboard {

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
    protected static $defaultDashboards = [];

    /**
     * AnalyticsDashboard constructor.
     * @param bool $dashboardID
     */
    public function __construct($dashboardID = false) {
        if ($dashboardID) {
            $this->setID($dashboardID);
        }

        // Create the default panels: metrics and charts.
        $this->panels['metrics'] = new AnalyticsPanel();
        $this->panels['charts']  = new AnalyticsPanel();
    }

    /**
     * Attempt to fetch an existing dashboard (default or personal).
     *
     * @todo Add database lookups.
     * @param string $dashboardID Unique identifier used to lookup the dashboard.
     * @return bool|AnalyticsDashboard An AnalyticsDashboard on success, false on failure.
     */
    public function getID($dashboardID) {
        $result = false;

        if (ctype_digit($dashboardID)) {
            // Database lookup
        } elseif (array_key_exists($dashboardID, static::$defaultDashboards)) {
            $result = static::$defaultDashboards[$dashboardID];
        }

        return $result;
    }

    /**
     * Grab a panel by its ID.
     *
     * @param string $panelID Target panel ID (e.g. metrics, charts)
     * @return AnalyticsPanel|bool An AnalyticsPanel instance on success, false on failure.
     */
    public function getPanel($panelID) {
        $result = false;

        if (in_array($panelID, $this->panels)) {
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
     * Set this dashboard's unique identifier.
     *
     * @param string $dashboardID A unique identifier for this instance.
     * @return $this
     */
    public function setID($dashboardID) {
        $this->dashboardID = $dashboardID;
        return $this;
    }
}
