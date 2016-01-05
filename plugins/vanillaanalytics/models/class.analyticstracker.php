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
     * @return
     */
    public function addTracker(TrackerInterface $interface) {
        $this->trackers[] = $interface;

        return $this;
    }

    /**
     * Build an array of all the default data we'll need for most events.
     *
     * @return array
     */
    public function getDefaultData() {

        // Basic information that should be universally available
        $defaults = [
            'dateTime' => AnalyticsData::getDateTime(),
            'ip'       => Gdn::request()->ipAddress(),
            'method'   => Gdn::request()->requestMethod(),
            'site'     => AnalyticsData::getSite(),
            'url'      => Gdn::request()->url(
                Gdn::request()->pathAndQuery(),
                true
            )
        ];

        // Only add user-related information if a user is signed in.
        $defaults['user'] = AnalyticsData::getCurrentUser();

        // Attempt to grab the referrer, if there is one, and record it.
        $referrer = Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_REFERER');
        $defaults['referrer'] = $referrer ?: null;

        // Grab the browser's user agent value, if available.
        $userAgent = Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_USER_AGENT');
        $defaults['userAgent'] = $userAgent ?: null;


        foreach ($this->trackers as $interface) {
            $interface->addDefaultData($defaults);
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
    public function trackEvent($collection, $event, $data = array()) {
        // Load up the defaults we'd like to have and merge them into the data.
        $data = array_merge($this->getDefaultData(), $data);

        // Iterate through our tracker list and tell each of them about our event.
        foreach ($this->trackers as $interface) {
            $interface->event($collection, $event, $data);
        }
    }
}
