<?php

/**
 * A logical grouping of dashboards.
 */
class AnalyticsSection {

    /**
     * @var array Collection of dashboards in this section.
     */
    protected $dashboards = [];

    /**
     * @var string A unique identifier for this section.
     */
    public $sectionID;

    /**
     * AnalyticsSection constructor.
     * @param string|bool $sectionID This section's unique identifier.
     */
    public function __construct($sectionID = false) {
        if ($sectionID) {
            $this->setID($sectionID);
        }
    }

    /**
     * Add an instance of AnalyticsDashboard to the section.
     *
     * @param AnalyticsDashboard $dashboard New dashboard for the collection.
     * @return $this
     */
    public function addDashboard(AnalyticsDashboard $dashboard) {
        $this->dashboards[] = $dashboard;
        return $this;
    }

    /**
     * Grab the full list of dashboards for this section.
     *
     * @return array
     */
    public function getDashboards() {
        return $this->dashboards;
    }

    /**
     * Set this section's unique identifier.
     *
     * @param string $sectionID The unique identifier for this section.
     * @return $this
     */
    public function setID($sectionID) {
        $this->sectionID = $sectionID;
        return $this;
    }
}
