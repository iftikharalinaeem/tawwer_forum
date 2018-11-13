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
import { uniqueIDFromPrefix } from "@library/componentIDs";
import ButtonLoader from "@library/components/ButtonLoader";
import { withDevice } from "@library/contexts/DeviceContext";
import Container from "@library/components/layouts/components/Container";
import { dummyOtherLanguagesData } from "@knowledge/state/dummyOtherLanguages";
import LanguagesDropDown from "@library/components/LanguagesDropDown";

interface IProps extends IDeviceProps {
    canSubmit: boolean;
    isSubmitLoading: boolean;
    selectedLang?: string;
    className?: string;
    callToAction?: string;
    optionsMenu?: React.ReactNode;
}

/**
 * Implement editor header component
 */
export class EditorHeader extends React.Component<IProps> {
    public static defaultProps: Partial<IProps> = {
        callToAction: t("Publish"),
    };
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
                                        widthOfParent={true}
                                        className="editorHeader-otherLanguages"
                                        renderLeft={true}
                                        buttonClassName="buttonNoBorder buttonNoMinWidth buttonNoHorizontalPadding editorHeader-otherLanguagesToggle"
                                        buttonBaseClass={ButtonBaseClass.STANDARD}
                                        selected={this.props.selectedLang}
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

export default withDevice<IProps>(EditorHeader);
