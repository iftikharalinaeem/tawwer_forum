/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { color, percent } from "csx";
import { colorOut, unit } from "@vanilla/library/src/scripts/styles/styleHelpers";

export const themeColorPickerVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("themeColorPicker");
    const colors = makeThemeVars("colors", {
        bg: color("#f5f6f7"),
    });

    return {
        colors,
    };
});
export const themeColorPickerClasses = useThemeCache(() => {
    const vars = themeColorPickerVariables();
    const globalVars = globalVariables();
    const style = styleFactory("themeColorPicker");

    const wrapper = style("wrapper", {
        display: "flex",
    });
    const frame = style("frame", {
        width: percent(80),
    });
    const options = style("options", {
        backgroundColor: colorOut(vars.colors.bg),
        height: unit(500), //needs to go
        width: percent(20),
    });

    return {
        frame,
        wrapper,
        options,
    };
});

export default themeColorPickerClasses;
