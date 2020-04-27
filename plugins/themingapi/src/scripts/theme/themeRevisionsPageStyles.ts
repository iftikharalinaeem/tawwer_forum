/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit, colorOut } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { metasVariables } from "@library/styles/metasStyles";

export const themeRevisionPageClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("themeEditorPage");
    const vars = metasVariables();

    const userNameFont = style("userName", {
        fontWeight: globalVars.fonts.weights.semiBold,
        paddingBottom: unit(5),
        lineHeight: unit(14),
    });
    const dateFont = style("userName", {
        fontSize: unit(vars.fonts.size),
        color: colorOut(vars.colors.fg),
    });

    const itemLabel = style("checkbox", {
        flexGrow: 1,
        display: "block",
        paddingBottom: unit(2),
    });

    return {
        userNameFont,
        itemLabel,
        dateFont,
    };
});
