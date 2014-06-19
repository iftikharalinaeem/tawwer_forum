/*!
 * Slidey - A theme with a focus on content, hiding superfluous content in a hidden sliding sidepanel.
 *
 * @author    Rebecca Van Bussel <rvanbussel@vanillaforums.com>
 * @copyright 2014 (c) Rebecca Van Bussel
 * @license   Proprietary
 */



$(document).on('click', '.js-panel-arrow', function (e) {
    "use strict";

    if ($("#Panel").css("right") == "-260px") {
        $("#Panel").css( "right", "0" );
        $(".js-panel-arrow").css({
          "transform": "rotate(180deg)"
        })
    }

    else {
        $("#Panel").css( "right", "-260px" );
        $(".js-panel-arrow").css({
            "transform": "none"
        })
    }
});

$(document).ready(function () {
  "use strict";
  $('.page-title').html($('#Content h1').html());
});
