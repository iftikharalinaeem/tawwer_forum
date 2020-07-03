/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import throttle from "lodash/throttle";
import React, { useContext, useEffect, useState } from "react";
import { fallbackLayoutVariables, IPanelLayoutClasses } from "@library/layout/panelLayoutStyles";
import {
    ITwoColumnLayoutMediaQueries,
    ITwoColumnLayoutMediaQueryStyles,
    twoColumnLayoutClasses,
    twoColumnLayoutDevices,
    twoColumnLayoutVariables,
} from "@library/layout/types/layout.twoColumns";
import {
    IThreeColumnLayoutMediaQueries,
    IThreeColumnLayoutMediaQueryStyles,
    threeColumnLayoutVariables,
} from "@library/layout/types/layout.threeColumns";
import { NestedCSSProperties } from "typestyle/lib/types";

export enum LayoutTypes {
    THREE_COLUMNS = "three columns", // Dynamic layout with up to 3 columns that adjusts to its contents. This is the default for KB
    TWO_COLUMNS = "two column", // Single column, but full width of page
}

export interface IAllLayoutMediaQueries {
    [LayoutTypes.TWO_COLUMNS]?: ITwoColumnLayoutMediaQueryStyles;
    [LayoutTypes.THREE_COLUMNS]?: IThreeColumnLayoutMediaQueryStyles;
}

export type ILayoutMediaQueryFunction = (styles: IAllLayoutMediaQueries) => NestedCSSProperties;

export type IAllLayoutDevices = twoColumnLayoutDevices | fallbackLayoutVariables;

export type IAllMediaQueriesForLayouts = ITwoColumnLayoutMediaQueries | IThreeColumnLayoutMediaQueries;

/* Allows to declare styles for any layout without causing errors
Declare media query styles like this:

    mediaQueries({
        [LayoutTypes.TWO_COLUMNS]: {
            oneColumnDown: {
                ...srOnly(),
            },
        },
        [LayoutTypes.THREE_COLUMNS]: {
            twoColumns: {
                // Styles go here
            }
        }
    }),
    Note that "twoColumns" does not exist in two column layout media queries, but it does not crash!
*/

export const filterQueriesByType = (mediaQueriesByType, type) => {
    return (mediaQueriesForAllLayouts: IAllLayoutMediaQueries) => {
        // console.log("");
        // console.log("mediaQueriesForAllLayouts: ", mediaQueriesForAllLayouts);
        let output = {};
        Object.keys(mediaQueriesForAllLayouts).forEach(layoutName => {
            // Check if we're in the correct layout before applying
            if (layoutName === type) {
                // Fetch the available styles and the media queries for the current layout
                const stylesByMediaQuery = mediaQueriesForAllLayouts[layoutName];
                const mediaQueries = allLayouts().mediaQueriesByType[type];
                // console.log("stylesByMediaQuery: ", stylesByMediaQuery);
                // console.log("mediaQueries: ", mediaQueries);

                // Match the two together
                if (stylesByMediaQuery) {
                    Object.keys(stylesByMediaQuery).forEach(queryName => {
                        const query: ILayoutMediaQueryFunction = mediaQueries[queryName];
                        const styles: NestedCSSProperties = stylesByMediaQuery[queryName];
                        // console.log("query(styles as any): ", query(styles as any));
                        output = {
                            $nest: {
                                ...query(styles as any).$nest,
                            },
                        };
                    });
                }
            }
        });
        return output;
    };
};

export const allLayouts = () => {
    const mediaQueriesByType = {};

    const variablesByType = {
        [LayoutTypes.THREE_COLUMNS]: threeColumnLayoutVariables(),
        [LayoutTypes.TWO_COLUMNS]: twoColumnLayoutVariables(),
    };

    const classesByType = {
        [LayoutTypes.THREE_COLUMNS]: threeColumnLayoutVariables(),
        [LayoutTypes.TWO_COLUMNS]: twoColumnLayoutClasses(),
    };

    Object.keys(LayoutTypes).forEach(layoutName => {
        const enumKey = LayoutTypes[layoutName];
        const layoutData = variablesByType[enumKey];
        mediaQueriesByType[enumKey] = layoutData.mediaQueries();
    });

    return {
        mediaQueriesByType,
        classesByType,
        variablesByType,
    };
};

