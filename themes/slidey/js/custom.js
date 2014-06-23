/*!
 * Slidey - A theme with a focus on content, hiding superfluous content in a hidden sliding sidepanel.
 *
 * @author    Rebecca Van Bussel <rvanbussel@vanillaforums.com>
 * @copyright 2014 (c) Rebecca Van Bussel
 * @license   Proprietary
 */


$(document).on('click', '.js-panel-trigger', function (e) {
    "use strict";

    e.preventDefault();

    $("#Panel").toggleClass("is-out");
});
