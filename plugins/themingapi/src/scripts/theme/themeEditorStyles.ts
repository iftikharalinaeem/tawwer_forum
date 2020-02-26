/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { color, percent, calc } from "csx";
import { colorOut, unit, absolutePosition } from "@vanilla/library/src/scripts/styles/styleHelpers";
import { layoutVariables } from "@vanilla/library/src/scripts/layout/panelLayoutStyles";
import titleBarNavClasses from "@vanilla/library/src/scripts/headers/titleBarNavStyles";

export const themeEditorVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("themeEditor");
    const colors = makeThemeVars("colors", {
        bg: color("#f5f6f7"),
    });

    const frame = makeThemeVars("frame", {
        width: 100,
    });
    const styleOptions = makeThemeVars("styleOptions", {
        width: 376,
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
            ...absolutePosition.fullSizeOfParent(),
            width: percent(100),
            height: percent(100),
            $nest: {
                "&&&": {
                    display: "flex",
                },
            },
        },
        mediaQueries.oneColumnDown({
            display: "block",
        }),
    );
    const frame = style(
        "frame",
        {
            width: calc(`${percent(vars.frame.width)} - ${unit(vars.styleOptions.width)}`),
            flexBasis: calc(`${percent(vars.frame.width)} - ${unit(vars.styleOptions.width)}`),
            height: percent(100),
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
            width: unit(vars.styleOptions.width),
            flexBasis: unit(vars.styleOptions.width),
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
