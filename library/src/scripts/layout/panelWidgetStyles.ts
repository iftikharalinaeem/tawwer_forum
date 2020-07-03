/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent } from "csx";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { paddings } from "@library/styles/styleHelpers";
import { LayoutTypes, useLayout } from "@library/layout/LayoutContext";

// @Deprecated - Use globalVariables().widget.padding directly
export const panelWidgetVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("panelWidget");

    const spacing = makeThemeVars("spacing", {
        padding: globalVariables().widget.padding,
    });

    return { spacing };
});

export const panelWidgetClasses = useThemeCache(mediaQueries => {
    const globalVars = globalVariables();
    const style = styleFactory("panelWidget");
    const vars = panelWidgetVariables();

    const root = style({
        display: "flex",
        flexDirection: "column",
        position: "relative",
        width: percent(100),
        ...paddings({
            all: globalVars.gutter.half,
        }),
        $nest: {
            "&.hasNoVerticalPadding": {
                ...paddings({ vertical: 0 }),
            },
            "&.hasNoHorizontalPadding": {
                ...paddings({ horizontal: 0 }),
            },
            "&.isSelfPadded": {
                ...paddings({ all: 0 }),
            },
        },
        ...mediaQueries({
            [LayoutTypes.TWO_COLUMNS]: {
                oneColumnDown: {
                    ...paddings({
                        all: vars.spacing.padding,
                    }),
                },
            },
            [LayoutTypes.THREE_COLUMNS]: {
                oneColumnDown: {
                    ...paddings({
                        all: vars.spacing.padding,
                    }),
                },
            },
        }),
    });

    return { root };
});
