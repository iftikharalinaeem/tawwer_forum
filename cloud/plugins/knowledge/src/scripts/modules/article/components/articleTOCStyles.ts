/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { useThemeCache, styleFactory } from "@library/styles/styleUtils";

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
