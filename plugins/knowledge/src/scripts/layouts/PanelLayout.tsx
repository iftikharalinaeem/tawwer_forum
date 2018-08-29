import * as React from "react";
import {Devices} from "../components/DeviceChecker";
import Panel from "../components/Panel";
import className from "classnames";
import PanelArea from "../components/PanelArea";
import {t} from "@dashboard/application";

export interface IPanelCellContent {
    middleTopComponents: JSX.Element;
    middleBottomComponents: JSX.Element;
    leftTopComponents?: JSX.Element;
    leftBottomComponents?: JSX.Element;
    rightTopComponents?: JSX.Element;
    rightBottomComponents?: JSX.Element;
}

interface IPanelLayoutProps {
    device: Devices;
    children: IPanelCellContent;
    className?: string;
}
//
// interface IState {
//     leftPanelRendered: boolean;
//     rightPanelRendered: boolean;
// }

export default class PanelLayout extends React.Component<IPanelLayoutProps> {
    public static defaultProps = {
        isMain: false,
    };
    //
    // public constructor(props) {
    //     super(props);
    //     // this.setLeftPanelRenderedState = this.setLeftPanelRenderedState.bind(this);
    //     // this.setRightPanelRenderedState = this.setRightPanelRenderedState.bind(this);
    // }


    public render() {
        const children = this.props.children;
        const isMobile = this.props.device === Devices.MOBILE;
        const renderedLeftPanel:boolean = !!(!isMobile && children.leftTopComponents && children.leftBottomComponents) || (isMobile && !!children.leftBottomComponents);
        const renderedRightPanel:boolean = !!(children.rightTopComponents || children.rightTopComponents) && this.props.device === Devices.DESKTOP;

        return <div className={className('panelLayout', this.props.className)}>
            <Panel className="panelLayout-leftPanel" rendered={renderedLeftPanel}>
                {
                    {
                        top: {
                            children: children.leftTopComponents,
                            className: "panelAndNav-leftTop",
                            rendered: !isMobile,
                        },
                        bottom: {
                            children: children.leftBottomComponents,
                            className: "panelAndNav-leftBottom",
                        },
                    }
                }
            </Panel>
            <div className="panelLayout-content">
                <main className="panelAndNav-mainPanel">
                    <PanelArea className="panelAndNav-middleTop">
                        {children.middleTopComponents}
                    </PanelArea>
                    <PanelArea className="panelAndNav-mobileMiddle" render={isMobile}>
                        {children.leftTopComponents}
                    </PanelArea>
                    <PanelArea className="panelAndNav-tabletMiddle" render={this.props.device !== Devices.DESKTOP}>
                        {children.rightTopComponents}
                    </PanelArea>
                    <PanelArea className="panelAndNav-middleBottom">
                        {children.middleBottomComponents}
                    </PanelArea>
                </main>
                <Panel className="panelLayout-rightPanel" rendered={renderedRightPanel}>
                    {
                        {
                            top: {
                                children: children.rightTopComponents,
                                className: "panelAndNav-rightTop",
                                rendered: !isMobile,
                            },
                            bottom: {
                                children: children.rightBottomComponents,
                                className: "panelAndNav-rightBottom",
                            },
                        }
                    }
                </Panel>
            </div>
        </div>;
    }

    // private setLeftPanelRenderedState(isRendered) {
    //     this.setState({
    //         leftPanelRendered: isRendered,
    //     });
    // }
    //
    // private setRightPanelRenderedState(isRendered) {
    //     this.setState({
    //         rightPanelRendered: isRendered,
    //     });
    // }
}
