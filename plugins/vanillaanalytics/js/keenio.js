$(document).ready(function() {
    var projectID = gdn.meta["keenio.projectID"] || false;
    var writeKey = gdn.meta["keenio.writeKey"] || false;

    if (projectID && writeKey && typeof Keen == "function") {
        window.keenClient = new Keen({
            projectId: projectID,
            writeKey: writeKey
        });

        var eventData = gdn.meta.eventData || {};

        if (typeof eventData === 'object') {
            if (typeof eventData.user === 'object') {
                var userData = eventData.user;

                if ((typeof userData.uuid === 'undefined' || typeof userData.sessionID === 'undefined') &&
                    typeof Cookies === 'function') {

                    var cookieRaw = Cookies.get(gdn.definition('vaCookieName'));

                    // Extract the event data, if available, and reset the cookie.  We only need to access it once.
                    if (cookieRaw) {
                        var trackingIDs;

                        try {
                            trackingIDs = JSON.parse(cookieRaw);
                        } catch (e) {
                        }

                        if (typeof userData.uuid === 'undefined' && typeof trackingIDs.uuid !== 'undefined') {
                            userData.uuid = trackingIDs.uuid;
                        }

                        if (typeof userData.sessionID === 'undefined' && typeof trackingIDs.sessionID !== 'undefined') {
                            userData.sessionID = trackingIDs.sessionID;
                        }
                    }
                }
            }

            // If we get this far and still don't have any event data, there's nothing to get.
            if (Object.keys(eventData).length > 0) {
                eventData.type = 'page_view';

                keenClient.addEvent(
                    'page',
                    eventData,
                    function (error, response) {
                        // If error isn't a falsy, an error was encountered.
                    }
                );
            }
        }
    }
});
