/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Container from "@library/components/layouts/components/Container";
import { PanelArea, PanelWidgetHorizontalPadding } from "@library/components/layouts/PanelLayout";
import BackLink from "@library/components/navigation/BackLink";
import classNames from "classnames";
import React from "react";

interface IProps {
    className?: string;
}

/**
 * Implement editor header component
 */
export default class NavigationManagerMenu extends React.Component<IProps> {
    public render() {
        return (
            <nav className={classNames("navigationManagerMenu", "modal-pageHeader", this.props.className)}>
                <Container>
                    <PanelArea>
                        <PanelWidgetHorizontalPadding>
                            <ul className="navigationManagerMenu-items">
                                <li className="navigationManagerMenu-item isPullLeft">
                                    <BackLink visibleLabel={true} className="navigationManagerMenu-backLink" />
                                </li>
                            </ul>
                        </PanelWidgetHorizontalPadding>
                    </PanelArea>
                </Container>
            </nav>
        );
    }
}
