/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import throttle from "lodash/throttle";
import React, { useContext, useEffect, useState } from "react";
import { IPanelLayoutClasses } from "@library/layout/panelLayoutStyles";
import { threeColumnLayoutVariables } from "./types/layout.threeColumns";
import { twoColumnLayoutVariables, twoColumnLayoutClasses } from "./types/layout.twoColumns";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import {
    filterQueriesByType,
    IAllMediaQueriesForLayouts,
    IAllLayoutDevices,
    ILayoutMediaQueryFunction,
} from "@library/layout/mediaQueriesForAllLayouts";

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
    calculateDevice: () => any;
    layoutSpecificStyles: (style) => any | undefined;
    rightPanelCondition: (currentDevice: string, shouldRenderRightPanel: boolean) => boolean;
}

const layoutDataByType = (type: LayoutTypes): ILayoutProps => {
    const layout = layoutData(type);
    const currentDevice = layout.variables.calculateDevice().toString();

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
                : (currentDevice, shouldRenderRightPanel) => {
                      return false;
                  },
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
