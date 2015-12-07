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
     * @param $defaults
     */
    public function addDefaultData(&$defaults);

    /**
     * Track an event.
     *
     * @param string $type Name/type of the event being tracked.
     * @param array $details A collection of details about the event.
     */
    public function event($type, $details = array());
}
