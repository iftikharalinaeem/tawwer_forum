/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { percent } from "csx";

export const themeEditorPageVariables = useThemeCache(() => {
    const makeThemeEditorVars = variableFactory("themeEditorPage");
});
export const themeEditorPageClasses = useThemeCache(() => {
    const vars = themeEditorPageVariables();
    const globalVars = globalVariables();
    const style = styleFactory("themeEditorPage");

    const form = style("form", {
        height: percent(100),
    });
    const editIcon = style("editIcon", {
        verticalAlign: "bottom",
    });

    const themeName = style("themeName", {
        display: "flex",
        fontWeight: globalVars.fonts.weights.semiBold,
        alignItems: "center",
    });

    const themeInput = style("themeInput", {
        $nest: {
            "&&": {
                padding: "0",
                border: "0",
                width: unit(180),
                textAlign: "center",
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
        form,
        editIcon,
        themeName,
        themeInput,
        inputWrapper,
    };
});

export default themeEditorPageClasses;
