<?php
/**
 * AnalyticsTracker class file.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanillaanalytics
 */

/**
 * The core of event tracking.  This singleton class is intended to handle dispatching individual service trackers.
 */
class AnalyticsTracker {

    /**
     * @var bool Used to determine if we should avoid tracking events.
     */
    private $disableTracking = false;

    /**
     * @var AnalyticsTracker Holds the instance for our singleton
     */
    private static $instance;

    /**
     * @var array An array containing instances of individual service tracker interfaces.
     */
    protected $trackers = [];

    /**
     * @var array An array of class names for available analytics service trackers.
     */
    protected $trackerClasses = [
        'KeenIOTracker'
    ];

    /**
     * @var array An array containing the user's unique ID and session ID values.
     */
    protected $trackingIDs = null;

    /**
     * Our constructor.
     */
    protected function __construct() {
        $this->disableTracking = (bool)c('VanillaAnalytics.DisableTracking', false);

        $trackerClasses = $this->getTrackerClasses();
        foreach ($trackerClasses as $currentTrackerClass) {
            if (call_user_func("{$currentTrackerClass}::isConfigured")) {
                $this->addTracker(new $currentTrackerClass);
            }
        }

        if (count($this->trackers) == 0) {
            Logger::event('vanilla_analytics', Logger::DEBUG, 'No tracking services available for recording analytics data.');
        }
    }

    /**
     * Allow trackers to add CSS files to the current page.
     *
     * @param Gdn_Controller $controller Instance of the current page's controller.
     */
    public function addCssFiles(Gdn_Controller $controller) {
        $inDashboard = $controller->MasterView == 'admin';

        if ($inDashboard) {
            $controller->addCssFile('vanillicon.css', 'static');
            $controller->addCssFile('dashboard.css', 'plugins/vanillaanalytics');
        }

        foreach ($this->trackers as $tracker) {
            $tracker->addCssFiles($controller, $inDashboard);
        }
    }

    /**
     * Call service trackers to add values to the gdn.meta JavaScript array
     *
     * @param Gdn_Controller Instance of the current controller.
     */
    public function addDefinitions(Gdn_Controller $controller) {
        $inDashboard = $controller->MasterView == 'admin';

        foreach ($this->trackers as $tracker) {
            $tracker->addDefinitions($controller, $inDashboard);
        }

        $eventData = $this->getPageViewData($controller);

        $controller->addDefinition('vaCookieName', c('Garden.Cookie.Name') . '-vA');
        $controller->addDefinition('eventData', $eventData);
    }

    /**
     * Call service trackers to add their own JavaScript files to the page.
     *
     * @param Gdn_Controller Instance of the current controller.
     */
    public function addJsFiles(Gdn_Controller $controller) {
        $inDashboard = $controller->MasterView == 'admin';

        $controller->addJsFile('vendors/js.cookie.js', 'plugins/vanillaanalytics');

        foreach ($this->trackers as $interface) {
            $interface->addJsFiles($controller, $inDashboard);
        }
    }

    /**
     * Adds a new tracker instance to the collection.
     *
     * @param TrackerInterface $interface
     * @return AnalyticsTracker
     */
    public function addTracker(TrackerInterface $interface) {
        $this->trackers[] = $interface;

        return $this;
    }

    /**
     * Fetch default data to populate a tracking cookie.
     *
     * @param array $eventData If an event is being tracked via cookie, pass the details here.
     * @return array
     */
    public function getCookieData(array $eventData = []) {
        return [
            'eventData'   => $eventData,
            'trackingIDs' => $this->trackingIDs()
        ];
    }

    /**
     * Grab an array representing available default analytics widgets.
     *
     * @return array
     */
    public function getDefaultWidgets() {
        $widgets = [];

        foreach ($this->trackers as $interface) {
            $interface->addWidgets($widgets);
        }

        return $widgets;
    }

