<?php

/**
 * Interface for handling analytics events triggered by AnalyticsTracker
 */
interface TrackerInterface {

    /**
     * Add definitions to the gdn.meta JavaScript array.
     *
     * @param Gdn_Controller $controller
     */
    public function addDefinitions(Gdn_Controller $controller);

    /**
     * Add JavaScript files to the current page.
     *
     * @param Gdn_Controller $controller
     */
    public function addJsFiles(Gdn_Controller $controller);

    /**
     * Add and overwrite default event data values.
     *
     * @param array $defaults
     * @return array
     */
    public function addDefaults(array $defaults = array());

    /**
     * Detect if an analytics tracker is configured for use.
     *
     * @param bool $write Configured to write to the tracker?
     * @param bool $read Configured to read from the tracker?
     * @return bool True on configured, false otherwise
     */
    public static function isConfigured($write = true, $read = true);

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
