/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { paddings } from "@library/styles/styleHelpers";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";

export const webhookAddEditClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("webhookAddEdit");

    const pullRight = style("pullRight", {
        ...paddings({
            vertical: globalVars.gutter.half,
            horizontal: globalVars.gutter.size * 2,
        }),
    });

    const eventRadio = style("eventRadio", {
        fontWeight: globalVars.fonts.weights.normal,
    });

    return { pullRight, eventRadio };
});
