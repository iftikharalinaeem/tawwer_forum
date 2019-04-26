/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Container from "@library/layout/components/Container";
import { PanelArea, PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";
import BackLink from "@library/routing/links/BackLink";
import classNames from "classnames";
import React from "react";
import { debugHelper } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { px } from "csx";
import { layoutVariables } from "@library/layout/layoutStyles";
import { modalClasses } from "@library/modal/modalStyles";

interface IProps {
    className?: string;
}

/**
 * Implement editor header component
 */
export default class NavigationManagerMenu extends React.Component<IProps> {
    public render() {
        const debug = debugHelper("navigationManagerMenu");
        const headerVars = titleBarVariables();
        const mediaQueries = layoutVariables().mediaQueries();
        const classesModal = modalClasses();
        const heightStyle = style(
            { height: px(headerVars.sizing.height), ...debug.name("items") },
            mediaQueries.oneColumn({
                height: px(headerVars.sizing.mobile.height),
            }),
        );

        return (
            <nav
                className={classNames(
                    "navigationManagerMenu",
                    "modal-pageHeader",
                    classesModal.pageHeader,
                    this.props.className,
                )}
            >
                <Container>
                    <PanelArea>
                        <PanelWidgetHorizontalPadding>
                            <ul className={classNames("navigationManagerMenu-items", heightStyle)}>
                                <li className={classNames("navigationManagerMenu-item", "isPullLeft", heightStyle)}>
                                    <BackLink
                                        visibleLabel={true}
                                        className={classNames("navigationManagerMenu-backLink", heightStyle)}
                                    />
                                </li>
                            </ul>
                        </PanelWidgetHorizontalPadding>
                    </PanelArea>
                </Container>
            </nav>
        );
    }
}
