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
import { PanelArea, PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { modalClasses } from "@library/modal/modalStyles";
import BackLink from "@library/routing/links/BackLink";
import { metasClasses } from "@library/styles/metasStyles";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React, { ReactNode } from "react";
import { editorHeaderClasses } from "@knowledge/modules/editor/components/editorHeaderStyles";
import { unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";

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
    selfPadded?: boolean;
}

interface IState {
    actionWidth: number | null;
}

/**
 * Implement editor header component
 */
export class EditorHeader extends React.Component<IProps, IState> {
    private restoreRef: React.RefObject<HTMLLIElement> = React.createRef();
    public state: IState = {
        actionWidth: null,
    };
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
        const showMobileDropDown =
            (this.props.device === Devices.MOBILE || this.props.device === Devices.XS) &&
            this.props.mobileDropDownTitle;
        const classesModal = modalClasses();
        const classesEditorHeader = editorHeaderClasses();
        const globalVars = globalVariables();

        const content = (
            <ul className={classNames(classesEditorHeader.items)}>
                <li
                    className={classNames(classesEditorHeader.item, "isPullLeft")}
                    style={
                        this.state.actionWidth && showMobileDropDown
                            ? { minWidth: unit(this.state.actionWidth) }
                            : { minWidth: unit(globalVars.icon.sizes.default) }
                    }
                >
                    <BackLink
                        title={t("Cancel")}
                        visibleLabel={true}
                        className={classNames("editorHeader-backLink", classesEditorHeader.backLink)}
                    />
                </li>
                {this.renderDraftIndicator()}
                {showMobileDropDown ? (
                    <li className={classNames(classesEditorHeader.centreColumn, "editorHeader-center")}>
                        <MobileDropDown
                            title={this.props.mobileDropDownTitle!}
                            buttonClass="editorHeader-mobileDropDown2"
                            frameBodyClassName="isSelfPadded"
                        >
                            {this.props.mobileDropDownContent}
                        </MobileDropDown>
                    </li>
                ) : null}
                <li
                    ref={this.restoreRef}
                    className={classNames(classesEditorHeader.item, classesEditorHeader.itemMarginLeft)}
                >
                    <Button
                        type="submit"
                        title={this.props.callToAction}
                        disabled={!this.props.canSubmit}
                        baseClass={ButtonTypes.TEXT_PRIMARY}
                        className={classNames(
                            "buttonNoHorizontalPadding",
                            "buttonNoBorder",
                            classesEditorHeader.publish,
                            classesEditorHeader.itemMarginLeft,
                        )}
                    >
                        {this.props.isSubmitLoading ? <ButtonLoader /> : this.props.callToAction}
                    </Button>
                </li>
                {this.props.optionsMenu && (
                    <li className={classNames("editorHeader-item", classesEditorHeader.itemMarginLeft)}>
                        {this.props.optionsMenu}
                    </li>
                )}
            </ul>
        );

        return (
            <nav
                className={classNames(this.props.className, classesModal.pageHeader, {
                    noShadow: !this.props.useShadow,
                })}
            >
                {!this.props.selfPadded && (
                    <PanelArea>
                        <Container>
                            <PanelWidgetHorizontalPadding>{content}</PanelWidgetHorizontalPadding>
                        </Container>
                    </PanelArea>
                )}
                {this.props.selfPadded && content}
            </nav>
        );
    }

    public componentDidUpdate(prevProps: Readonly<IProps>, prevState: Readonly<{}>, snapshot?: any): void {
        if (!this.state.actionWidth && this.restoreRef.current) {
            this.setState({
                actionWidth: this.restoreRef.current.offsetWidth,
            });
        }
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
            return (
                <li
                    className={classNames(
                        "editorHeader-item",
                        "editorHeader-itemDraftStatus",
                        classesMetas.draftStatus,
                    )}
                >
                    {content}
                </li>
            );
        } else {
            return null;
        }
    }
}

export default withDevice(EditorHeader);
