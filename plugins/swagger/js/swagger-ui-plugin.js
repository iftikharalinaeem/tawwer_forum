jQuery(document).ready(function($) {
    var HideTopbarPlugin = function () {
        // This plugin overrides the Topbar component to return nothing
        return {
            components: {
                contentType: function() { return null },
                info: function() { return null },
                Topbar: function() { return null }
            }
        }
    };

    window.ui = SwaggerUIBundle({
        url: gdn.url("/api/v2/swagger"),
        dom_id: "#swagger-ui",
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
        requestInterceptor: function (request) {
            request.headers["x-transient-key"] = gdn.getMeta("TransientKey");
            return request;
        }
    });

});
