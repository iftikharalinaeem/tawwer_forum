<?php
/**
 * TrackerInterface class file.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */

/**
 * Interface for handling analytics events triggered by AnalyticsTracker
 */
interface TrackerInterface {

    /**
     * Add CSS files to the current page.
     *
     * @param Gdn_Controller $controller Instance of the current page's controller.
     * @param bool $inDashboard Is the current page a dashboard page?
     */
    public function addCssFiles(Gdn_Controller $controller, $inDashboard = false);

    /**
     * Add definitions to the gdn.meta JavaScript array.
     *
     * @param Gdn_Controller $controller
     * @param bool $inDashboard Is the current page a dashboard page?
     */
    public function addDefinitions(Gdn_Controller $controller, $inDashboard = false);

    /**
     * Add JavaScript files to the current page.
     *
     * @param Gdn_Controller $controller
     * @param bool $inDashboard Is the current page a dashboard page?
     */
    public function addJsFiles(Gdn_Controller $controller, $inDashboard = false);

    /**
     * Add and overwrite default event data values.
     *
     * @param array $defaults
     * @return array
     */
    public function addDefaults(array $defaults = array());

    /**
     * Add wiget configurations to the ongoing list.
     *
     * @param array $widgets Incoming array of widgets to add to.
     */
    public function addWidgets(array &$widgets);

    /**
     * Detect if an analytics tracker is configured for use.
     *
     * @param bool $disableWrite Disable writing to the tracker?
     * @param bool $disableRead Disable reading from the tracker?
     * @return bool True on configured, false otherwise
     */
    public static function isConfigured($disableWrite = false, $disableRead = false);

    /**
     * Track an event.
     *
     * @param string $collection Grouping for the current event.
     * @param string $type Name/type of the event being tracked.
     * @param array $details A collection of details about the event.
     */
    public function event($collection, $type, array $details = []);

    /**
     * Setup routine, called when plug-in is enabled.
     */
    public function setup();
}
