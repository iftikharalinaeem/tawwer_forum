/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import { cssRule, media } from "typestyle";
import { calc, important, percent, px, viewHeight } from "csx";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { margins, paddings, sticky, unit } from "@library/styles/styleHelpers";
import { IPanelLayoutClasses, layoutVariables } from "../panelLayoutStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { panelAreaClasses } from "@library/layout/panelAreaStyles";
import { panelListClasses } from "@library/layout/panelListStyles";
import { panelBackgroundVariables } from "@library/layout/panelBackgroundStyles";
import { LayoutTypes } from "@library/layout/types/layouts";

export enum threeColumnLayoutDevices {
    XS = "xs",
    MOBILE = "mobile",
    TABLET = "tablet",
    DESKTOP = "desktop",
    NO_BLEED = "no_bleed", // Not enough space for back link which goes outside the margin.
}

export interface IThreeColumnLayoutMediaQueryStyles {
    noBleed?: NestedCSSProperties;
    oneColumn?: NestedCSSProperties;
    oneColumnDown?: NestedCSSProperties;
    aboveOneColumn?: NestedCSSProperties;
    twoColumns?: NestedCSSProperties;
    twoColumnsDown?: NestedCSSProperties;
    noBleedDown?: NestedCSSProperties;
    xs?: NestedCSSProperties;
}

export interface IThreeColumnLayoutMediaQueries {
    noBleed: (styles: NestedCSSProperties) => NestedCSSProperties;
    oneColumn: (styles: NestedCSSProperties) => NestedCSSProperties;
    oneColumnDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    aboveOneColumn: (styles: NestedCSSProperties) => NestedCSSProperties;
    twoColumns: (styles: NestedCSSProperties) => NestedCSSProperties;
    twoColumnsDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    noBleedDown: (styles: NestedCSSProperties) => NestedCSSProperties;
    xs: (styles: NestedCSSProperties) => NestedCSSProperties;
}

export const threeColumnLayoutVariables = useThemeCache(
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
        const Devices = threeColumnLayoutDevices;

        // Important variables that will be used to calculate other variables
        const makeThemeVars = variableFactory("threeColumnLayout");

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
                currentDevice === threeColumnLayoutDevices.DESKTOP ||
                currentDevice === threeColumnLayoutDevices.NO_BLEED
            );
        };

        const isCompact = currentDevice => {
            return currentDevice === threeColumnLayoutDevices.XS || currentDevice === threeColumnLayoutDevices.MOBILE;
        };

        const layoutSpecificStyles = style => {
            const myMediaQueries = mediaQueries();
            const middleColumnMaxWidth = style("middleColumnMaxWidth", {
                $nest: {
                    "&.hasAdjacentPanel": {
                        flexBasis: calc(`100% - ${unit(panel.paddedWidth)}`),
                        maxWidth: calc(`100% - ${unit(panel.paddedWidth)}`),
                        ...myMediaQueries.oneColumnDown({
                            flexBasis: percent(100),
                            maxWidth: percent(100),
                        }),
                    },
                    "&.hasTwoAdjacentPanels": {
                        flexBasis: calc(`100% - ${unit(panel.paddedWidth * 2)}`),
                        maxWidth: calc(`100% - ${unit(panel.paddedWidth * 2)}`),
                        ...myMediaQueries.oneColumnDown({
                            flexBasis: percent(100),
                            maxWidth: percent(100),
                        }),
                    },
                },
            });

            const leftColumn = style("leftColumn", {
                position: "relative",
                width: unit(panel.paddedWidth),
                flexBasis: unit(panel.paddedWidth),
                minWidth: unit(panel.paddedWidth),
                paddingRight: offset ? unit(offset) : undefined,
            });

            const rightColumn = style("rightColumn", {
                position: "relative",
                width: unit(panel.paddedWidth),
                flexBasis: unit(panel.paddedWidth),
                minWidth: unit(panel.paddedWidth),
                overflow: "initial",
                paddingRight: offset ? unit(offset) : undefined,
            });

            return {
                leftColumn,
                rightColumn,
                middleColumnMaxWidth,
            };
        };

        const rightPanelCondition = (currentDevice, shouldRenderLeftPanel: boolean) => {
            return currentDevice === Devices.TABLET && !shouldRenderLeftPanel;
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
            layoutSpecificStyles,
            rightPanelCondition,
        };
    },
);

