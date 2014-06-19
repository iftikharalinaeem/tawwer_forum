/*!
 * Slidey - A theme with a focus on content, hiding superfluous content in a hidden sliding sidepanel.
 *
 * @author    Rebecca Van Bussel <rvanbussel@vanillaforums.com>
 * @copyright 2014 (c) Rebecca Van Bussel
 * @license   Proprietary
 */



$(document).on('click', '.panel-arrow', function (e) {
    "use strict";

    if ($("#Panel").hasClass("is-out")) {
        $("#Panel").removeClass("is-out");
        $(".panel-arrow").removeClass("is-right");
    }

    else {
        $("#Panel").addClass("is-out");
        $(".panel-arrow").addClass("is-right");
    }
});

$(document).ready(function () {
    "use strict";
    $('.page-title').html($('#Content h1').html());
});
