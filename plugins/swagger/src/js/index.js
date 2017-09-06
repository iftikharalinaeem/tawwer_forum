import {SwaggerUIBundle, SwaggerUIStandalonePreset} from 'swagger-ui-dist';

jQuery(document).ready(function($) {

    window.ui = SwaggerUIBundle({
        url: gdn.url('/api/v2/swagger'),
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset
        ],
        plugins: [
            SwaggerUIBundle.plugins.DownloadUrl
        ],
        layout: "StandaloneLayout",
        validatorUrl: null,
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
