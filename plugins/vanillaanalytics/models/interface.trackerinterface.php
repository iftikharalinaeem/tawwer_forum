<?php

/**
 * Interface for handling analytics events triggered by AnalyticsTracker
 */
interface TrackerInterface {

    /**
     * Track an event.
     *
     * @param string $type Name/type of the event being tracked.
     * @param array $details A collection of details about the event.
     */
    public function event($type, $details = array());
}
