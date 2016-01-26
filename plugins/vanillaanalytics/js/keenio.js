$(document).ready(function() {
    var projectID = gdn.meta["keenio.projectID"] || false;
    var writeKey = gdn.meta["keenio.writeKey"] || false;

    if (projectID && writeKey && typeof Keen == "function") {
        window.keenClient = new Keen({
            projectId: projectID,
            writeKey: writeKey
        });

        var eventData = gdn.meta.eventData || {};

        // If our cookie library is available, check to see if we have event data hiding in a cookie.
        if (typeof Cookies === 'function') {
            var cookieName  = gdn.definition('vaCookieName');
            var cookieValue = Cookies.get(cookieName);

            // Extract the event data, if available, and reset the cookie.  We only need to access it once.
            if (cookieValue) {
                var cookieData = JSON.parse(cookieValue);

                if (typeof cookieData.eventData === 'object' && Object.keys(cookieData.eventData).length > 0) {
                    $.extend(eventData, cookieData.eventData);
                    cookieData.eventData = {};
                    cookieValue = JSON.stringify(cookieData);
                }
            }

            Cookies.set(cookieName, cookieValue);
        }

        // If we get this far and still don't have any event data, there's nothing to get.
        if (typeof eventData === 'object' && Object.keys(eventData).length > 0) {
            eventData.type = 'page_view';

            keenClient.addEvent(
                "page",
                eventData,
                function(error, response){
                    // If error isn't a falsy, an error was encountered.
                }
            );
        }
    }
});
