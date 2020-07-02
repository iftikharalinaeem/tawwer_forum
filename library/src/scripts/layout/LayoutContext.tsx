/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import throttle from "lodash/throttle";
import React, { useContext, useEffect, useState } from "react";
import { fallbackLayoutVariables, IPanelLayoutClasses, layoutVariables } from "@library/layout/panelLayoutStyles";
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
import { panelListClasses } from "@library/layout/panelListStyles";

export enum LayoutTypes {
    THREE_COLUMNS = "three columns", // Dynamic layout with up to 3 columns that adjusts to its contents. This is the default for KB
    TWO_COLUMNS = "two column", // Single column, but full width of page
    // ONE_COLUMN = "one column", // Single column, but full width of page
    // NARROW = "one column narrow", // Single column, but narrower than default
    // LEGACY = "legacy", // Legacy layout used on the Forum pages. The media queries are also used for older components. Newer ones should use the context
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


Note that "twoColumns" does not exist in the two column layout media queries, but it does not crash!
*/

export const filterQueriesByType = (mediaQueriesByType, type) => {
    return (mediaQueriesByLayout: IAllLayoutMediaQueries) => {
        Object.keys(mediaQueriesByLayout).forEach(layoutName => {
            if (layoutName === type) {
                // Check if we're in the correct layout before applying
                const mediaQueriesForLayout = mediaQueriesByLayout[layoutName];
                const stylesForLayout = mediaQueriesByLayout[layoutName];
                if (mediaQueriesForLayout) {
                    Object.keys(mediaQueriesForLayout).forEach(queryName => {
                        mediaQueriesForLayout[queryName] = stylesForLayout;
                        const result = mediaQueriesForLayout[queryName];
                        return result;
                    });
                }
            }
        });
        return {};
    };
};

export const allLayouts = (props: { offset?: number } = {}) => {
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

const LayoutContext = React.createContext<ILayoutProps>({
    type: LayoutTypes.THREE_COLUMNS,
    currentDevice: fallbackLayoutVariables.DESKTOP,
    Devices: fallbackLayoutVariables as any,
    // isCompact: defaultLayoutVars.isCompact(threeColumnLayoutDevices.DESKTOP),
    // isFullWidth: defaultLayoutVars.isFullWidth(threeColumnLayoutDevices.DESKTOP),
    // classes: threeColumnLayoutClasses(),
    // currentLayoutVariables: defaultLayoutVars,
    mediaQueries: layoutVariables().mediaQueries,
    // contentWidth: defaultLayoutVars.contentWidth,
    // calculateDevice: defaultLayoutVars.calculateDevice,
    // layoutSpecificStyles: defaultLayoutVars["layoutSpecificStyles"] ?? undefined,
    // rightPanelCondition: defaultLayoutVars.rightPanelCondition,
} as any);

export default LayoutContext;

export function useLayout() {
    return useContext(LayoutContext);
}

const defaultRenderRightPanel = (currentDevice, shouldRenderRightPanel) => {
    return false;
};

export function LayoutProvider(props: { type?: LayoutTypes; children: React.ReactNode }) {
    const { type = LayoutTypes.THREE_COLUMNS, children } = props;
    const layout = layoutData(type);
    const currentDevice = layout.variables.calculateDevice();

    console.log("trigger: ", type);

    const [deviceInfo, setDeviceInfo] = useState<ILayoutProps>({
        type,
        currentDevice,
        Devices: layout.variables.Devices as any,
        isCompact: layout.variables.isCompact(currentDevice),
        isFullWidth: layout.variables.isFullWidth(currentDevice),
        classes: layout.classes,
        currentLayoutVariables: layout.variables,
        mediaQueries: filterQueriesByType(layout.variables.mediaQueries, type),
        contentWidth: layout.variables.contentWidth,
        calculateDevice: layout.variables.calculateDevice,
        layoutSpecificStyles: layout.variables["layoutSpecificStyles"] ?? undefined,
        rightPanelCondition: layout.variables["rightPanelCondition"] ?? defaultRenderRightPanel,
    });

    useEffect(() => {
        const throttledUpdate = throttle(() => {
            const currentDevice = layout.variables.calculateDevice();
            setDeviceInfo({
                type,
                currentDevice,
                Devices: layout.variables.Devices as any,
                isCompact: layout.variables.isCompact(currentDevice),
                isFullWidth: layout.variables.isFullWidth(currentDevice),
                classes: layout.classes,
                currentLayoutVariables: layout.variables,
                mediaQueries: filterQueriesByType(layout.variables.mediaQueries, type),
                contentWidth: layout.variables.contentWidth,
                calculateDevice: layout.variables.calculateDevice,
                layoutSpecificStyles: layout.variables["layoutSpecificStyles"] ?? undefined,
                rightPanelCondition: layout.variables["rightPanelCondition"] ?? defaultRenderRightPanel,
            });
        }, 100);
        window.addEventListener("resize", throttledUpdate);
        return () => {
            window.removeEventListener("resize", throttledUpdate);
        };
    }, [layout.variables, setDeviceInfo]);

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
