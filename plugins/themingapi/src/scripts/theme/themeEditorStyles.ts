/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { margins, unit, negative } from "@library/styles/styleHelpers";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";

export const themeEditorVariables = useThemeCache(() => {
    const makeThemeEditorVars = variableFactory("themeEditor");
});
export const themeEitorClasses = useThemeCache(() => {
    const vars = themeEditorVariables();
    const globalVars = globalVariables();
    const style = styleFactory("themeEditor");

    const editIcon = style("editIcon", {
        verticalAlign: "bottom",
    });

    const themeName = style("themeName", {
        display: "flex",
        fontWeight: globalVars.fonts.weights.semiBold,
    });

    const themeInput = style("themeInput", {
        $nest: {
            "&&": {
                padding: "0",
                border: "0",
                width: unit(145),
                fontSize: globalVars.fonts.size.medium,
                fontWeight: globalVars.fonts.weights.semiBold,
            },
        },
    });
    const inputWrapper = style("inputWrapper", {
        $nest: {
            "&&&": {
                margin: 0,
            },
        },
    });
    return {
        editIcon,
        themeName,
        themeInput,
        inputWrapper,
    };
});

export default themeEitorClasses;
