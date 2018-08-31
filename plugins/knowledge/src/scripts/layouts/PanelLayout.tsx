/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import { Devices } from "@knowledge/components/DeviceChecker";
import Panel from "@knowledge/components/Panel";
import className from "classnames";
import PanelArea from "@knowledge/components/PanelArea";
import { t } from "@dashboard/application";
import { IBreadcrumbsProps } from "@knowledge/components/Breadcrumbs";
import PanelLayoutBreadcrumbs from "@knowledge/components/PanelLayoutBreadcrumbs";

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
    toggleMobileMenu?: (open) => void;
}

export default class PanelLayout extends React.Component<IPanelLayoutProps> {
    public static defaultProps = {
        isMain: false,
    };

    public render() {
        const children = this.props.children;
        const isMobile = this.props.device === Devices.MOBILE;
        const renderedLeftPanel: boolean =
            !!(!isMobile && children.leftTopComponents && children.leftBottomComponents) ||
            (isMobile && !!children.leftBottomComponents);
        const renderedRightPanel: boolean =
            !!(children.rightTopComponents || children.rightTopComponents) && this.props.device === Devices.DESKTOP;

        return (
            <div className={className("panelLayout", this.props.className)}>
                <PanelLayoutBreadcrumbs renderLeftPanel={renderedLeftPanel} breadcrumbs={this.props.breadcrumbs} />

                <div className="panelLayout-main">
                    <div className="panelLayout-container">
                        <Panel className="panelLayout-left" render={renderedLeftPanel}>
                            {{
                                top: {
                                    children: children.leftTopComponents,
                                    className: "panelArea-leftTop",
                                    render: !isMobile,
                                },
                                bottom: {
                                    children: children.leftBottomComponents,
                                    className: "panelArea-leftBottom",
                                },
                            }}
                        </Panel>
                        <div className="panelLayout-content">
                            <main className="panelLayout-middle">
                                <PanelArea className="panelAndNav-middleTop">{children.middleTopComponents}</PanelArea>
                                <PanelArea className="panelAndNav-mobileMiddle" render={isMobile}>
                                    {children.leftTopComponents}
                                </PanelArea>
                                <PanelArea
                                    className="panelAndNav-tabletMiddle"
                                    render={this.props.device !== Devices.DESKTOP}
                                >
                                    {children.rightTopComponents}
                                </PanelArea>
                                <PanelArea className="panelAndNav-middleBottom">
                                    {children.middleBottomComponents}
                                </PanelArea>
                                <PanelArea
                                    className="panelAndNav-tabletBottom"
                                    render={this.props.device !== Devices.DESKTOP}
                                >
                                    {children.rightBottomComponents}
                                </PanelArea>
                            </main>
                            <Panel className="panelLayout-right" render={renderedRightPanel}>
                                {{
                                    top: {
                                        children: children.rightTopComponents,
                                        className: "panelArea-rightTop",
                                    },
                                    bottom: {
                                        children: children.rightBottomComponents,
                                        className: "panelArea-rightBottom",
                                    },
                                }}
                            </Panel>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}
