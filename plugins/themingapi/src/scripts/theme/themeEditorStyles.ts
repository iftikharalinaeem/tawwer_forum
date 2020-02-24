/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { color, percent } from "csx";
import { colorOut, unit } from "@vanilla/library/src/scripts/styles/styleHelpers";
import { layoutVariables } from "@vanilla/library/src/scripts/layout/panelLayoutStyles";

export const themeEditorVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("themeEditor");
    const colors = makeThemeVars("colors", {
        bg: color("#f5f6f7"),
    });

    const frame = makeThemeVars("frame", {
        width: 80,
    });
    const styleOptions = makeThemeVars("styleOptions", {
        width: 20,
    });

    return {
        colors,
        frame,
        styleOptions,
    };
});
export const themeEditorClasses = useThemeCache(() => {
    const vars = themeEditorVariables();
    const style = styleFactory("themeEditor");

    const mediaQueries = layoutVariables().mediaQueries();
    const wrapper = style(
        "wrapper",
        {
            display: "flex",
        },
        mediaQueries.oneColumnDown({
            display: "block",
        }),
    );
    const frame = style(
        "frame",
        {
            width: percent(80),
        },
        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );
    const styleOptions = style(
        "styleOptions",
        {
            backgroundColor: colorOut(vars.colors.bg),
            boxShadow: "0 5px 10px 0 rgba(0, 0, 0, 0.3)",
            maxHeight: unit(500),
            height: unit(500),
            width: percent(20),
        },
        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );

    return {
        frame,
        wrapper,
        styleOptions,
    };
});

export default themeEditorClasses;
