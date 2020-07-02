/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import { media } from "typestyle";
import { calc, percent, px } from "csx";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { sticky, unit } from "@library/styles/styleHelpers";
import { IPanelLayoutClasses, layoutVariables } from "../panelLayoutStyles";
import { threeColumnLayoutClasses } from "@library/layout/types/layout.threeColumns";
import { LayoutTypes } from "@library/layout/types/layouts";

export enum twoColumnLayoutDevices {
    XS = "xs",
    MOBILE = "mobile",
    DESKTOP = "desktop",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
}

export interface ITwoColumnLayoutMediaQueryStyles {
    noBleed?: NestedCSSProperties;
    noBleedDown?: NestedCSSProperties;
    oneColumn?: NestedCSSProperties;
    oneColumnDown?: NestedCSSProperties;
    aboveOneColumn?: NestedCSSProperties;
    xs?: NestedCSSProperties;
}

export interface ITwoColumnLayoutMediaQueries {
    noBleed: (styles: NestedCSSProperties) => NestedCSSProperties;
    oneColumn: (styles: NestedCSSProperties) => NestedCSSProperties;
    oneColumnDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    aboveOneColumn: (styles: NestedCSSProperties) => NestedCSSProperties;
    noBleedDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    xs: (styles: NestedCSSProperties) => NestedCSSProperties;
}

export const twoColumnLayoutVariables = useThemeCache((props = {}) => {
    const layoutVars = layoutVariables();
    const Devices = twoColumnLayoutDevices;
    const { gutter, contentWidth } = layoutVars;
    const { fullGutter } = layoutVars.foundationalWidths;

    const makeThemeVars = variableFactory("twoColumnLayout");

    // Important variables that will be used to calculate other variables
    const foundationalWidths = makeThemeVars("foundationalWidths", {
        fullGutter,
        minimalMiddleColumnWidth: 600,
        panelWidth: 343,
        breakPoints: {
            xs: layoutVars.foundationalWidths.breakPoints.xs,
        }, // Other break point are calculated
    });

    const panelInit = makeThemeVars("panel", {
        width: foundationalWidths.panelWidth,
    });

    const panel = makeThemeVars("panel", {
        ...panelInit,
        paddedWidth: panelInit.width + layoutVars.gutter.full,
    });

    const middleColumnInit = makeThemeVars("middleColumn", {
        width: contentWidth - panel.paddedWidth - layoutVars.gutter.full,
    });

    const middleColumn = makeThemeVars("middleColumn", {
        ...middleColumnInit,
        paddedWidth: middleColumnInit.width + layoutVars.gutter.full,
    });

    const breakPoints = makeThemeVars("breakPoints", {
        noBleed: contentWidth,
        oneColumn: foundationalWidths.minimalMiddleColumnWidth + panel.paddedWidth,
        xs: foundationalWidths.breakPoints.xs,
    });

    const mediaQueries = (): ITwoColumnLayoutMediaQueries => {
        const noBleed = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: px(breakPoints.noBleed),
                    minWidth: useMinWidth ? px(breakPoints.oneColumn + 1) : undefined,
                },
                styles,
            );
        };

        const noBleedDown = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(breakPoints.noBleed),
                },
                styles,
            );
        };

        const oneColumn = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
            return media(
                {
                    maxWidth: px(breakPoints.oneColumn),
                    minWidth: useMinWidth ? px(breakPoints.xs + 1) : undefined,
                },
                styles,
            );
        };

        const oneColumnDown = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(breakPoints.oneColumn),
                },
                styles,
            );
        };

        const aboveOneColumn = (styles: NestedCSSProperties) => {
            return media(
                {
                    minWidth: px(breakPoints.oneColumn + 1),
                },
                styles,
            );
        };

        const xs = (styles: NestedCSSProperties) => {
            return media(
                {
                    maxWidth: px(breakPoints.xs),
                },
                styles,
            );
        };

        return {
            noBleed,
            noBleedDown,
            oneColumn,
            oneColumnDown,
            aboveOneColumn,
            xs,
        };
    };

    const calculateDevice = () => {
        const width = document.body.clientWidth;
        if (width <= breakPoints.xs) {
            return Devices.XS;
        } else if (width <= breakPoints.oneColumn) {
            return Devices.MOBILE;
        } else if (width <= breakPoints.noBleed) {
            return Devices.NO_BLEED;
        } else {
            return Devices.DESKTOP;
        }
    };

    const isFullWidth = currentDevice => {
        return currentDevice === Devices.DESKTOP || currentDevice === Devices.NO_BLEED;
    };

    const isCompact = currentDevice => {
        return currentDevice === Devices.XS || currentDevice === Devices.MOBILE;
    };

    return {
        type: LayoutTypes.TWO_COLUMNS,
        Devices,
        foundationalWidths,
        panel,
        middleColumn,
        contentWidth,
        breakPoints,
        mediaQueries,
        calculateDevice,
        isFullWidth,
        isCompact,
    };
});

export const twoColumnLayoutClasses = useThemeCache(() => {
    const vars = twoColumnLayoutVariables();
    const style = styleFactory("twoColumnLayout");
    const mediaQueries = vars.mediaQueries();

    const rightColumn = style("rightColumn", {
        position: "relative",
        width: unit(vars.panel.paddedWidth),
        flexBasis: unit(vars.panel.paddedWidth),
        minWidth: unit(vars.panel.paddedWidth),
        overflow: "initial",
    });

    const mainColumnMaxWidth = style("middleColumnMaxWidth", {
        $nest: {
            "&.hasAdjacentPanel": {
                flexBasis: calc(`100% - ${unit(vars.panel.paddedWidth)}`),
                maxWidth: calc(`100% - ${unit(vars.panel.paddedWidth)}`),
                ...mediaQueries.oneColumnDown({
                    flexBasis: percent(100),
                    maxWidth: percent(100),
                }),
            },
        },
    });

    const isSticky = style("isSticky", {
        ...sticky(),
        height: percent(100),
        $unique: true,
        ...mediaQueries.oneColumnDown({
            position: "relative",
            top: "auto",
            left: "auto",
            bottom: "auto",
        }),
    });

    const inheritedClasses = threeColumnLayoutClasses();
    const classes: IPanelLayoutClasses = {
        root: inheritedClasses.root,
        content: inheritedClasses.content,
        top: inheritedClasses.top,
        main: inheritedClasses.main,
        container: inheritedClasses.container,
        fullWidth: inheritedClasses.fullWidth,
        rightColumn,
        mainColumn: inheritedClasses.mainColumn,
        isSticky,
        mainColumnMaxWidth,
        panel: inheritedClasses.panel,
        breadcrumbs: inheritedClasses.breadcrumbs,
        breadcrumbsContainer: inheritedClasses.breadcrumbsContainer,
    };

    return classes;
});
