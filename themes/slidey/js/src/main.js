/*!
 * Slidey - A theme with a focus on content, hiding superfluous content in a hidden sliding sidepanel.
 *
 * @author    Rebecca Van Bussel <rvanbussel@vanillaforums.com>
 * @copyright 2014 (c) Rebecca Van Bussel
 * @license   Proprietary
 */


$(document).on('click', '.js-panel-arrow', function (e) {
    "use strict";

    if ($("#Panel").hasClass("is-out")) {
        $("#Panel").removeClass("is-out");
        $(e.currentTarget).removeClass("is-right");
    }

    else {
        $("#Panel").addClass("is-out");
        $(".panel-arrow").addClass("is-right");
    }
    e.preventDefault();
});
