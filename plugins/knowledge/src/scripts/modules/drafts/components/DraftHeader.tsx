/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import * as React from "react";
import { t } from "@library/application";
import BackLink from "@library/components/navigation/BackLink";
import classNames from "classnames";
import Container from "@library/components/layouts/components/Container";
import { PanelArea, PanelWidgetHorizontalPadding } from "@library/components/layouts/PanelLayout";
import MobileDropDown from "@library/components/headers/pieces/MobileDropDown";

interface IProps {
    className?: string;
    mobileDropDownTitle?: string;
}

/**
 * Implements actions to take on draft
 */
export default class DraftHeader extends React.Component<IProps> {
    public render() {
        return (
            <nav className={classNames("draftPage-header", "modal-pageHeader", this.props.className)}>
                <Container>
                    <PanelArea>
                        <ul className="editorHeader-items">
                            <li className="editorHeader-item isPullLeft">
                                <PanelWidgetHorizontalPadding>
                                    <BackLink title={t("Back")} className="draftPage-backLink" visibleLabel={true} />
                                </PanelWidgetHorizontalPadding>
                            </li>
                            {this.props.mobileDropDownTitle && (
                                <li className="editorHeader-center">
                                    <MobileDropDown
                                        title={this.props.mobileDropDownTitle!}
                                        buttonClass="editorHeader-mobileDropDown"
                                    />
                                </li>
                            )}
                        </ul>
                    </PanelArea>
                </Container>
            </nav>
        );
    }
}
