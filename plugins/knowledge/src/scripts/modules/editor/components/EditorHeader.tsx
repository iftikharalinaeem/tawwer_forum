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
import Translate from "@library/components/translation/Translate";
import DateTime from "@library/components/DateTime";
import LanguagesDropDown from "@library/components/LanguagesDropDown";
import { dummyOtherLanguagesData } from "@library/state/dummyOtherLanguages";
import Container from "@library/components/layouts/components/Container";
import { withDevice } from "@library/contexts/DeviceContext";

interface IProps extends IDeviceProps {
    canSubmit: boolean;
    draft?: ILoadable<IResponseArticleDraft>;
    selectedKey?: string;
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
        draft: {
            status: LoadStatus.PENDING,
        },
        isSubmitLoading: false,
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

    private renderDraftIndicator(): React.ReactNode {
        const { status, data } = this.props.draft!;
        if (status === LoadStatus.LOADING) {
            return (
                <li className="editorHeader-item">
                    <span className="editorHeader-saveDraft metaStyle">{t("Saving Draft...")}</span>
                </li>
            );
        }

        if (data) {
            return (
                <li className="editorHeader-item">
                    <span className="editorHeader-saveDraft metaStyle">
                        <Translate
                            source="Draft Saved <0/>"
                            c0={<DateTime mode="relative" timestamp={data.dateUpdated} />}
                        />
                    </span>
                </li>
            );
        }

        if (status === LoadStatus.ERROR) {
            return (
                <li className="editorHeader-item">
                    <span className="editorHeader-saveDraft metaStyle isError">{t("Error Saving Draft.")}</span>
                </li>
            );
        }

        return null;
    }
}

export default withDevice<IProps>(EditorHeader);