    /**
     * Build an array of all the default data we'll need for most events.
     *
     * @param bool $trackerDefaults Should defaults from enabled trackers be included?
     * @return array
     */
    public function getDefaultData($trackerDefaults = false) {

        // Basic information that should be universally available
        $defaults = [
            'dateTime' => AnalyticsData::getDateTime(),
            'ip'       => Gdn::request()->ipAddress(),
            'method'   => Gdn::request()->requestMethod(),
            'site'     => AnalyticsData::getSite(),
            'url'      => url('', '/')
        ];

        // Only add user-related information if a user is signed in.
        $defaults['user'] = AnalyticsData::getCurrentUser();

        // Attempt to grab the referrer, if there is one, and record it.
        $referrer = Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_REFERER');
        $defaults['referrer'] = $referrer ?: null;

        // Grab the browser's user agent value, if available.
        $userAgent = Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_USER_AGENT');
        $defaults['userAgent'] = $userAgent ?: null;

        if ($trackerDefaults) {
            foreach ($this->trackers as $tracker) {
                $defaults = $tracker->addDefaults($defaults);
            }
        }

        $this->EventArguments['Defaults']   =& $defaults;

        Gdn::pluginManager()->callEventHandlers(
            $this,
            'AnalyticsTracker',
            'GetDefaultData'
        );

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
     * Fetch data for use in a page view event.
     *
     * @param Gdn_Controller $controller Current page controller instance.
     * @return array
     */
    public function getPageViewData(Gdn_Controller $controller) {
        $eventData = $this->getDefaultData(true);

        // Figure out if we have a discussion.  If we do, include it in the event data.
        if ($discussion = $controller->data('Discussion', false)) {
            $eventData['discussion'] = AnalyticsData::getDiscussion(val('DiscussionID', $discussion, 0));
        } else {
            $eventData['discussion'] = [
                'discussionID' => 0
            ];
        }

        return $eventData;
    }

    /**
     * Grab a list of all available analytics service tracker classes.
     *
     * @return array An array of available analytics service tracker classes.
     */
    public function getTrackerClasses() {
        return $this->trackerClasses;
    }

    /**
     * Setup routine, called when plug-in is enabled.
     */
    public function setup() {
        $trackerClasses = $this->getTrackerClasses();

        // Allow each individual tracker to make preparations.
        foreach ($trackerClasses as $currentTrackerClass) {
            $tracker = new $currentTrackerClass;
            $tracker->setup();
        }
    }

    /**
     * Tracks an event.  Calls analytics service interfaces to record event details.
     *
     * @param string $collection Bucket to place the event data into
     * @param string $event Name of the event
     * @param array $data Specific details about the event
     */
    public function trackEvent($collection, $event, $data = array()) {
        if ($this->trackingDisabled()) {
            return;
        }

        // Load up the defaults we'd like to have and merge them into the data.
        $defaults = $this->getDefaultData();

        // Allow other add-ons to plug-in and modify the event.
        $this->EventArguments['Collection'] =& $collection;
        $this->EventArguments['Event']      =& $event;
        $this->EventArguments['Data']       =& $data;
        $this->EventArguments['Defaults']   =& $defaults;

        Gdn::pluginManager()->callEventHandlers(
            $this,
            'AnalyticsTracker',
            'BeforeTrackEvent'
        );

        // Iterate through our tracker list and tell each of them about our event.
        foreach ($this->trackers as $interface) {
            $interfaceDefaults = $interface->addDefaults($defaults);
            $details = array_merge($interfaceDefaults, $data);

            $interface->event($collection, $event, $details);

            unset($interfaceDefaults, $details);
        }
    }

    /**
     * Is event tracking disabled?
     *
     * @return bool True on disabled, false on enabled
     */
    public function trackingDisabled() {
        return $this->disableTracking;
    }

    /**
     * Set and get tracking ID detail array.
     *
     * @param string|null $uuid Universally unique ID for users.
     * @param string|null $sessionID Unique ID for tracking user sessions.
     * @return array UUID and session ID values for the current user.
     */
    public function trackingIDs() {
        // No tracking IDs?  Well, let's fix that...
        if (is_null($this->trackingIDs)) {
            // Start with an empty template.
            $this->trackingIDs = [
                'sessionID' => null,
                'uuid'      => null
            ];

            // Fetch our tracking cookie.
            $cookieIDsRaw = Gdn::session()->getCookie('-vA', false);

            // Does the tracking cookie contain valid JSON?
            if ($cookieIDs = @json_decode($cookieIDsRaw)) {
                // Do we already have a UUID for this user?
                if ($uuid = val('uuid', $cookieIDs)) {
                    // Is the user logged, but their UUID doesn't match what we have on file?
                    if (Gdn::session()->isValid() && AnalyticsData::getUserUuid() != $uuid) {
                        // Log the mismatch and update the UUID to the one we have saved for the user.
                        Logger::event('vanilla_analytics', Logger::DEBUG, 'User UUID mismatch.');
                        $uuid = AnalyticsData::getUserUuid();
                    }

                    $this->trackingIDs['uuid'] = $uuid;
                } else {
                    // No UUID? No problem.  Create a new one.
                    $this->trackingIDs['uuid'] = AnalyticsData::getUserUuid();
                }

                // Is this *not* a new visit and we have an existing session ID in our cookie?
                if (!Gdn::session()->newVisit() && $sessionID = val('sessionID', $cookieIDs)) {
                    $this->trackingIDs['sessionID'] = $sessionID;
                } else {
                    // New session or no existing session ID? Create a new one.
                    $this->trackingIDs['sessionID'] = AnalyticsData::uuid();
                }
            } else {
                // We've got nothing.  Start from scratch.
                $this->trackingIDs = [
                    'sessionID' => AnalyticsData::uuid(),
                    'uuid'      => AnalyticsData::getUserUuid()
                ];
            }
        }

        return $this->trackingIDs;
    }
}
