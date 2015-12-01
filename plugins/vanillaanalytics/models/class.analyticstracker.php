<?php

/**
 * The core of event tracking.  This class is intended to handle dispatching individual service trackers.
 * @package VanillaAnalytics
 */
class AnalyticsTracker {

    private static $instance;

    /**
     * An array containing instances of individual service tracker interfaces.
     * @var array
     * @access protected
     */
    protected $trackers = [];

    protected function __construct() {
        require_once(dirname(__FILE__) . '/../vendor/autoload.php');
    }

    /**
     *
     */
    public function addDefinitions(Gdn_Controller $controller) {
        foreach ($this->trackers as $interface) {
            $interface->addDefinitions($controller);
        }
    }

    /**
     *
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

    public function getDefaultData() {
        $defaults = [
            'url' => [
                'scheme' => Gdn::request()->scheme(),
                'domain' => parse_url(Gdn::request()->domain(), PHP_URL_HOST),
                'path' => Gdn::request()->path(),
            ],
            'ip'        => Gdn::request()->ipAddress(),
            'method'    => Gdn::request()->requestMethod(),
        ];

        if (Gdn::session()->isValid()) {
            $defaults['user'] = [
                'userID'         => Gdn::session()->UserID,
                'name'       => val('Name', Gdn::session()->User),
                'dateFirstVisit' => val('DateFirstVisit', Gdn::session()->User)
            ];
        }

        $referrer = Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_REFERER');
        if ($referrer && $referrerParsed = parse_url($referrer)) {
            $defaults['referer'] = $referrerParsed;
        }

        if ($userAgent = Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_USER_AGENT')) {
            try {
                $defaults['userAgent'] = parse_user_agent($userAgent);
                var_export($defaults['userAgent']);

                if (!empty($defaults['userAgent']['version']) &&
                    preg_match('#^(?P<major>\d+)\.#', $defaults['userAgent']['version'], $version)) {
                    $defaults['userAgent']['majorVersion'] = $version['major'];
                }
            } catch (\InvalidArgumentException $e) {
            }
        }

        return $defaults;
    }

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

        trace($data, "Event: {$event}");
    }
}
