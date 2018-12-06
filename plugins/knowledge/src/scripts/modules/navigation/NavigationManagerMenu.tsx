/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";
import { PanelArea, PanelWidgetHorizontalPadding } from "@library/components/layouts/PanelLayout";
import { IDeviceProps } from "@library/components/DeviceChecker";
import BackLink from "@library/components/navigation/BackLink";
import { ButtonBaseClass } from "@library/components/forms/Button";
import classNames from "classnames";
import { LoadStatus, ILoadable } from "@library/@types/api";
import { IResponseArticleDraft } from "@knowledge/@types/api";
import { dummyOtherLanguagesData } from "@library/state/dummyOtherLanguages";
import Container from "@library/components/layouts/components/Container";
import { withDevice } from "@library/contexts/DeviceContext";
import { Devices } from "@library/components/DeviceChecker";

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
