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
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import classNames from "classnames";
import ButtonLoader from "@library/components/ButtonLoader";
import { LoadStatus, ILoadable } from "@library/@types/api";
import { IResponseArticleDraft } from "@knowledge/@types/api";
import LanguagesDropDown from "@library/components/LanguagesDropDown";
import { dummyOtherLanguagesData } from "@library/state/dummyOtherLanguages";
import Container from "@library/components/layouts/components/Container";
import { withDevice } from "@library/contexts/DeviceContext";
import { Devices } from "@library/components/DeviceChecker";

interface IProps extends IDeviceProps {}

/**
 * Implement editor header component
 */
export class NavigationManagerMenu extends React.Component<IProps> {
    public render() {
        return (
            <nav className={classNames("editorHeader", "modal-pageHeader", this.props.className)}>
                <Container>
                    <PanelArea>
                        <PanelWidgetHorizontalPadding>
                            <ul className="editorHeader-items">
                                <li className="editorHeader-item isPullLeft">
                                    <BackLink
                                        title={t("Cancel")}
                                        visibleLabel={true}
                                        className="editorHeader-backLink"
                                    />
                                </li>
                                {this.renderDraftIndicator()}
                                <li className="editorHeader-center" role="presentation" />
                                <li className="editorHeader-item">
                                    <Button
                                        type="submit"
                                        title={this.props.callToAction}
                                        disabled={!this.props.canSubmit}
                                        className={classNames(
                                            "editorHeader-publish",
                                            "buttonNoHorizontalPadding",
                                            "buttonNoBorder",
                                        )}
                                    >
                                        {this.props.isSubmitLoading ? <ButtonLoader /> : this.props.callToAction}
                                    </Button>
                                </li>
                                <li className="editorHeader-item">
                                    <LanguagesDropDown
                                        widthOfParent={false}
                                        className="editorHeader-otherLanguages"
                                        renderLeft={true}
                                        buttonClassName="buttonNoBorder buttonNoMinWidth buttonNoHorizontalPadding editorHeader-otherLanguagesToggle"
                                        buttonBaseClass={ButtonBaseClass.STANDARD}
                                        selected={this.props.selectedLang}
                                        openAsModal={this.props.device === Devices.MOBILE}
                                    >
                                        {dummyOtherLanguagesData.children}
                                    </LanguagesDropDown>
                                </li>
                                {this.props.optionsMenu && (
                                    <li className="editorHeader-item">{this.props.optionsMenu}</li>
                                )}
                            </ul>
                        </PanelWidgetHorizontalPadding>
                    </PanelArea>
                </Container>
            </nav>
        );
    }
}

export default withDevice<IProps>(NavigationManagerMenu);
