/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import { Devices, IDeviceProps } from "@knowledge/components/DeviceChecker";
const DeviceContext = React.createContext<Devices>(Devices.DESKTOP);
export default DeviceContext;

export function withDevice<T extends IDeviceProps = IDeviceProps>(
    WrappedComponent: React.ComponentClass<IDeviceProps>,
) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    class ComponentWithDevice extends React.Component<Omit<T, keyof IDeviceProps>> {
        public static displayName = `withDevice(${displayName})`;
        public render() {
            return (
                <DeviceContext.Consumer>
                    {context => {
                        return <WrappedComponent device={context} {...this.props} />;
                    }}
                </DeviceContext.Consumer>
            );
        }
    }
    return ComponentWithDevice;
}
