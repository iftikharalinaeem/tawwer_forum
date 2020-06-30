/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import { media } from "typestyle";
import { calc, percent, px } from "csx";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { unit } from "@library/styles/styleHelpers";
import { layoutVariables } from "../panelLayoutStyles";
import {
    ThreeColumnLayoutDevices,
    IThreeColumnLayoutMediaQueries,
} from "@library/layout/types/interface.layoutThreeColumn";
import { LayoutTypes } from "@library/layout/types/interface.layout";

export const threeColumnLayout = useThemeCache(
    (
        props: {
            vars?: {
                offset?: number;
            };
        } = {},
    ) => {
        const { vars = { offset: undefined } } = props;
        const { offset } = vars;
        const layoutVars = layoutVariables();
        const Devices = ThreeColumnLayoutDevices;

        // Important variables that will be used to calculate other variables
        const makeThemeVars = variableFactory("threeColumnLayout");

        const foundationalWidths = makeThemeVars("foundationalWidths", {
            ...layoutVars.foundationalWidths,
        });

        const panel = makeThemeVars("panel", {
            width: foundationalWidths.panelWidth,
        });

        const panelPaddedWidth = () => {
            return panel.width + layoutVars.gutter.full;
        };

        const middleColumn = makeThemeVars("middleColumn", {
            width: layoutVars.middleColumn.width,
        });

        const middleColumnPaddedWidth = () => {
            return middleColumn.width + layoutVars.gutter.full;
        };

        const contentWidth = () => {
            return layoutVars.contentWidth();
        };

        const breakPoints = makeThemeVars("breakPoints", {
            noBleed: contentWidth(),
            twoColumns: foundationalWidths.breakPoints.twoColumns,
            oneColumn: foundationalWidths.minimalMiddleColumnWidth + panelPaddedWidth(),
            xs: foundationalWidths.breakPoints.xs,
        });

        const mediaQueries = (): IThreeColumnLayoutMediaQueries => {
            const noBleed = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
                return media(
                    {
                        maxWidth: px(breakPoints.noBleed),
                        minWidth: useMinWidth ? px(breakPoints.twoColumns + 1) : undefined,
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

            const twoColumnsDown = (styles: NestedCSSProperties) => {
                return media(
                    {
                        maxWidth: px(breakPoints.twoColumns),
                    },
                    styles,
                );
            };

            const twoColumns = (styles: NestedCSSProperties, useMinWidth: boolean = true) => {
                return media(
                    {
                        maxWidth: px(breakPoints.twoColumns),
                        minWidth: useMinWidth ? px(breakPoints.oneColumn + 1) : undefined,
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
                twoColumnsDown,
                twoColumns,
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
            } else if (width <= breakPoints.twoColumns) {
                return Devices.TABLET;
            } else if (width <= breakPoints.noBleed) {
                return Devices.NO_BLEED;
            } else {
                return Devices.DESKTOP;
            }
        };

        const isFullWidth = currentDevice => {
            return (
                currentDevice === ThreeColumnLayoutDevices.DESKTOP ||
                currentDevice === ThreeColumnLayoutDevices.NO_BLEED
            );
        };

        const isCompact = currentDevice => {
            return currentDevice === ThreeColumnLayoutDevices.XS || currentDevice === ThreeColumnLayoutDevices.MOBILE;
        };

        const layoutSpecificStyles = style => {
            const myMediaQueries = mediaQueries();
            const middleColumnMaxWidth = style("middleColumnMaxWidth", {
                $nest: {
                    "&.hasAdjacentPanel": {
                        flexBasis: calc(`100% - ${unit(panelPaddedWidth())}`),
                        maxWidth: calc(`100% - ${unit(panelPaddedWidth())}`),
                        ...myMediaQueries.oneColumnDown({
                            flexBasis: percent(100),
                            maxWidth: percent(100),
                        }),
                    },
                    "&.hasTwoAdjacentPanels": {
                        flexBasis: calc(`100% - ${unit(panelPaddedWidth() * 2)}`),
                        maxWidth: calc(`100% - ${unit(panelPaddedWidth() * 2)}`),
                        ...myMediaQueries.oneColumnDown({
                            flexBasis: percent(100),
                            maxWidth: percent(100),
                        }),
                    },
                },
            });

            const leftColumn = style("leftColumn", {
                position: "relative",
                width: unit(panelPaddedWidth()),
                flexBasis: unit(panelPaddedWidth()),
                minWidth: unit(panelPaddedWidth()),
                paddingRight: offset ? unit(offset) : undefined,
            });

            const rightColumn = style("rightColumn", {
                position: "relative",
                width: unit(panelPaddedWidth()),
                flexBasis: unit(panelPaddedWidth()),
                minWidth: unit(panelPaddedWidth()),
                overflow: "initial",
                paddingRight: offset ? unit(offset) : undefined,
            });

            return {
                leftColumn,
                rightColumn,
                middleColumnMaxWidth,
            };
        };

        return {
            type: LayoutTypes.THREE_COLUMNS,
            Devices,
            foundationalWidths,
            panel,
            panelPaddedWidth,
            middleColumn,
            middleColumnPaddedWidth,
            contentWidth,
            breakPoints,
            mediaQueries,
            calculateDevice,
            isFullWidth,
            isCompact,
            layoutSpecificStyles,
        };
    },
);
