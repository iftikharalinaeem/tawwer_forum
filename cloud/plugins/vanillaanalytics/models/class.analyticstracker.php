<?php
/**
 * AnalyticsTracker class file.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */

/**
 * The core of event tracking.  This singleton class is intended to handle dispatching individual service trackers.
 */
class AnalyticsTracker {

    /** Analytics cookie suffix. */
    const COOKIE_SUFFIX = '-vA';

    /** Fallback value for when the privacy config is not explicitly set. */
    const PRIVACY_CONFIG_DEFAULT = 'eu';

    /** @var \Vanilla\Analytics\Cookie */
    private $cookie;

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
     * Our constructor.
     */
    protected function __construct() {
        $this->disableTracking = (bool)c('VanillaAnalytics.DisableTracking', false);

        $trackerClasses = $this->getTrackerClasses();
        foreach ($trackerClasses as $currentTrackerClass) {
            if (call_user_func("{$currentTrackerClass}::isConfigured", $this->disableTracking)) {
                $this->addTracker(new $currentTrackerClass);
            }
        }

        if (count($this->trackers) == 0) {
            Logger::event('vanilla_analytics', Logger::DEBUG, 'No tracking services available for recording analytics data.');
        }

        $this->cookie = new Vanilla\Analytics\Cookie(Gdn::session());
        $this->cookie->loadCookie(self::COOKIE_SUFFIX);
    }

