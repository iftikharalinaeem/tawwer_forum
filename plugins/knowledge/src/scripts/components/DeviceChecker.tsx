import * as React from "react";

export enum Devices {
    MOBILE = "mobile",
    TABLET = "tablet",
    DESKTOP = "desktop",
}

export interface IDevice {
    device: Devices;
}

export default class DeviceChecker extends React.Component {
    public deviceChecker: React.RefObject<HTMLDivElement> = React.createRef();

    constructor(props) {
        super(props);
    }

    public render() {
        return (
            <div ref={this.deviceChecker} className="deviceChecker" />
        );
    }

    public get device() {
        if (this.deviceChecker.current) {
            switch (this.deviceChecker.current.offsetWidth) {
                default:
                    return Devices.DESKTOP;
            }
        } else {
            throw new Error("deviceChecker does not exist");
        }
    }

    public componentDidMount() {
        console.log("Device checker: ", this.device);
    }
}
