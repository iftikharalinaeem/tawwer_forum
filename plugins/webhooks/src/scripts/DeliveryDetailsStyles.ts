/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { margins, unit, colorOut, defaultTransition } from "@library/styles/styleHelpers";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles";

export const deliveryDetailsCSSClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("deliveryDetails");

    const root = style({
        $nest: {
            "&&": {
                padding: 0,
                height: 0,
                overflow: "hidden",
                willChange: "height",
                ...defaultTransition("height"),
            },
            "&&.isActive": {
                height: "auto",
            },
            "&:hover, &:focus": {
                backgroundColor: colorOut(globalVars.mainColors.bg),
            },
            ".userContent": {
                ...margins({
                    bottom: unit(26),
                }),
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