    /**
     * Allow trackers to add CSS files to the current page.
     *
     * @param Gdn_Controller $controller Instance of the current page's controller.
     */
    public function addCssFiles(Gdn_Controller $controller) {
        $inDashboard = $controller->MasterView == 'admin';

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

        $eventData = $this->getPageViewData($controller);

        // Pages served to guests can be cached on infrastructure. Some values need to be reset/removed to avoid inaccuracies.
        if (!Gdn::session()->isValid()) {
            $eventData['ip'] = '0.0.0.0';
            unset($eventData['dateTime'], $eventData['referrer'], $eventData['user']['pv']);
        }

        foreach ($this->trackers as $tracker) {
            $tracker->addDefinitions($controller, $inDashboard, $eventData);
        }

        $controller->addDefinition('vaCookieName', c('Garden.Cookie.Name') . '-vA');
        $controller->addDefinition('eventData', $eventData);
        $controller->addDefinition('viewEventType', AnalyticsData::getViewEventType());
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
     * @param  bool $isGuestCollection Will this data be used in a collection that contains guest data?
     * @return array
     */
    public function getDefaultData($trackerDefaults = false, bool $isGuestCollection = false) {

        // Basic information that should be universally available
        $defaults = [
            'dateTime' => AnalyticsData::getDateTime(),
            'ip' => anonymizeIP(Gdn::request()->ipAddress()),
            'method' => Gdn::request()->requestMethod(),
            'site' => AnalyticsData::getSite(),
            'url' => url('', true),
            '_country' => $this->getRequestCountry(),
        ];

        // Only add user-related information if a user is signed in.
        $user = AnalyticsData::getCurrentUser($isGuestCollection);
        if ($isGuestCollection && $user['userID'] > 0 && $this->cookie->isPrivacyOptIn() === false) {
            $user = AnalyticsData::anonymizeUser($user);
        }
        $defaults['user'] = $user;

        // Grab the browser's user agent value, if available.
        $userAgent = Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_USER_AGENT');
        $defaults['userAgent'] = $userAgent ?: null;

        if ($trackerDefaults) {
            foreach ($this->trackers as $tracker) {
                $defaults = $tracker->addDefaults($defaults);
            }
        }

        $this->EventArguments['Defaults'] =& $defaults;

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
        $eventData = $this->getDefaultData(true, true);

        // Attempt to grab the referrer, if there is one, and record it.
        $referrer = Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_REFERER');
        $eventData['referrer'] = $referrer ?: null;

        // Figure out if we have a discussion.  If we do, include it in the event data.
        if ($discussion = $controller->data('Discussion', false)) {
            $eventData['discussion'] = AnalyticsData::getDiscussion(val('DiscussionID', $discussion, 0));
        } else {
            $eventData['discussion'] = ['discussionID' => 0];
        }

        return $eventData;
    }

    /**
     * Attempt to get country for the current request.
     *
     * @return string|null A country code in the ISO 3166-1 Alpha 2 format on success. Null on failure.
     */
    public function getRequestCountry() {
        $result = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;
        return $result;
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
     * Based off the site config, determine the default privacy status of a request.
     *
     * @return int
     */
    private function defaultRequestPrivacy() {
        $config = c('VanillaAnalytics.Anonymize', self::PRIVACY_CONFIG_DEFAULT);
        $result = \Vanilla\Analytics\Cookie::PRIVACY_MASK_OPT_OUT;

        if (is_string($config)) {
            $config = explodeTrim(',', $config);
            if (in_array('eu', $config)) {
                $country = $this->getRequestCountry();
                if ($country && $this->countryIsEU($country) === false) {
                    $result = \Vanilla\Analytics\Cookie::PRIVACY_MASK_OPT_IN;
                } elseif (in_array('guest', $config) && Gdn::session()->isValid()) {
                    $result = \Vanilla\Analytics\Cookie::PRIVACY_MASK_OPT_IN;
                }
            } elseif (in_array('guest', $config) && Gdn::session()->isValid()) {
                $result = \Vanilla\Analytics\Cookie::PRIVACY_MASK_OPT_IN;
            }
        }

        return $result;
    }

    /**
     * Given a country code, determine whether or not it is in the European Union.
     *
     * @param string $country
     * @return bool
     */
    private function countryIsEU(string $country) {
        $country = strtoupper($country);
        // Countries in the EU
        $EU = [
            'AT', // Austria
            'BE', // Belgium
            'BG', // Bulgaria
            'HR', // Croatia
            'CY', // Republic of Cyprus
            'CZ', // Czech Republic
            'DK', // Denmark
            'EE', // Estonia
            'FI', // Finland
            'FR', // France
            'DE', // Germany
            'GR', // Greece
            'HU', // Hungary
            'IE', // Ireland
            'IT', // Italy
            'LV', // Latvia
            'LT', // Lithuania
            'LU', // Luxembourg
            'MT', // Malta
            'NL', // Netherlands
            'PL', // Poland
            'PT', // Portugal
            'RO', // Romania
            'SK', // Slovakia
            'SI', // Slovenia
            'ES', // Spain
            'SE', // Sweden
            'GB', // UK
        ];
        $result = in_array($country, $EU);
        return $result;
    }

    /**
     * Update cookies, as necessary.
     */
    public function refreshCookies() {
        $privacy = $this->cookie->getPrivacy();
        $defaultRequestPrivacy = $this->defaultRequestPrivacy();
        $isDefaultPrivacy = ($privacy & \Vanilla\Analytics\Cookie::PRIVACY_MASK_OPT_IN) === $defaultRequestPrivacy;
        if ($privacy === null || ($this->cookie->isPrivacyAuto() && !$isDefaultPrivacy)) {
            // If privacy needs updating, reset it all.
            $privacy = \Vanilla\Analytics\Cookie::PRIVACY_MASK_AUTO | $defaultRequestPrivacy;
            $this->cookie
                ->setPrivacy($privacy)
                ->setSecondarySessionID(null)
                ->setSessionID(null)
                ->setUUID(null)
            ;
        }

        if ($this->cookie->getUUID() === null) {
            $UUID = AnalyticsData::uuid();
            $this->cookie->setUUID($UUID);
        }

        // Update the two session IDs if they're empty or if a new session was detected by Vanilla.
        if (Gdn::session()->isNewVisit()) {
            $this->resetSessionIDs();
        } else {
            if ($this->cookie->getSecondarySessionID() === null) {
                $secondarySessionID = AnalyticsData::uuid();
                $this->cookie->setSecondarySessionID($secondarySessionID);
            }
            if ($this->cookie->getSessionID() === null) {
                $sessionID = AnalyticsData::uuid();
                $this->cookie->setSessionID($sessionID);
            }
        }

        $this->cookie->saveToCookie(self::COOKIE_SUFFIX);
    }

    /**
     * Reset the user's session IDs in the cookie object.
     */
    public function resetSessionIDs() {
        $secondarySessionID = AnalyticsData::uuid();
        $sessionID = AnalyticsData::uuid();
        $this->cookie->setSessionID($sessionID);
        $this->cookie->setSecondarySessionID($secondarySessionID);
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
    public function trackEvent($collection, $event, $data = []) {
        if ($this->trackingDisabled()) {
            return;
        }

        // Load up the defaults we'd like to have and merge them into the data.
        $defaults = $this->getDefaultData();

        // Allow other add-ons to plug-in and modify the event.
        $this->EventArguments['Collection'] =& $collection;
        $this->EventArguments['Event'] =& $event;
        $this->EventArguments['Data'] =& $data;
        $this->EventArguments['Defaults'] =& $defaults;

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
     * @param bool $useSecondarySession Should the secondary session ID be used, instead of the primary session ID?
     * @return array UUID and session ID values for the current user.
     */
    public function trackingIDs(bool $useSecondarySession = false) {
        $sessionID = $useSecondarySession ? $this->cookie->getSecondarySessionID() : $this->cookie->getSessionID();
        $result = [
            'sessionID' => $sessionID,
            'uuid' => $this->cookie->getUUID(),
        ];
        return $result;
    }
}
