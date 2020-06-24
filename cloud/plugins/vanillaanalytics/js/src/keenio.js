/**
 * @class
 * @property {boolean|Keen} keenClient Instance of a Keen object.
 * @property {string} projectID Project ID for a valid project on keen.io.
 * @property {string} writeKey An API key with write access to the configured project.
 */
var keenTracker = {
    keenClient: false,
    projectID : gdn.meta["keenio.projectID"] || false,
    writeKey  : gdn.meta["keenio.writeKey"] || false
};

/**
 *
 * @memberof keenTracker
 * @param {object} event
 * @param {object} sendData Data sent by the browser as part of the analyticstick request.
 * @param {jqXHR} jqXHR Superset of the browser's native XMLHttpRequest object.
 * @param {string} textStatus Status of the request (e.g. success, error, timeout)
 * @link http://api.jquery.com/jQuery.ajax/#jqXHR
 */
keenTracker.analyticsTickHandler = function(event, sendData, jqXHR, textStatus) {
    // Only track the page view if the hit to analyticstick was a success.
    if (textStatus === 'success') {
        if (typeof jqXHR.responseJSON !== "undefined") {
            var eventData = gdn.definition('eventData', {});
            if (typeof jqXHR.responseJSON.clientIP !== "undefined") {
                eventData.ip = jqXHR.responseJSON.clientIP;
            }
            if (typeof jqXHR.responseJSON.dateTime !== "undefined") {
                eventData.dateTime = jqXHR.responseJSON.dateTime;
            }
            eventData.referrer = document.referrer;
            gdn.meta.eventData = eventData;
        }
        keenTracker.event(gdn.definition("viewEventType", "page_view"), "page", true);
    }
};

/**
 * Log an event with keen.io.
 *
 * @memberof keenTracker
 * @param {string} eventType The type/name of the event being tracked.
 * @param {string} collection The collection to store the event under.  Defaults to "page".
 * @param {boolean} isGuestCollection Will this data be used in a collection that contains guest data?
 */
keenTracker.event = function(eventType, collection, isGuestCollection) {
    // Load up our API client and the event data from the page.
    var client = this.getKeenClient();
    var eventData = gdn.definition('eventData', {});

    // Do we have both a usable client and data to send?
    if (client && Object.keys(eventData).length > 0) {
        // Establish the event name/type and augment the user data, if necessary.
        eventData.type = eventType;
        eventData.user = this.getUser(eventData, isGuestCollection);

        // Send everything off to keen.
        client.recordEvent(
            collection || 'page',
            eventData
        );
    }
};

/**
 * Fetch the current instance of our keen.io client or create a new one, if possible.
 *
 * @memberof keenTracker
 * @return {boolean|Keen} An instance of Keen on success.  False (default value) on fail.
 */
keenTracker.getKeenClient = function() {
    /**
     * No existing client instance?
     * Do we have a project ID configured?
     * Do we have a write key configured?
     * Is Keen available to instantiate?
     * If yes to all: Create a client.  Otherwise: Nothing to do here.
     */
    if (!this.keenClient && this.projectID && this.writeKey && typeof Keen == 'function') {
        this.keenClient = new Keen({
            projectId: this.projectID,
            writeKey: this.writeKey
        });
    }

    return this.keenClient;
};

/**
 * Extract the user-specific data from an eventData collection.  Augment it as necessary.
 *
 * @memberof keenTracker
 * @param {object} eventData An object with properties representing specifics of the current event.
 * @param {boolean} isGuestCollection Will this data be used in a collection that contains guest data?
 * @return {object} An object representing the current user.  May be an empty object.
 */
keenTracker.getUser = function(eventData, isGuestCollection) {
    // Defaulting to an empty object.
    var userData = {};

    var extractUserData = function(cookie, data) {
        if (typeof cookie !== 'object') {
            return data;
        }

        // Missing a UUID, but one is available from our cookie? Update it.
        if (typeof cookie.uuid !== 'undefined') {
            data.uuid = cookie.uuid;
        }

        // Missing a session ID, but one is available from our cookie? Update it.
        if (isGuestCollection) {
            if (typeof cookie.secondarySessionID !== 'undefined') {
                data.sessionID = cookie.secondarySessionID;
            }
        } else if (typeof cookie.sessionID !== 'undefined') {
            data.sessionID = cookie.sessionID;
        }

        if (typeof cookie.pv !== 'undefined') {
            data.pv = cookie.pv;
        }

        return data;
    };

    // eventData needs to be a valid object and contain a property of "user", which is also an object.
    if (typeof eventData === 'object' && typeof eventData.user === 'object') {
        userData = eventData.user;

        /**
         * Grab up-to-date data from the analytics cookie.
         */
        if (typeof Cookies === 'function') {
            var cookie = Cookies.getJSON(gdn.definition('vaCookieName'));
            userData = extractUserData(cookie, userData);
        }
    }

    return userData;
};

// Attach our listener.
$(document).on('analyticsTick', keenTracker.analyticsTickHandler);
