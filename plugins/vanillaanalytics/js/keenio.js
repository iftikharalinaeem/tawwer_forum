$(document).ready(function() {
    var projectID = gdn.meta["keenio.projectID"] || false;
    var writeKey = gdn.meta["keenio.writeKey"] || false;

    if (projectID && writeKey && typeof Keen == "function") {
        window.keenClient = new Keen({
            projectId: projectID,
            writeKey: writeKey
        });

        // If our cookie library is available, check to see if we have event data hiding in a cookie.
        if (Cookies) {
            var cookieName  = gdn.definition('vaCookieName');
            var cookieValue = Cookies.get(cookieName);

            // Extract the event data, if available, and reset the cookie.  We only need to access it once.
            eventData = cookieValue ? JSON.parse(cookieValue) : false;
            Cookies.remove(cookieName);
        }

        /**
         * If eventData still hasn't been set, it means our attempt to grab event details from the cookie failed.  Try
         * to grab them from the gdn.meta collection.
         */
        if (!eventData) {
            eventData = gdn.meta.eventData || false;
        }

        // If we get this far and still don't have any event data, there's nothing to get.
        if (eventData) {
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
