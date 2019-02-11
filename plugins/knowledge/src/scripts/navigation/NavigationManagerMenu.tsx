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
import { debugHelper } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { vanillaHeaderVariables } from "@library/styles/vanillaHeaderStyles";
import { px } from "csx";
import { layoutVariables } from "@library/styles/layoutStyles";

interface IProps {
    className?: string;
}

/**
 * Implement editor header component
 */
export default class NavigationManagerMenu extends React.Component<IProps> {
    public render() {
        const debug = debugHelper("navigationManagerMenu");
        const headerVars = vanillaHeaderVariables();
        const mediaQueries = layoutVariables().mediaQueries();
        const heightStyle = style(
            { height: px(headerVars.sizing.height), ...debug.name("items") },
            mediaQueries.oneColumn({
                height: px(headerVars.sizing.mobile.height),
            }),
        );

        return (
            <nav className={classNames("navigationManagerMenu", "modal-pageHeader", this.props.className)}>
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
