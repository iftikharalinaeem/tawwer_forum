/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IResponseArticleDraft } from "@knowledge/@types/api/article";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import MobileDropDown from "@library/headers/pieces/MobileDropDown";
import Container from "@library/layout/components/Container";
import { Devices, IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import FlexSpacer from "@library/layout/FlexSpacer";
import { PanelArea, PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { modalClasses } from "@library/modal/modalStyles";
import BackLink from "@library/routing/links/BackLink";
import { metasClasses } from "@library/styles/metasStyles";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React, { ReactNode } from "react";
import { editorFormClasses } from "@knowledge/modules/editor/editorFormStyles";

interface IProps extends IDeviceProps {
    callToAction?: string;
    canSubmit?: boolean;
    className?: string;
    draft?: ILoadable<IResponseArticleDraft>;
    isSubmitLoading: boolean;
    optionsMenu?: React.ReactNode;
    saveDraft?: ILoadable<{}>;
    selectedLang?: string;
    selectedKey?: string;
    mobileDropDownContent?: React.ReactNode; // Needed for mobile flyouts
    mobileDropDownTitle?: string; // For mobile
    useShadow?: boolean;
}

/**
 * Implement editor header component
 */
export class EditorHeader extends React.Component<IProps> {
    public static defaultProps: Partial<IProps> = {
        callToAction: t("Publish"),
        canSubmit: true,
        draft: {
            status: LoadStatus.PENDING,
        },
        saveDraft: {
            status: LoadStatus.PENDING,
        },
        isSubmitLoading: false,
        useShadow: true,
    };
    public render() {
        const showMobileDropDown = this.props.device === Devices.MOBILE && this.props.mobileDropDownTitle;
        const classesModal = modalClasses();
        const classesEditorForm = editorFormClasses();

        return (
            <nav
                className={classNames(this.props.className, classesModal.pageHeader, {
                    noShadow: !this.props.useShadow,
                })}
            >
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
                                {showMobileDropDown ? (
                                    <li className="editorHeader-center">
                                        <MobileDropDown
                                            title={this.props.mobileDropDownTitle!}
                                            buttonClass="editorHeader-mobileDropDown"
                                            frameBodyClassName="isSelfPadded"
                                        >
                                            {this.props.mobileDropDownContent}
                                        </MobileDropDown>
                                    </li>
                                ) : (
                                    <FlexSpacer tag="li" className="editorHeader-split" />
                                )}
                                <li className="editorHeader-item">
                                    <Button
                                        type="submit"
                                        title={this.props.callToAction}
                                        disabled={!this.props.canSubmit}
                                        baseClass={ButtonTypes.TEXT}
                                        className={classNames(
                                            "editorHeader-publish",
                                            "buttonNoHorizontalPadding",
                                            "buttonNoBorder",
                                            classesEditorForm.publish,
                                        )}
                                    >
                                        {this.props.isSubmitLoading ? <ButtonLoader /> : this.props.callToAction}
                                    </Button>
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
        const { status } = this.props.saveDraft!;
        const { data } = this.props.draft!;
        let content: ReactNode = null;
        const classesMetas = metasClasses();

        if (status === LoadStatus.LOADING) {
            content = (
                <span className={classNames("editorHeader-saveDraft", classesMetas.metaStyle)}>
                    {t("Saving draft...")}
                </span>
            );
        }

        if (data) {
            content = (
                <span className={classNames("editorHeader-saveDraft", classesMetas.metaStyle)}>
                    <Translate
                        source="Draft saved <0/>"
                        c0={<DateTime mode="relative" timestamp={data.dateUpdated} />}
                    />
                </span>
            );
        }

        if (status === LoadStatus.ERROR) {
            content = (
                <span className={classNames("editorHeader-saveDraft", classesMetas.metaStyle, "isError")}>
                    {t("Error saving draft.")}
                </span>
            );
        }

        if (content) {
            return <li className="editorHeader-item editorHeader-itemDraftStatus">{content}</li>;
        } else {
            return null;
        }
    }
}

export default withDevice(EditorHeader);
