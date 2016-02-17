<?php
/**
 * AnalyticsSection class file.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanillaanalytics
 */

/**
 * A logical grouping of dashboards.
 */
class AnalyticsSection {

    /**
     * @var array Collection of dashboards in this section.
     */
    protected $dashboards = [];

    /**
     * @var array A list of default section configurations.
     */
    static protected $defaults = [];

    /**
     * @var string A unique identifier for this section.
     */
    public $sectionID;

    /**
     * @var string Human-readable title for this section.
     */
    protected $title = '';

    /**
     * AnalyticsSection constructor.
     *
     * @param bool|integer|string $sectionID This section's unique identifier. False if none.
     */
    public function __construct($sectionID = false) {
        if ($sectionID) {
            $this->setID($sectionID);
        }
    }

    /**
     * Add an instance of AnalyticsDashboard to the section.
     *
     * @param array|string|AnalyticsDashboard $dashboard New dashboard for the collection.
     * @return $this
     */
    public function addDashboard($dashboard) {
        if ($dashboard instanceof AnalyticsDashboard) {
            // Is this an actual dashboard instance?
            $this->dashboards[] = $dashboard;
        } elseif (is_array($dashboard)) {
            // Is this an array we need to iterate through?
            foreach ($dashboard as $currentDashboard) {
                $this->addDashboard($currentDashboard);
            }
        } elseif (is_string($dashboard)) {
            // Is this a string we can use to lookup a dashboard?
            $dashboardModel = new AnalyticsDashboard();
            $newDashboard = $dashboardModel->getID($dashboard);
            if ($newDashboard) {
                $this->dashboards[] = $newDashboard;
            }
        }

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
     * Attempt to fetch a section by its ID.
     *
     * @param string $sectionID Unique identifier used to lookup the section.
     * @return bool|AnalyticsSection An AnalyticsSection on success, false on failure.
     */
    public function getID($sectionID) {
        $result = false;
        $defaults = $this->getDefaults();

        if (array_key_exists($sectionID, $defaults)) {
            $result = $defaults[$sectionID];
        }

        return $result;
    }

    /**
     * Fetch the title for this section.
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Get the default section configurations.  Generate them, if necessary.
     *
     * @return array
     */
    public function getDefaults() {
        if (empty(static::$defaults)) {
            $defaultSections = [
                'Basic' => ['traffic', 'posting']
            ];

            foreach ($defaultSections as $title => $dashboards) {
                $sectionID = strtolower(preg_replace(
                    '#[^A-Za-z0-9\-]#',
                    '',
                    str_replace(' ', '-', $title)
                ));
                $section = new AnalyticsSection($sectionID);
                $section->setTitle = t($title);
                $section->addDashboard($dashboards);
                static::$defaults[$sectionID] = $section;
            }
        }

        return static::$defaults;
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

    /**
     * Set the title for this section.
     *
     * @param string $title New title for this section.
     * @return $this
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }
}
