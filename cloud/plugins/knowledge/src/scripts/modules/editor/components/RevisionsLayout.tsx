/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import EditorHeader from "@knowledge/modules/editor/components/EditorHeader";
import { mobileDropDownClasses } from "@library/headers/pieces/mobileDropDownStyles";
import Container from "@library/layout/components/Container";
import Heading from "@library/layout/Heading";
import { useLayout } from "@library/layout/LayoutContext";
import SmartAlign from "@library/layout/SmartAlign";
import Breadcrumbs, { ICrumb } from "@library/navigation/Breadcrumbs";
import { t } from "@library/utility/appUtils";
import PanelWidget from "@vanilla/library/src/scripts/layout/components/PanelWidget";
import ThreeColumnLayout from "@vanilla/library/src/scripts/layout/ThreeColumnLayout";
import * as React from "react";

interface IProps {
    bodyHeading: React.ReactNode;
    bodyContent: React.ReactNode;
    draftList: React.ReactNode;
    revisionList: React.ReactNode;
    canSubmit: boolean;
    crumbs: ICrumb[];
    mobileDropDownTitle?: string;
}

/**
 * Implements the article's layout
 */
export function RevisionsLayout(props: IProps) {
    const { mobileDropDownTitle, bodyHeading, bodyContent, crumbs } = props;
    const { isCompact, isFullWidth } = useLayout();
    const mobileTitle = mobileDropDownTitle ? mobileDropDownTitle : t("History");

    const classesMobileDropdown = mobileDropDownClasses();

    let mobileDropDownContent: React.ReactNode = (
        <>
            <Heading depth={3} className={classesMobileDropdown.subTitle}>
                <SmartAlign>{t("Revisions")}</SmartAlign>
            </Heading>
            {props.revisionList}
        </>
    );
    if (props.draftList !== null) {
        mobileDropDownContent = (
            <>
                <Heading depth={3} className={classesMobileDropdown.subTitle}>
                    <SmartAlign>{t("Drafts")}</SmartAlign>
                </Heading>
                {props.draftList}
                {mobileDropDownContent}
            </>
        );
    }

    return (
        <>
            <EditorHeader
                canSubmit={props.canSubmit}
                isSubmitLoading={false}
                callToAction={t("Restore")}
                mobileDropDownTitle={mobileTitle}
                mobileDropDownContent={mobileDropDownContent}
            />
            <Container className="richEditorRevisionsForm-body">
                <ThreeColumnLayout
                    topPadding={!isCompact}
                    breadcrumbs={!isCompact && <Breadcrumbs forceDisplay={false}>{crumbs}</Breadcrumbs>}
                    leftTop={isFullWidth && <></>}
                    middleTop={<PanelWidget>{bodyHeading}</PanelWidget>}
                    middleBottom={<PanelWidget>{bodyContent}</PanelWidget>}
                    rightTop={!isCompact && props.draftList}
                    rightBottom={!isCompact && props.revisionList}
                />
            </Container>
        </>
    );
}

export default RevisionsLayout;
