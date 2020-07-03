/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import {
    fallbackLayoutVariables,
    generatePanelLayoutClasses,
    IPanelLayoutVariables,
    layoutVariables,
} from "../panelLayoutStyles";
import { LayoutTypes } from "@library/layout/LayoutContext";

export const threeColumnLayoutVariables = useThemeCache(
    (): IPanelLayoutVariables => {
        const layoutVars = layoutVariables();
        const Devices = fallbackLayoutVariables;

        const makeThemeVars = variableFactory("threeColumnLayout");

        const colors = makeThemeVars("colors", {
            ...layoutVars.colors,
        });
        const contentSizes = makeThemeVars("contentSizes", {
            ...layoutVars.contentSizes,
        });
        const gutter = makeThemeVars("gutter", {
            ...layoutVars.gutter,
        });
        const panelLayoutBreakPoints = makeThemeVars("panelLayoutBreakPoints", {
            ...layoutVars.panelLayoutBreakPoints,
        });
        const panelLayoutSpacing = makeThemeVars("panelLayoutSpacing", {
            ...layoutVars.panelLayoutBreakPoints,
        });

        const rightPanelCondition = (currentDevice, shouldRenderLeftPanel: boolean) => {
            return currentDevice === Devices.TABLET && !shouldRenderLeftPanel;
        };

        const foundationalWidths = makeThemeVars("foundationalWidths", {
            ...layoutVars.foundationalWidths,
        });

        const panelInit = makeThemeVars("panel", {
            width: foundationalWidths.panelWidth,
        });

        const panel = makeThemeVars("panel", {
            ...panelInit,
            paddedWidth: panelInit.width + layoutVars.gutter.full,
        });

        const middleColumnInit = makeThemeVars("middleColumn", {
            width: layoutVars.middleColumn.width,
        });

        const middleColumn = makeThemeVars("middleColumn", {
            ...middleColumnInit,
            paddedWidth: middleColumnInit.width + layoutVars.gutter.full,
        });

        const contentWidth = layoutVars.contentWidth;

        const breakPoints = makeThemeVars("breakPoints", {
            noBleed: contentWidth,
            twoColumns: foundationalWidths.breakPoints.twoColumns,
            oneColumn: foundationalWidths.minimalMiddleColumnWidth + panel.paddedWidth,
            xs: foundationalWidths.breakPoints.xs,
        });

        const mediaQueries = layoutVars.mediaQueries;

        const calculateDevice = () => {
            return layoutVars.calculateDeviceFunction(breakPoints, Devices)();
        };

        const isFullWidth = currentDevice => {
            return currentDevice === Devices.DESKTOP || currentDevice === Devices.NO_BLEED;
        };

        const isCompact = currentDevice => {
            return currentDevice === Devices.XS || currentDevice === Devices.MOBILE;
        };

        return {
            colors,
            contentSizes,
            gutter,
            panelLayoutBreakPoints,
            panelLayoutSpacing,
            type: LayoutTypes.THREE_COLUMNS,
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
            rightPanelCondition,
        };
    },
);

export const threeColumnLayoutClasses = () => {
    return generatePanelLayoutClasses({
        vars: threeColumnLayoutVariables(),
        name: "threeColumnLayout",
        type: LayoutTypes.THREE_COLUMNS,
    });
};
