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
        const { device } = this.props;
        const children = this.getParsedChildren();

        // Calculate some rendering variables.
        const isMobile = device === Devices.MOBILE;
        const isDesktop = device === Devices.DESKTOP;
        const shouldRenderLeftPanel: boolean = !isMobile && !!(children.leftTop || children.leftBottom);
        const shouldRenderRightPanel: boolean = isDesktop && !!(children.rightTop || children.rightBottom);
        const renderMobilePanel: boolean = isMobile && !!children.leftBottom;

        // Determine the classes we want to display.
        const panelClasses = className(
            "panelLayout",
            { noLeftPanel: !shouldRenderLeftPanel },
            { noRightPanel: !shouldRenderLeftPanel },
            this.props.className,
        );

        const crumbClasses = className(
            "panelLayout-top",
            { noLeftPanel: !shouldRenderLeftPanel },
            this.props.className,
        );

        return (
            <div className={panelClasses}>
                {children.breadcrumbs && (
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
                                <PanelArea className="panelArea-breadcrumbs">{children.breadcrumbs}</PanelArea>
                            </Panel>
                        </div>
                    </div>
                )}

                <div className="panelLayout-main">
                    <div className="panelLayout-container">
                        {shouldRenderLeftPanel && (
                            <Panel className="panelLayout-left">
                                <PanelArea className="panelArea-leftTop">{children.leftTop}</PanelArea>
                                <PanelArea className="panelArea-leftBottom">{children.leftBottom}</PanelArea>
                            </Panel>
                        )}

                        <div className={classNames("panelLayout-content", { hasAdjacentPanel: shouldRenderLeftPanel })}>
                            <main
                                className={classNames("panelLayout-middle", {
                                    hasAdjacentPanel: shouldRenderRightPanel,
                                })}
                            >
                                <PanelArea className="panelAndNav-middleTop">{children.middleTop}</PanelArea>
                                {isMobile && (
                                    <PanelArea className="panelAndNav-mobileMiddle">{children.leftTop}</PanelArea>
                                )}
                                {isDesktop && (
                                    <PanelArea className="panelAndNav-tabletMiddle">{children.rightTop}</PanelArea>
                                )}
                                <PanelArea className="panelAndNav-middleBottom">{children.middleBottom}</PanelArea>
                                {isDesktop && (
                                    <PanelArea className="panelAndNav-tabletBottom">{children.rightBottom}</PanelArea>
                                )}
                            </main>
                            {shouldRenderRightPanel && (
                                <Panel className="panelLayout-right">
                                    <PanelArea className="panelArea-rightTop">{children.rightTop}</PanelArea>
                                    <PanelArea className="panelArea-rightBottom">{children.rightBottom}</PanelArea>
                                </Panel>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    /**
     * Parse out a specific subset of children. This is fast enough,
     * but should not be called more than once per render.
     */
    private getParsedChildren() {
        let leftTop: React.ReactNode = null;
        let leftBottom: React.ReactNode = null;
        let middleTop: React.ReactNode = null;
        let middleBottom: React.ReactNode = null;
        let rightTop: React.ReactNode = null;
        let rightBottom: React.ReactNode = null;
        let breadcrumbs: React.ReactNode = null;

        React.Children.forEach(this.props.children, child => {
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

        return {
            leftTop,
            leftBottom,
            middleTop,
            middleBottom,
            rightTop,
            rightBottom,
            breadcrumbs,
        };
    }
}

// Simple container components.
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

// The components that make up the
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
