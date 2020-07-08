/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IResponseArticleDraft, IArticle } from "@knowledge/@types/api/article";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import MobileDropDown from "@library/headers/pieces/MobileDropDown";
import Container from "@library/layout/components/Container";
import { Devices, useDevice } from "@library/layout/DeviceContext";

import ButtonLoader from "@library/loaders/ButtonLoader";
import { modalClasses } from "@library/modal/modalStyles";
import BackLink from "@library/routing/links/BackLink";
import { metasClasses } from "@library/styles/metasStyles";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React, { ReactNode, useRef } from "react";
import { editorHeaderClasses } from "@knowledge/modules/editor/components/editorHeaderStyles";
import { unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useMeasure } from "@vanilla/react-utils";
import PanelArea from "@vanilla/library/src/scripts/layout/components/PanelArea";
import PanelWidgetHorizontalPadding from "@vanilla/library/src/scripts/layout/components/PanelWidgetHorizontalPadding";

interface IProps {
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
export default function EditorHeader(props: IProps) {
    const device = useDevice();
    const showMobileDropDown = (device === Devices.MOBILE || device === Devices.XS) && props.mobileDropDownTitle;

    const restoreRef = useRef<HTMLLIElement | null>(null);
    const restoreSize = useMeasure(restoreRef);
    const backRef = useRef<HTMLLIElement | null>(null);
    const backSize = useMeasure(backRef);
    const largerWidth = backSize.width > restoreSize.width ? backSize.width : restoreSize.width;

    const classesModal = modalClasses();
    const classesEditorHeader = editorHeaderClasses();
    const globalVars = globalVariables();

    const minButtonSizeStyles: React.CSSProperties =
        restoreSize.width && backSize.width
            ? { minWidth: unit(largerWidth) }
            : { minWidth: unit(globalVars.icon.sizes.default) };

    const content = (
        <ul className={classNames(classesEditorHeader.items)}>
            <li
                className={classNames(classesEditorHeader.item, "isPullLeft")}
                ref={backRef}
                style={minButtonSizeStyles}
            >
                <BackLink
                    title={t("Cancel")}
                    visibleLabel={true}
                    className={classNames("editorHeader-backLink", classesEditorHeader.backLink)}
                />
            </li>
            <DraftIndicator
                draftData={props.draft && props.draft.data}
                draftStatus={props.saveDraft && props.saveDraft.status}
            />
            {showMobileDropDown ? (
                <li className={classNames(classesEditorHeader.centreColumn, "editorHeader-center")}>
                    <MobileDropDown
                        title={props.mobileDropDownTitle!}
                        buttonClass="editorHeader-mobileDropDown2"
                        frameBodyClassName="isSelfPadded"
                    >
                        {props.mobileDropDownContent}
                    </MobileDropDown>
                </li>
            ) : null}
            <li
                ref={restoreRef}
                className={classNames(classesEditorHeader.item, "isPullRight")}
                style={minButtonSizeStyles}
            >
                <Button
                    submit={true}
                    title={props.callToAction}
                    disabled={!props.canSubmit}
                    baseClass={ButtonTypes.TEXT_PRIMARY}
                    className={classNames(
                        "buttonNoHorizontalPadding",
                        "buttonNoBorder",
                        classesEditorHeader.publish,
                        classesEditorHeader.itemMarginLeft,
                    )}
                >
                    {props.isSubmitLoading ? <ButtonLoader /> : props.callToAction}
                </Button>
            </li>
            {props.optionsMenu && (
                <li className={classNames("editorHeader-item", classesEditorHeader.itemMarginLeft)}>
                    {props.optionsMenu}
                </li>
            )}
        </ul>
    );

    return (
        <nav
            className={classNames(props.className, classesModal.pageHeader, {
                noShadow: !props.useShadow,
            })}
        >
            {!props.selfPadded && (
                <PanelArea>
                    <Container>
                        <PanelWidgetHorizontalPadding>{content}</PanelWidgetHorizontalPadding>
                    </Container>
                </PanelArea>
            )}
            {props.selfPadded && content}
        </nav>
    );
}

(EditorHeader as React.FC).defaultProps = {
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

function DraftIndicator(props: { draftStatus: LoadStatus | undefined; draftData: IResponseArticleDraft | undefined }) {
    const { draftStatus, draftData } = props;
    let content: ReactNode = null;
    const classesMetas = metasClasses();

    if (draftStatus === LoadStatus.LOADING) {
        content = (
            <span className={classNames("editorHeader-saveDraft", classesMetas.metaStyle)}>{t("Saving draft...")}</span>
        );
    }

    if (draftData) {
        content = (
            <span className={classNames("editorHeader-saveDraft", classesMetas.metaStyle)}>
                <Translate
                    source="Draft saved <0/>"
                    c0={<DateTime mode="relative" timestamp={draftData.dateUpdated} />}
                />
            </span>
        );
    }

    if (draftStatus === LoadStatus.ERROR) {
        content = (
            <span className={classNames("editorHeader-saveDraft", classesMetas.metaStyle, "isError")}>
                {t("Error saving draft.")}
            </span>
        );
    }

    if (content) {
        return <li className={classNames(classesMetas.meta, classesMetas.draftStatus)}>{content}</li>;
    } else {
        return null;
    }
}
