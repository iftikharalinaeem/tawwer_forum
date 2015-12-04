<?php

/**
 * The core of event tracking.  This singleton class is intended to handle dispatching individual service trackers.
 * @package VanillaAnalytics
 */
class AnalyticsTracker {

    /**
     * Holds the instance for our singleton
     * @var AnalyticsTracker
     */
    private static $instance;

    /**
     * An array containing instances of individual service tracker interfaces.
     * @var array
     */
    protected $trackers = [];

    /**
     * Our constructor.
     */
    protected function __construct() {
        require_once(dirname(__FILE__) . '/../vendor/autoload.php');

        // For now, using keen.io is hardwired.
        if (c('VanillaAnalytics.KeenIO.ProjectID') && c('VanillaAnalytics.KeenIO.WriteKey')) {
            $this->addTracker(new KeenIOTracker());
        }
    }

    /**
     * Call service trackers to add values to the gdn.meta JavaScript array
     *
     * @param Gdn_Controller Instance of the current controller.
     */
    public function addDefinitions(Gdn_Controller $controller) {
        foreach ($this->trackers as $tracker) {
            $tracker->addDefinitions($controller);
        }

        $controller->addDefinition('eventData', $this->getDefaultData());
    }

    /**
     * Call service trackers to add their own JavaScript files to the page.
     *
     * @param Gdn_Controller Instance of the current controller.
     */
    public function addJsFiles(Gdn_Controller $controller) {
        foreach ($this->trackers as $interface) {
            $interface->addJsFiles($controller);
        }
    }

    /**
     * Adds a new tracker instance to the collection.
     *
     * @param TrackerInterface $interface
     */
    public function addTracker(TrackerInterface &$interface) {
        $this->trackers[] = $interface;
    }

    /**
     * Build an array of all the default data we'll need for most events.
     *
     * @return array
     */
    public function getDefaultData() {

        // Basic information that should be universally available
        $timestamp = gmmktime();
        $defaults = [
            'dateTime' => [
                'day' => (int)date('w', $timestamp),
                'date' =>(int) date('j', $timestamp),
                'month' => (int)date('n', $timestamp),
                'year' => (int)date('Y', $timestamp),
                'hour' => (int)date('G', $timestamp),
                'minute' => (int)date('i', $timestamp),
                'timezone' => date('T', $timestamp),
                'timestamp' => $timestamp,
                'iso8601' => date('c', $timestamp)
            ],
            'ip' => Gdn::request()->ipAddress(),
            'method' => Gdn::request()->requestMethod(),
            'url' => [
                'scheme' => Gdn::request()->scheme(),
                'domain' => parse_url(Gdn::request()->domain(), PHP_URL_HOST),
                'path' => Gdn::request()->path(),
            ]
        ];

        // Only add user-related information if a user is signed in.
        if (Gdn::session()->isValid()) {
            $defaults['user'] = [
                'userID'         => Gdn::session()->UserID,
                'name'       => val('Name', Gdn::session()->User),
                'dateFirstVisit' => val('DateFirstVisit', Gdn::session()->User)
            ];
        }

        // Attempt to grab the referrer, if there is one, and record it.
        $referrer = Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_REFERER');
        if ($referrer && $referrerParsed = parse_url($referrer)) {
            $defaults['referer'] = $referrerParsed;
        }

        // Grab the browser's user agent value, if available.
        if ($userAgent = Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_USER_AGENT')) {
            try {
                $defaults['userAgent'] = parse_user_agent($userAgent);

                // Attempt to grab the browser's major version number, if possible.
                if (!empty($defaults['userAgent']['version']) &&
                    preg_match('#^(?P<major>\d+)\.#', $defaults['userAgent']['version'], $version)) {
                    $defaults['userAgent']['majorVersion'] = (int)$version['major'];
                }
            } catch (\InvalidArgumentException $e) {
                // The function used to parse the user agent may throw a InvalidArgumentException exception.
            }
        }

        return $defaults;
    }

    /**
     * Return the singleton instance of our object.  Create a new one if it doesn't exist.
     * @return AnalyticsTracker
     */
    public static function getInstance() {
        if (empty(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Tracks an event.  Calls analytics service interfaces to record event details.
     * @param $event
     * @param array $data
     */
    public function trackEvent($event, $data = array()) {
        // Load up the defaults we'd like to have and merge them into the data.
        $data = array_merge($this->getDefaultData(), $data);

        // Iterate through our tracker list and tell each of them about our event.
        foreach ($this->trackers as $interface) {
            $interface->event($event, $data);
        }
    }
}
