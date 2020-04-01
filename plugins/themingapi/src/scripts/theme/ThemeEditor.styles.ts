/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { themeBuilderVariables } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { layoutVariables } from "@vanilla/library/src/scripts/layout/panelLayoutStyles";
import { absolutePosition, colorOut, fonts, unit } from "@vanilla/library/src/scripts/styles/styleHelpers";
import { calc, percent } from "csx";

// Intentionally not overwritable.
export const themeEditorVariables = () => {
    const frameContainer = {
        width: 100,
    };
    const frame = {
        width: 100,
    };

    const panel = {
        width: 376,
    };
    const styleOptions = {
        width: 376,
        mobile: {
            margin: {
                top: 12,
            },
        },
        borderRaduis: 6,
    };

    return {
        frame,
        panel,
        frameContainer,
        styleOptions,
    };
};

export const themeEditorClasses = useThemeCache(() => {
    const vars = themeEditorVariables();
    const globalVars = globalVariables();
    const themeBuilderVars = themeBuilderVariables();
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
            position: "relative",
            flexBasis: calc(`${percent(vars.frame.width)} - ${unit(vars.panel.width)}`),
            height: percent(100),
        },

        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );

    const shadowTop = style("shadowTop", {
        position: "absolute",
        top: -1,
        height: 1,
        right: 0,
        left: 0,
        boxShadow: "0 2px 10px 2px rgba(0, 0, 0, 0.4)",
    });

    const shadowRight = style("shadowRight", {
        position: "absolute",
        top: 12,
        bottom: 0,
        width: 1,
        right: -1,
        boxShadow: "-2px 0 10px 2px rgba(0, 0, 0, 0.2)",
    });

    const panel = style(
        "panel",
        {
            width: unit(vars.panel.width),
            flexBasis: unit(vars.panel.width),
            zIndex: 1,
            $nest: {
                "& .SelectOne__single-value": {
                    ...fonts(themeBuilderVars.defaultFont),
                },
                "& .SelectOne__value-container, & .SelectOne__menu": {
                    ...fonts(themeBuilderVars.defaultFont),
                },
                "& .suggestedTextInput-option.suggestedTextInput-option.isFocused": {
                    background: colorOut(
                        themeBuilderVars.mainColors.primary.mix(
                            themeBuilderVars.mainColors.bg,
                            globalVars.constants.states.hover.stateEmphasis,
                        ),
                    ),
                },
            },
        },
        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );
    const frameContainer = style(
        "frameContainer",
        {
            flexBasis: calc(`${percent(vars.frameContainer.width)} - ${unit(vars.styleOptions.width)}`),
            height: percent(100),
        },

        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );

    return {
        frame,
        wrapper,
        frameContainer,
        panel,
        shadowTop,
        shadowRight,
    };
});
