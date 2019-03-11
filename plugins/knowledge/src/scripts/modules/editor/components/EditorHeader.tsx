/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { t } from "@library/application";
import { PanelArea, PanelWidgetHorizontalPadding } from "@library/components/layouts/PanelLayout";
import { IDeviceProps } from "@library/components/DeviceChecker";
import BackLink from "@library/components/navigation/BackLink";
import Button from "@library/components/forms/Button";
import classNames from "classnames";
import ButtonLoader from "@library/components/ButtonLoader";
import { LoadStatus, ILoadable } from "@library/@types/api";
import { IResponseArticleDraft } from "@knowledge/@types/api";
import Translate from "@library/components/translation/Translate";
import DateTime from "@library/components/DateTime";
import Container from "@library/components/layouts/components/Container";
import { withDevice } from "@library/contexts/DeviceContext";
import { Devices } from "@library/components/DeviceChecker";
import MobileDropDown from "@library/components/headers/pieces/MobileDropDown";
import FlexSpacer from "@library/components/FlexSpacer";
import { metasClasses } from "library/src/scripts/styles/metasStyles";

interface IProps extends IDeviceProps {
    callToAction?: string;
    canSubmit: boolean;
    className?: string;
    draft?: ILoadable<IResponseArticleDraft>;
    isSubmitLoading: boolean;
    optionsMenu?: React.ReactNode;
    saveDraft?: ILoadable<{}>;
    selectedLang?: string;
    selectedKey?: string;
    mobileDropDownContent?: React.ReactNode; // Needed for mobile dropdown
    mobileDropDownTitle?: string; // For mobile
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
        saveDraft: {
            status: LoadStatus.PENDING,
        },
        isSubmitLoading: false,
    };
    public render() {
        const showMobileDropDown = this.props.device === Devices.MOBILE && this.props.mobileDropDownTitle;
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
                                        className={classNames(
                                            "editorHeader-publish",
                                            "buttonNoHorizontalPadding",
                                            "buttonNoBorder",
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

export default withDevice<IProps>(EditorHeader);
