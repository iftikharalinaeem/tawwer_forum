/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import {
    fallbackLayoutVariables,
    generatePanelLayoutClasses,
    IPanelLayoutMediaQueries,
    IPanelLayoutMediaQueryStyles,
    layoutVariables,
} from "../panelLayoutStyles";
import { LayoutTypes } from "@library/layout/LayoutContext";

export interface IThreeColumnLayoutMediaQueryStyles extends IPanelLayoutMediaQueryStyles {}

export interface IThreeColumnLayoutMediaQueries extends IPanelLayoutMediaQueries {}

export const threeColumnLayoutVariables = useThemeCache(() => {
    const layoutVars = layoutVariables();
    const Devices = fallbackLayoutVariables;

    // Important variables that will be used to calculate other variables
    const makeThemeVars = variableFactory("threeColumnLayout");

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

    const calculateDevice = layoutVars.setCalculateDevice(breakPoints, Devices);

    const isFullWidth = currentDevice => {
        return currentDevice === Devices.DESKTOP || currentDevice === Devices.NO_BLEED;
    };

    const isCompact = currentDevice => {
        return currentDevice === Devices.XS || currentDevice === Devices.MOBILE;
    };

    return {
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
});

export const threeColumnLayoutClasses = () => {
    return generatePanelLayoutClasses(threeColumnLayoutVariables(), "threeColumnLayout");
};
