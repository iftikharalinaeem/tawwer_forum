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
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { modalClasses } from "@library/modal/modalStyles";
import { navigationManagerClasses } from "@knowledge/navigation/navigationManagerStyles";
import TitleBar from "@vanilla/library/src/scripts/headers/TitleBar";

interface IProps {
    className?: string;
}

/**
 * Implement editor header component
 */
export default class NavigationManagerMenu extends React.Component<IProps> {
    public render() {
        const debug = debugHelper("navigationManagerMenu");
        const titleBarVars = titleBarVariables();
        const classesModal = modalClasses();
        const classes = navigationManagerClasses();
        const height = classes.height(titleBarVars);

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
                            <TitleBar useMobileBackButton={false} />
                            <ul className={classNames("navigationManagerMenu-items", height)}>
                                <li className={classNames("navigationManagerMenu-item", "isPullLeft", height)}>
                                    <BackLink
                                        visibleLabel={true}
                                        className={classNames("navigationManagerMenu-backLink", height)}
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