export const layoutData = (type: LayoutTypes = LayoutTypes.THREE_COLUMNS) => {
    const layouts = allLayouts();
    return {
        mediaQueries: layouts.mediaQueriesByType[type] as IAllMediaQueriesForLayouts,
        classes: layouts.classesByType[type] as IPanelLayoutClasses,
        variables: layouts.variablesByType[type],
    };
};

export interface ILayoutProps {
    type: LayoutTypes;
    currentDevice: string;
    Devices: IAllLayoutDevices;
    isCompact: boolean; // Usually mobile and/or xs, but named this way to be more generic and not be confused with the actual mobile media query
    isFullWidth: boolean; // Usually desktop and no bleed, but named this way to be more generic and just to mean it's the full size
    classes: IPanelLayoutClasses;
    currentLayoutVariables: any;
    mediaQueries: ILayoutMediaQueryFunction;
    contentWidth: number;
    calculateDevice: () => IAllLayoutDevices;
    layoutSpecificStyles: (style) => any | undefined;
    rightPanelCondition: (currentDevice: string, shouldRenderRightPanel: boolean) => boolean;
}

const defaultRenderRightPanel = (currentDevice, shouldRenderRightPanel) => {
    return false;
};

const layoutDataByType = (type: LayoutTypes) => {
    const layout = layoutData(type);
    const currentDevice = layout.variables.calculateDevice();

    const mediaQueries = filterQueriesByType(layout.variables.mediaQueries, type);

    return {
        type,
        currentDevice,
        Devices: layout.variables.Devices as any,
        isCompact: layout.variables.isCompact(currentDevice),
        isFullWidth: layout.variables.isFullWidth(currentDevice),
        classes: layout.classes,
        currentLayoutVariables: layout.variables,
        mediaQueries,
        contentWidth: layout.variables.contentWidth,
        calculateDevice: layout.variables.calculateDevice,
        layoutSpecificStyles: layout.variables["layoutSpecificStyles"] ?? undefined,
        rightPanelCondition:
            layout.variables["rightPanelCondition"] !== undefined
                ? layout.variables["rightPanelCondition"]
                : defaultRenderRightPanel,
    };
};

const LayoutContext = React.createContext<ILayoutProps>({
    mediaQueries: fallbackMediaQueries,
} as any);

export default LayoutContext;

export function useLayout() {
    return useContext(LayoutContext);
}

export function LayoutProvider(props: { type?: LayoutTypes; children: React.ReactNode }) {
    const { type = LayoutTypes.THREE_COLUMNS, children } = props;

    const [deviceInfo, setDeviceInfo] = useState<ILayoutProps>(layoutDataByType(type));

    useEffect(() => {
        const throttledUpdate = throttle(() => {
            setDeviceInfo(layoutDataByType(type));
        }, 100);
        window.addEventListener("resize", throttledUpdate);
        return () => {
            window.removeEventListener("resize", throttledUpdate);
        };
    }, [type, setDeviceInfo]);

    return <LayoutContext.Provider value={deviceInfo}>{children}</LayoutContext.Provider>;
}

/**
 * HOC to inject DeviceContext as props.
 *
 * @param WrappedComponent - The component to wrap
 */
export function withLayout<T extends ILayoutProps = ILayoutProps>(WrappedComponent: React.ComponentType<T>) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    const ComponentWithDevice = (props: Optionalize<T, ILayoutProps>) => {
        return (
            <LayoutContext.Consumer>
                {context => {
                    // https://github.com/Microsoft/TypeScript/issues/28938
                    return <WrappedComponent device={context} {...(props as T)} />;
                }}
            </LayoutContext.Consumer>
        );
    };
    ComponentWithDevice.displayName = `withLayout(${displayName})`;
    return ComponentWithDevice;
}

/**
 * Allows newer media query declaration while falling back to default (no context)
 */
export function fallbackMediaQueries() {
    return filterQueriesByType(threeColumnLayoutVariables().mediaQueries, LayoutTypes.THREE_COLUMNS);
}
