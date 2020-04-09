/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { paddings, margins, unit, colorOut } from "@library/styles/styleHelpers";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";

export const DeliveryDetailsCSSClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("ResponseDetails");

    const root = style({
        $nest: {
            "&:hover, &:focus": {
                backgroundColor: colorOut(globalVars.mainColors.bg),
            },
        },
    });

    const title = style("title", {
        $nest: {
            "&&": {
                ...margins({
                    top: unit(26),
                    bottom: globalVars.gutter.size,
                }),
                fontSize: globalVars.fonts.size.medium,
            },
        },
    });

    return { title, root };
});
