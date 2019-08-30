/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, flexHelper, unit } from "@library/styles/styleHelpers";
import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { GlobalsNumber } from "csstype";
import { important, percent, px, ColorHelper, em } from "csx";
import { setAllLinkColors } from "@library/styles/styleHelpers";

export const articleTOCClasses = useThemeCache(() => {
    const style = styleFactory("articleReactions");
    const globalVars = globalVariables();

    const link = style({
        color: colorOut(globalVars.meta.colors.fg),
        fontSize: unit(globalVars.fonts.size.medium),
    });

    const item = style({
        $nest: {
            [`&.isActive .${link}`]: {
                fontWeight: globalVars.fonts.weights.bold,
            },
        },
    });

    return {
        link,
        item,
    };
});
