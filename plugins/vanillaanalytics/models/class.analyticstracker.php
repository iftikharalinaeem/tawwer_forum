<?php
class AnalyticsTracker {
    protected static $trackers = [];

    public static function addTracker($name, TrackerInterface &$interface) {
        $trackers[$name] = $interface;
    }

    public static function trackEvent($name, $data = array()) {
        $defaults = array(
            'domain'    => rtrim(Url('/', true), '/'),
            'ip'        => Gdn::request()->ipAddress(),
            'method'    => Gdn::request()->requestMethod(),
            'path'      => Gdn::request()->path(),
            'timestamp' => date('c'),
            'userID'    => Gdn::session()->UserID,
            'username'  => val('Name', Gdn::session()->User, 'anonymous')
        );

        $data = array_merge($defaults, $data);

        trace($data, "Analytics event: {$name}");

        foreach (static::$trackers as $name => $interface) {
            $interface->trackEvent($name, $data);
        }
    }
}
