/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import debounce from "lodash/debounce";

export enum Devices {
    MOBILE = "mobile",
    TABLET = "tablet",
    DESKTOP = "desktop",
}

export interface IDeviceProps {
    device: Devices;
}

interface IDeviceCheckerProps {
    doUpdate: () => void;
}

export default class DeviceChecker extends React.Component<IDeviceCheckerProps> {
    public deviceChecker: React.RefObject<HTMLDivElement> = React.createRef();

    public render() {
        return <div ref={this.deviceChecker} className="deviceChecker" />;
    }

    public get device() {
        if (this.deviceChecker.current) {
            let device = Devices.DESKTOP;
            switch (`${this.deviceChecker.current.offsetWidth}`) {
                case "1":
                    device = Devices.MOBILE;
                    break;
                case "2":
                    device = Devices.TABLET;
                    break;
            }
            return device;
        } else {
            throw new Error("deviceChecker does not exist");
        }
    }

    public componentDidMount() {
        window.addEventListener("resize", e => {
            debounce(
                () => {
                    window.requestAnimationFrame(data => {
                        this.props.doUpdate();
                    });
                },
                100,
                {
                    leading: true,
                },
            )();
        });
    }
}
