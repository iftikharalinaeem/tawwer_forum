/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import { Devices } from "@knowledge/components/DeviceChecker";
import className from "classnames";
import classNames from "classnames";
import CompoundComponent from "@knowledge/layouts/CompoundComponent";

interface IPanelLayoutProps {
    device: Devices;
    children: React.ReactNode;
    className?: string;
    toggleMobileMenu?: (isOpen: boolean) => void;
}

export default class PanelLayout extends CompoundComponent<IPanelLayoutProps> {
    public static LeftTop = LeftTop;
    public static LeftBottom = LeftBottom;
    public static MiddleTop = MiddleTop;
    public static MiddleBottom = MiddleBottom;
    public static RightTop = RightTop;
    public static RightBottom = RightBottom;
    public static Breadcrumbs = Breadcrumbs;

    public static defaultProps = {
        isMain: false,
    };

    public render() {
        const { children, device } = this.props;

        let leftTop: React.ReactNode = null;
        let leftBottom: React.ReactNode = null;
        let middleTop: React.ReactNode = null;
        let middleBottom: React.ReactNode = null;
        let rightTop: React.ReactNode = null;
        let rightBottom: React.ReactNode = null;
        let breadcrumbs: React.ReactNode = null;

        React.Children.forEach(children, child => {
            switch (true) {
                case this.childIsOfType(child, PanelLayout.LeftTop):
                    leftTop = child;
                    break;
                case this.childIsOfType(child, PanelLayout.LeftBottom):
                    leftBottom = child;
                    break;
                case this.childIsOfType(child, PanelLayout.MiddleTop):
                    middleTop = child;
                    break;
                case this.childIsOfType(child, PanelLayout.MiddleBottom):
                    middleBottom = child;
                    break;
                case this.childIsOfType(child, PanelLayout.RightTop):
                    rightTop = child;
                case this.childIsOfType(child, PanelLayout.RightBottom):
                    rightBottom = child;
                    break;
                case this.childIsOfType(child, PanelLayout.Breadcrumbs):
                    breadcrumbs = child;
                    break;
            }
        });

        const isMobile = device === Devices.MOBILE;
        const isDesktop = device === Devices.DESKTOP;
        const shouldRenderLeftPanel: boolean = !isMobile && !!(leftTop || leftBottom);
        const shouldRenderRightPanel: boolean = isDesktop && !!(rightTop || rightBottom);
        const renderMobilePanel: boolean = isMobile && !!leftBottom;

        const crumbClasses = className(
            "panelLayout-top",
            { noLeftPanel: !shouldRenderLeftPanel },
            this.props.className,
        );

        return (
            <div
                className={className(
                    "panelLayout",
                    { noLeftPanel: !shouldRenderLeftPanel },
                    { noRightPanel: !shouldRenderLeftPanel },
                    this.props.className,
                )}
            >
                {breadcrumbs && (
                    <div className={crumbClasses}>
                        <div className="panelLayout-container">
                            {shouldRenderLeftPanel && (
                                <Panel className="panelLayout-left">
                                    <PanelArea className="panelArea-breadcrumbsSpacer" />
                                </Panel>
                            )}
                            <Panel
                                className={className("panelLayout-breadcrumbs", {
                                    hasAdjacentPanel: shouldRenderLeftPanel,
                                })}
                            >
                                <PanelArea className="panelArea-breadcrumbs">{breadcrumbs}</PanelArea>
                            </Panel>
                        </div>
                    </div>
                )}

                <div className="panelLayout-main">
                    <div className="panelLayout-container">
                        {shouldRenderLeftPanel && (
                            <Panel className="panelLayout-left">
                                <PanelArea className="panelArea-leftTop">{leftTop}</PanelArea>
                                <PanelArea className="panelArea-leftBottom">{leftBottom}</PanelArea>
                            </Panel>
                        )}

                        <div className={classNames("panelLayout-content", { hasAdjacentPanel: shouldRenderLeftPanel })}>
                            <main
                                className={classNames("panelLayout-middle", {
                                    hasAdjacentPanel: shouldRenderRightPanel,
                                })}
                            >
                                <PanelArea className="panelAndNav-middleTop">{middleTop}</PanelArea>
                                {isMobile && <PanelArea className="panelAndNav-mobileMiddle">{leftTop}</PanelArea>}
                                {isDesktop && <PanelArea className="panelAndNav-tabletMiddle">{rightTop}</PanelArea>}
                                <PanelArea className="panelAndNav-middleBottom">{middleBottom}</PanelArea>
                                {isDesktop && <PanelArea className="panelAndNav-tabletBottom">{rightBottom}</PanelArea>}
                            </main>
                            {shouldRenderRightPanel && (
                                <Panel className="panelLayout-right">
                                    <PanelArea className="panelArea-rightTop">{rightTop}</PanelArea>
                                    <PanelArea className="panelArea-rightBottom">{rightBottom}</PanelArea>
                                </Panel>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

interface IContainerProps {
    className?: string;
    children?: React.ReactNode;
}

export function Panel(props: IContainerProps) {
    return <div className={className("panelLayout-panel", props.className)}>{props.children}</div>;
}

export function PanelArea(props: IContainerProps) {
    return <div className={className("panelArea", props.className)}>{props.children}</div>;
}

export function PanelWidget(props: IContainerProps) {
    return <div className={className("panelWidget", props.className)}>{props.children}</div>;
}

interface IPanelItemProps {
    children?: React.ReactNode;
}

export function LeftTop(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}

export function LeftBottom(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}

export function MiddleTop(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}

export function MiddleBottom(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}

export function RightTop(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}

export function RightBottom(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}

export function Breadcrumbs(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}
