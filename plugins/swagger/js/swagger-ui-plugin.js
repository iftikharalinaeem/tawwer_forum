jQuery(document).ready(function($) {
    var HideTopbarPlugin = function () {
        // this plugin overrides the Topbar component to return nothing
        return {
            components: {
                Topbar: function() { return null }
            }
        }
    }

    window.ui = SwaggerUIBundle({
        url: gdn.url('/api/v2/swagger'),
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset
        ],
        plugins: [
            SwaggerUIBundle.plugins.DownloadUrl,
            HideTopbarPlugin
        ],
        layout: "StandaloneLayout",
        validatorUrl: null,
        // filter: true,
        requestInterceptor: function (request) {
            request.headers['x-transient-key'] = gdn.getMeta('TransientKey');
        }
    });

});
//
// (function(window, $) {
//     $(document).on("ajaxSend", function (e, f, g) {
//         console.log(e);
//     });
// })(window, jQuery);
