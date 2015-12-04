$(document).ready(function() {
    var projectID = gdn.meta["keenio.projectID"] || false;
    var writeKey = gdn.meta["keenio.writeKey"] || false;

    if (projectID && writeKey && typeof Keen == "function") {
        window.keenClient = new Keen({
            projectId: projectID,
            writeKey: writeKey,
        });

        eventData = gdn.meta.eventData || false;

        if (eventData) {
            keenClient.addEvent(
                "pageView",
                eventData,
                function(error, response){
                    // If error isn't a falsy, an error was encountered.
                }
            );
        }
    }
});
