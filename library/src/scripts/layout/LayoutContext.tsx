/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import throttle from "lodash/throttle";
import React, { useContext, useEffect, useState } from "react";
import {
    IAllLayoutDevices,
    LayoutTypes,
    layoutData,
    ILayoutMediaQueryFunction,
    filterQueriesByType,
} from "@library/layout/types/layouts";
import {
    threeColumnLayoutClasses,
    threeColumnLayoutDevices,
    threeColumnLayoutVariables,
} from "@library/layout/types/layout.threeColumns";

export interface ILayoutProps {
    type: LayoutTypes;
    currentDevice: string;
    Devices: IAllLayoutDevices;
    isCompact: boolean; // Usually mobile and/or xs, but named this way to be more generic and not be confused with the actual mobile media query
    isFullWidth: boolean; // Usually desktop and no bleed, but named this way to be more generic and just to mean it's the full size
    layoutClasses: any;
    currentLayoutVariables: any;
    mediaQueries: ILayoutMediaQueryFunction;
    contentWidth: number;
    calculateDevice: () => IAllLayoutDevices;
    layoutSpecificStyles: (style) => any | undefined;
    rightPanelCondition: (currentDevice: string, shouldRenderLeftPanel: boolean) => boolean;
}

const defaultLayoutVars = threeColumnLayoutVariables();

const LayoutContext = React.createContext<ILayoutProps>({
    type: LayoutTypes.THREE_COLUMNS,
    currentDevice: threeColumnLayoutDevices.DESKTOP,
    Devices: defaultLayoutVars.Devices as any,
    isCompact: defaultLayoutVars.isCompact(threeColumnLayoutDevices.DESKTOP),
    isFullWidth: defaultLayoutVars.isFullWidth(threeColumnLayoutDevices.DESKTOP),
    layoutClasses: threeColumnLayoutClasses(),
    currentLayoutVariables: defaultLayoutVars,
    mediaQueries: filterQueriesByType(
        defaultLayoutVars.mediaQueries,
        LayoutTypes.THREE_COLUMNS,
    ) as ILayoutMediaQueryFunction,
    contentWidth: defaultLayoutVars.contentWidth,
    calculateDevice: defaultLayoutVars.calculateDevice,
    layoutSpecificStyles: defaultLayoutVars["layoutSpecificStyles"] ?? undefined,
    rightPanelCondition: defaultLayoutVars.rightPanelCondition,
});

export default LayoutContext;

export function useLayout() {
    return useContext(LayoutContext);
}

const defaultRenderRightPanel = (currentDevice, shouldRenderLeftPanel) => {
    return false;
};

export function LayoutProvider(props: { type?: LayoutTypes; children: React.ReactNode }) {
    const { type = LayoutTypes.THREE_COLUMNS, children } = props;
    const defaultLayoutVars = threeColumnLayoutVariables();
    const [deviceInfo, setDeviceInfo] = useState<ILayoutProps>({
        type: LayoutTypes.THREE_COLUMNS,
        currentDevice: threeColumnLayoutDevices.DESKTOP,
        Devices: defaultLayoutVars.Devices as any,
        isCompact: defaultLayoutVars.isCompact(threeColumnLayoutDevices.DESKTOP),
        isFullWidth: defaultLayoutVars.isFullWidth(threeColumnLayoutDevices.DESKTOP),
        layoutClasses: threeColumnLayoutClasses(),
        currentLayoutVariables: defaultLayoutVars,
        mediaQueries: filterQueriesByType(
            defaultLayoutVars.mediaQueries,
            LayoutTypes.THREE_COLUMNS,
        ) as ILayoutMediaQueryFunction,
        contentWidth: defaultLayoutVars.contentWidth,
        calculateDevice: defaultLayoutVars.calculateDevice,
        layoutSpecificStyles: defaultLayoutVars["layoutSpecificStyles"] ?? undefined,
        rightPanelCondition: defaultLayoutVars["rightPanelCondition"] ?? defaultRenderRightPanel,
    });

    const layout = layoutData(type);

    useEffect(() => {
        const throttledUpdate = throttle(() => {
            const currentDevice = layout.variables.calculateDevice();
            setDeviceInfo({
                type,
                currentDevice,
                Devices: layout.variables.Devices as any,
                isCompact: layout.variables.isCompact(currentDevice),
                isFullWidth: layout.variables.isFullWidth(currentDevice),
                layoutClasses: layout.classes,
                currentLayoutVariables: layout.variables,
                mediaQueries: filterQueriesByType(layout.variables.mediaQueries, type),
                contentWidth: layout.variables.contentWidth,
                calculateDevice: layout.variables.calculateDevice,
                layoutSpecificStyles: layout.variables["layoutSpecificStyles"] ?? undefined,
                rightPanelCondition: defaultLayoutVars["rightPanelCondition"] ?? defaultRenderRightPanel,
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
