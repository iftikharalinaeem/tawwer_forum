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
    const vars = globalVariables();

    const frame = style({
        paddingTop: px(12),
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
    });

    return {
        link,
    };
});
