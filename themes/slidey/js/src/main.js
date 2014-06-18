/*!
 * Slidey - A theme with a focus on content, hiding superfluous content in a hidden sliding sidepanel.
 *
 * @author    Rebecca Van Bussel <rvanbussel@vanillaforums.com>
 * @copyright 2014 (c) Rebecca Van Bussel
 * @license   Proprietary
 */



function slide() {
    "use strict";

    if ($("#Panel").css("right") == "-260px") {
        $("#Panel").css( "right", "0" );
        $("#panel-arrow").css({
          "transform": "rotate(180deg)"
        })
    }

    else {
        $("#Panel").css( "right", "-260px" );
        $("#panel-arrow").css({
            "transform": "none"
        })
    }
}
