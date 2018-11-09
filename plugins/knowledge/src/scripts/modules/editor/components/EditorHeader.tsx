/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";
import { PanelArea } from "@knowledge/layouts/PanelLayout";
import { PanelWidgetHorizontalPadding } from "@knowledge/layouts/PanelLayout";
import { IDeviceProps } from "@library/components/DeviceChecker";
import BackLink from "@library/components/BackLink";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import classNames from "classnames";
import SelectBox from "@library/components/SelectBox";
import { dummyOtherLanguagesData } from "../../categories/state/dummyOtherLanguages";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import ButtonLoader from "@library/components/ButtonLoader";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import Container from "@knowledge/layouts/components/Container";

interface IProps extends IDeviceProps {
    canSubmit: boolean;
    isSubmitLoading: boolean;
    selectedKey?: string;
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

    private localeTitleID = uniqueIDFromPrefix("editorHeader");

    public render() {
        let foundIndex = false;
        const processedChildren = dummyOtherLanguagesData.children.map(language => {
            // This is all hard coded for now.
            const selected = language.key === this.props.selectedKey;
            language.selected = selected;
            if (selected) {
                foundIndex = selected;
            }
            return language;
        });
        if (!foundIndex) {
            processedChildren[0].selected = true;
        }

        const label = t("Switch Locale");

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
                                        title={label}
                                        disabled={!this.props.canSubmit}
                                        baseClass={ButtonBaseClass.TEXT}
                                        className={classNames("editorHeader-publish")}
                                    >
                                        {this.props.isSubmitLoading ? <ButtonLoader /> : this.props.callToAction}
                                    </Button>
                                </li>
                                <li className="editorHeader-item">
                                    <h3 id={this.localeTitleID} className="sr-only">
                                        {label}
                                    </h3>
                                    <SelectBox
                                        describedBy={this.localeTitleID}
                                        className="editorHeader-otherLanguages"
                                        buttonClassName="buttonNoBorder buttonNoMinWidth"
                                        buttonBaseClass={ButtonBaseClass.STANDARD}
                                        renderLeft={true}
                                    >
                                        {processedChildren}
                                    </SelectBox>
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
