/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { calc, color, percent, px, translateY, viewHeight } from "csx";
import { cssRule, media } from "typestyle";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { margins, paddings, sticky, unit } from "@library/styles/styleHelpers";
import { important } from "csx/lib/strings";
import { panelListClasses } from "@library/layout/panelListStyles";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { panelAreaClasses } from "@library/layout/panelAreaStyles";
import { NestedCSSProperties } from "typestyle/lib/types";
import { panelWidgetVariables } from "@library/layout/panelWidgetStyles";
import { panelBackgroundVariables } from "@library/layout/panelBackgroundStyles";
import { IThemeVariables } from "@library/theming/themeReducer";

export const layoutVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const makeThemeVars = variableFactory("layoutVariables", forcedVars);

    const colors = makeThemeVars("colors", {
        leftColumnBg: globalVars.mainColors.bg,
    });

    // Important variables that will be used to calculate other variables
    const foundationalWidths = makeThemeVars("foundationalWidths", {
        fullGutter: globalVars.constants.fullGutter,
        panelWidth: globalVars.panel.width,
        middleColumnWidth: 700,
        minimalMiddleColumnWidth: 550, // Will break if middle column width is smaller than this value.
        narrowContentWidth: 900, // For home page widgets, narrower than full width
        breakPoints: {
            // Other break points are calculated
            twoColumns: 1200,
            xs: 500,
        },
    });

    const gutter = makeThemeVars("gutter", {
        full: foundationalWidths.fullGutter, // 48
        size: foundationalWidths.fullGutter / 2, // 24
        halfSize: foundationalWidths.fullGutter / 4, // 12
        quarterSize: foundationalWidths.fullGutter / 8, // 6
    });

    const fullPadding = panelWidgetVariables().spacing.padding * 2;

    const panel = makeThemeVars("panel", {
        width: foundationalWidths.panelWidth,
        paddedWidth: foundationalWidths.panelWidth + fullPadding,
    });

    const middleColumn = makeThemeVars("middleColumn", {
        width: foundationalWidths.middleColumnWidth,
        paddedWidth: foundationalWidths.middleColumnWidth + fullPadding,
    });

    // @Deprecated - Use LayoutContext to get variables
    const contentWidth = middleColumn.paddedWidth + panel.paddedWidth * 2 + fullPadding;

    // @Deprecated - Needs to be split into 2 layouts
    const contentSizes = makeThemeVars("content", {
        full: contentWidth,
        narrow:
            foundationalWidths.narrowContentWidth < contentWidth ? foundationalWidths.narrowContentWidth : contentWidth,
    });

    // @Deprecated - Use LayoutContext to get variables
    const panelLayoutBreakPoints = makeThemeVars("panelLayoutBreakPoints", {
        noBleed: contentWidth,
        twoColumn: foundationalWidths.breakPoints.twoColumns,
        oneColumn: foundationalWidths.minimalMiddleColumnWidth + panel.paddedWidth,
        xs: foundationalWidths.breakPoints.xs,
    });

    const panelLayoutSpacing = makeThemeVars("panelLayoutSpacing", {
        margin: {
            top: 0,
            bottom: 0,
        },
        padding: {
            top: gutter.halfSize * 1.5,
        },
        extraPadding: {
            top: 32,
            bottom: 32,
            noBreadcrumbs: {},
            mobile: {
                noBreadcrumbs: {
                    top: 16,
                },
            },
        },
        largePadding: {
            top: 64,
        },
        offset: {
            left: -44,
            right: -36,
        },
        withPanelBackground: {
            gutter: 70,
        },
    });

    // @Deprecated - Use LayoutContext to get media queries of current layout.
    const mediaQueries = () => {
        const noBleed = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.noBleed),
                    minWidth: useMinWidth ? px(panelLayoutBreakPoints.twoColumn + 1) : undefined,
                },
                styles,
            );
        };

        const noBleedDown = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.noBleed),
                },
                styles,
            );
        };

        const twoColumnsDown = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.twoColumn),
                },
                styles,
            );
        };

        const twoColumns = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.twoColumn),
                    minWidth: useMinWidth ? px(panelLayoutBreakPoints.oneColumn + 1) : undefined,
                },
                styles,
            );
        };

        const oneColumn = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.oneColumn),
                    minWidth: useMinWidth ? px(panelLayoutBreakPoints.xs + 1) : undefined,
                },
                styles,
            );
        };

        const oneColumnDown = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.oneColumn),
                },
                styles,
            );
        };

        const aboveOneColumn = (styles: NestedCSSProperties) => {
            return media(
                {
                    minWidth: px(panelLayoutBreakPoints.oneColumn + 1),
                },
                styles,
            );
        };

        const xs = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(panelLayoutBreakPoints.xs),
                },
                styles,
            );
        };

        return {
            noBleed,
            noBleedDown,
            twoColumns,
            twoColumnsDown,
            oneColumn,
            oneColumnDown,
            aboveOneColumn,
            xs,
        };
    };

    return {
        colors,
        foundationalWidths,
        gutter,
        panel,
        middleColumn,
        contentSizes,
        contentWidth,
        mediaQueries,
        panelLayoutSpacing,
        panelLayoutBreakPoints,
    };
});

export interface IPanelLayoutClasses {
    root: string;
    content: string;
    top: string;
    main: string;
    container: string;
    fullWidth: string;
    leftColumn: string;
    rightColumn: string;
    middleColumn: string;
    middleColumnMaxWidth: string;
    panel: string;
    isSticky: string;
    breadcrumbs: string;
    breadcrumbsContainer: string;
}
