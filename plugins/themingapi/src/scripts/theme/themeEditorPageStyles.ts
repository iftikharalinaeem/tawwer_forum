/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit, colorOut } from "@library/styles/styleHelpers";
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

    const editIcon = style("editIcon", {
        verticalAlign: "bottom",
    });

    const themeName = style("themeName", {
        display: "flex",
        fontWeight: globalVars.fonts.weights.semiBold,
        alignItems: "center",
    });

    const hiddenInputMeasure = style("hiddenInputMeasure", {
        visibility: "hidden",
        opacity: 0,
        position: "absolute",
        zIndex: -100,
        width: "auto !important",
    });

    const themeInput = style("themeInput", {
        $nest: {
            "&&": {
                padding: "0",
                border: "0",
                textAlign: "center",
                lineHeight: unit(18),
                fontSize: globalVars.fonts.size.medium,
                fontWeight: globalVars.fonts.weights.semiBold,
                borderBottom: `2px solid ${colorOut(globalVars.elementaryColors.transparent)}`,
            },

            "&&:focus": {
                outline: "none",
                borderBottom: `2px solid ${colorOut(globalVars.mainColors.primary)}`,
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
    const messageBar = style("messageBar", {
        $nest: {
            "&&& .messages-wrap": {
                width: unit(500),
            },
        },
    });
    return {
        editIcon,
        themeName,
        hiddenInputMeasure,
        themeInput,
        inputWrapper,
        messageBar,
    };
});

export default themeEditorPageClasses;