export const threeColumnLayoutClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = layoutVariables();
    const mediaQueries = vars.mediaQueries();
    const style = styleFactory("threeColumnLayout");
    const classesPanelArea = panelAreaClasses();
    const classesPanelList = panelListClasses();

    const main = style("main", {
        minHeight: viewHeight(20),
        width: percent(100),
    });

    const root = style(
        {
            ...margins(vars.panelLayoutSpacing.margin),
            width: percent(100),
            $nest: {
                [`&.noBreadcrumbs > .${main}`]: {
                    paddingTop: unit(globalVars.gutter.size),
                    ...mediaQueries.oneColumnDown({
                        paddingTop: 0,
                    }),
                },
                "&.hasTopPadding": {
                    paddingTop: unit(vars.panelLayoutSpacing.extraPadding.top),
                },
                "&.hasTopPadding.noBreadcrumbs": {
                    paddingTop: unit(vars.panelLayoutSpacing.extraPadding.mobile.noBreadcrumbs.top),
                },
                "&.hasLargePadding": {
                    ...paddings(vars.panelLayoutSpacing.largePadding),
                },
            },
        },
        mediaQueries.oneColumnDown({
            $nest: {
                "&.hasTopPadding.noBreadcrumbs": {
                    paddingTop: unit(vars.panelLayoutSpacing.extraPadding.mobile.noBreadcrumbs.top),
                },
            },
        }),
    );

    const content = style("content", {
        display: "flex",
        flexGrow: 1,
        width: percent(100),
        justifyContent: "space-between",
    });

    const panel = style("panel", {
        width: percent(100),
        $nest: {
            [`& > .${classesPanelArea.root}:first-child .${classesPanelList.root}`]: {
                marginTop: unit(
                    (globalVars.fonts.size.title * globalVars.lineHeights.condensed) / 2 -
                        globalVariables().fonts.size.medium / 2,
                ),
            },
        },
    });

    const top = style("top", {
        width: percent(100),
        marginBottom: unit(globalVars.gutter.half),
    });

    const container = style("container", {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "flex-start",
        justifyContent: "space-between",
    });

    const fullWidth = style("fullWidth", {
        position: "relative",
        padding: 0,
    });

    const offset = panelBackgroundVariables().config.render
        ? layoutVariables().panelLayoutSpacing.withPanelBackground.gutter - globalVars.widget.padding * 2
        : 0;

    const leftColumn = style("leftColumn", {
        position: "relative",
        width: unit(vars.panel.paddedWidth),
        flexBasis: unit(vars.panel.paddedWidth),
        minWidth: unit(vars.panel.paddedWidth),
        paddingRight: unit(offset),
    });

    const rightColumn = style("rightColumn", {
        position: "relative",
        width: unit(vars.panel.paddedWidth),
        flexBasis: unit(vars.panel.paddedWidth),
        minWidth: unit(vars.panel.paddedWidth),
        overflow: "initial",
        paddingLeft: unit(offset),
    });

    const middleColumn = style("middleColumn", {
        justifyContent: "space-between",
        flexGrow: 1,
        width: percent(100),
        maxWidth: percent(100),
        paddingBottom: unit(vars.panelLayoutSpacing.extraPadding.bottom),
        ...mediaQueries.oneColumnDown(paddings({ left: important(0), right: important(0) })),
    });

    const middleColumnMaxWidth = style("middleColumnMaxWidth", {
        $nest: {
            "&.hasAdjacentPanel": {
                flexBasis: calc(`100% - ${unit(vars.panel.paddedWidth)}`),
                maxWidth: calc(`100% - ${unit(vars.panel.paddedWidth)}`),
                ...mediaQueries.oneColumnDown({
                    flexBasis: percent(100),
                    maxWidth: percent(100),
                }),
            },
            "&.hasTwoAdjacentPanels": {
                flexBasis: calc(`100% - ${unit(vars.panel.paddedWidth * 2)}`),
                maxWidth: calc(`100% - ${unit(vars.panel.paddedWidth * 2)}`),
                ...mediaQueries.oneColumnDown({
                    flexBasis: percent(100),
                    maxWidth: percent(100),
                }),
            },
        },
    });

    const breadcrumbs = style("breadcrumbs", {});

    const isSticky = style(
        "isSticky",
        {
            ...sticky(),
            height: percent(100),
            $unique: true,
        },
        mediaQueries.oneColumnDown({
            position: "relative",
            top: "auto",
            left: "auto",
            bottom: "auto",
        }),
    );

    // To remove when we have overlay styles converted
    cssRule(`.overlay .${root}.noBreadcrumbs .${main}`, {
        paddingTop: 0,
    });

    const breadcrumbsContainer = style("breadcrumbs", {
        paddingBottom: unit(10),
    });

    const classes: IPanelLayoutClasses = {
        root,
        content,
        top,
        main,
        container,
        fullWidth,
        leftColumn,
        rightColumn,
        middleColumn,
        middleColumnMaxWidth,
        panel,
        isSticky,
        breadcrumbs,
        breadcrumbsContainer,
    };
    return classes;
});
