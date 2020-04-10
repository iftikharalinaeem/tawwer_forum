/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { paddings, margins, unit, colorOut } from "@library/styles/styleHelpers";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles";

export const deliveryDetailsCSSClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("deliveryDetails");

    const root = style({
        $nest: {
            "&&": {
                padding: 0,
            },
            "&:hover, &:focus": {
                backgroundColor: colorOut(globalVars.mainColors.bg),
            },
        },
    });

    const title = style("title", {
        $nest: {
            "&&": {
                ...margins({
                    top: unit(0),
                    bottom: globalVars.gutter.size,
                }),
                fontSize: globalVars.fonts.size.medium,
            },
        },
    });

    cssOut(`${title} + .userContent`, {
        ...margins({
            bottom: unit(26),
        }),
    });

    return { title, root };
});
