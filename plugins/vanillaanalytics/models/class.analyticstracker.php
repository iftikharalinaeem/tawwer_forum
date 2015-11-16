<?php

/**
 * The core of event tracking.  This class is intended to handle dispatching individual service trackers.
 * @package VanillaAnalytics
 */
class AnalyticsTracker {

    /**
     * An array containing instances of individual service tracker interfaces.
     * @var array
     * @access protected
     */
    protected static $trackers = [];

    /**
     * Adds a new tracker instance to the collection.
     *
     * @param TrackerInterface $interface
     */
    public static function addTracker(TrackerInterface &$interface) {
        static::$trackers[] = $interface;
    }

    /**
     * Tracks an event.  Calls analytics service interfaces to record event details.
     * @param $event
     * @param array $data
     */
    public static function trackEvent($event, $data = array()) {

        // Load up the defaults we'd like to have and merge them into the data.
        $defaults = array(
            'domain'    => rtrim(Url('/', true), '/'),
            'ip'        => Gdn::request()->ipAddress(),
            'method'    => Gdn::request()->requestMethod(),
            'path'      => Gdn::request()->path(),
            'userID'    => Gdn::session()->UserID,
            'username'  => val('Name', Gdn::session()->User, 'anonymous')
        );

        $data = array_merge($defaults, $data);

        // Iterate through our tracker list and tell each of them about our event.
        foreach (static::$trackers as $interface) {
            $interface->event($event, $data);
        }

        trace($data, "Event: {$event}");
    }
}
