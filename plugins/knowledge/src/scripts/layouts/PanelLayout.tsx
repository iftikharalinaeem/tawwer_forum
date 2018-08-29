import * as React from "react";
import {Devices} from "../components/DeviceChecker";
import Panel from "../components/Panel";
import className from "classnames";
import PanelArea from "../components/PanelArea";
import {t} from "@dashboard/application";
import {IBreadcrumbsProps} from "../components/Breadcrumbs";
import PanelLayoutBreadcrumbs from "../components/PanelLayoutBreadcrumbs";

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
    breadcrumbs?: IBreadcrumbsProps;
    children: IPanelCellContent;
    className?: string;
}

export default class PanelLayout extends React.Component<IPanelLayoutProps> {
    public static defaultProps = {
        isMain: false,
    };

    public render() {
        const children = this.props.children;
        const isMobile = this.props.device === Devices.MOBILE;
        const renderedLeftPanel:boolean = !!(!isMobile && children.leftTopComponents && children.leftBottomComponents) || (isMobile && !!children.leftBottomComponents);
        const renderedRightPanel:boolean = !!(children.rightTopComponents || children.rightTopComponents) && this.props.device === Devices.DESKTOP;

        return <div className={className('panelLayout', this.props.className)}>
            <PanelLayoutBreadcrumbs renderLeftPanel={renderedLeftPanel} breadcrumbs={this.props.breadcrumbs} />

            <div className="panelLayout-main">
                <Panel className="panelLayout-leftPanel" render={renderedLeftPanel}>
                    {
                        {
                            top: {
                                children: children.leftTopComponents,
                                className: "panelAndNav-leftTop",
                                render: !isMobile,
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
                    <Panel className="panelLayout-rightPanel" render={renderedRightPanel}>
                        {
                            {
                                top: {
                                    children: children.rightTopComponents,
                                    className: "panelAndNav-rightTop",
                                    render: this.props.device !== Devices.DESKTOP,
                                },
                                bottom: {
                                    children: children.rightBottomComponents,
                                    className: "panelAndNav-rightBottom",
                                },
                            }
                        }
                    </Panel>
                </div>
            </div>
        </div>;
    }
}
