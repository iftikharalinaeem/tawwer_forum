/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import { media } from "typestyle";
import { px } from "csx";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { generatePanelLayoutClasses, layoutVariables } from "../panelLayoutStyles";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import { ITwoColumnLayoutMediaQueries, twoColumnLayoutDevices } from "@library/layout/types/interface.twoColumns";
import { IPanelLayoutVariables } from "@library/layout/types/interface.panelLayout";

export const twoColumnLayoutVariables = useThemeCache(
    (): IPanelLayoutVariables => {
        const layoutVars = layoutVariables();
        const Devices = twoColumnLayoutDevices;
        const { contentWidth } = layoutVars;
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

        const gutter = makeThemeVars("gutter", {
            full: foundationalWidths.fullGutter, // 48
            size: foundationalWidths.fullGutter / 2, // 24
            halfSize: foundationalWidths.fullGutter / 4, // 12
            quarterSize: foundationalWidths.fullGutter / 8, // 6
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

        const calculateDevice = (): string => {
            const width = document.body.clientWidth;
            if (width <= breakPoints.xs) {
                return Devices.XS.toString();
            } else if (width <= breakPoints.oneColumn) {
                return Devices.MOBILE.toString();
            } else if (width <= breakPoints.noBleed) {
                return Devices.NO_BLEED.toString();
            } else {
                return Devices.DESKTOP.toString();
            }
        };

        const isFullWidth = currentDevice => {
            return currentDevice === Devices.DESKTOP || currentDevice === Devices.NO_BLEED;
        };

        const isCompact = currentDevice => {
            return currentDevice === Devices.XS || currentDevice === Devices.MOBILE;
        };

        const panelLayoutSpacing = makeThemeVars("panelLayoutSpacing", layoutVars.panelLayoutSpacing);

        return {
            type: LayoutTypes.TWO_COLUMNS.toString(),
            Devices,
            foundationalWidths,
            panel,
            middleColumn,
            gutter,
            panelLayoutSpacing,
            contentWidth,
            breakPoints,
            mediaQueries,
            calculateDevice,
            isFullWidth,
            isCompact,
        };
    },
);

export const twoColumnLayoutClasses = () => {
    return generatePanelLayoutClasses({
        vars: twoColumnLayoutVariables(),
        name: "twoColumnLayout",
        type: LayoutTypes.TWO_COLUMNS,
    });
};
