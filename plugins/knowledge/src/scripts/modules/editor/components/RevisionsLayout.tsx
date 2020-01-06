/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import EditorHeader from "@knowledge/modules/editor/components/EditorHeader";
import Container from "@library/layout/components/Container";
import { Devices, IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import Heading from "@library/layout/Heading";
import PanelLayout, { PanelWidget, PanelWidgetVerticalPadding } from "@library/layout/PanelLayout";
import SmartAlign from "@library/layout/SmartAlign";
import Breadcrumbs, { ICrumb } from "@library/navigation/Breadcrumbs";
import { t } from "@library/utility/appUtils";
import * as React from "react";
import { RouteComponentProps, withRouter } from "react-router";
import { mobileDropDownClasses } from "@library/headers/pieces/mobileDropDownStyles";

interface IProps extends IDeviceProps, RouteComponentProps<{}> {
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
export class RevisionsLayout extends React.Component<IProps> {
    public render() {
        const { device, mobileDropDownTitle, bodyHeading, bodyContent, crumbs } = this.props;
        const isDesktop = device === Devices.DESKTOP || device === Devices.NO_BLEED;
        const isMobile = device === Devices.MOBILE || device === Devices.XS;
        const mobileTitle = mobileDropDownTitle ? mobileDropDownTitle : t("History");

        const classesMobileDropdown = mobileDropDownClasses();

        let mobileDropDownContent: React.ReactNode = (
            <>
                <Heading depth={3} className={classesMobileDropdown.subTitle}>
                    <SmartAlign>{t("Revisions")}</SmartAlign>
                </Heading>
                {this.props.revisionList}
            </>
        );
        if (this.props.draftList !== null) {
            mobileDropDownContent = (
                <>
                    <Heading depth={3} className={classesMobileDropdown.subTitle}>
                        <SmartAlign>{t("Drafts")}</SmartAlign>
                    </Heading>
                    {this.props.draftList}
                    {mobileDropDownContent}
                </>
            );
        }

        return (
            <>
                <EditorHeader
                    canSubmit={this.props.canSubmit}
                    isSubmitLoading={false}
                    callToAction={t("Restore")}
                    mobileDropDownTitle={mobileTitle}
                    mobileDropDownContent={mobileDropDownContent}
                />
                <Container className="richEditorRevisionsForm-body">
                    <PanelLayout
                        topPadding={this.props.device !== Devices.MOBILE && this.props.device !== Devices.XS}
                        breadcrumbs={
                            this.props.device !== Devices.MOBILE &&
                            this.props.device !== Devices.XS && <Breadcrumbs forceDisplay={false}>{crumbs}</Breadcrumbs>
                        }
                        leftTop={isDesktop && <></>}
                        middleTop={<PanelWidget>{bodyHeading}</PanelWidget>}
                        middleBottom={<PanelWidget>{bodyContent}</PanelWidget>}
                        rightTop={!isMobile && this.props.draftList}
                        rightBottom={!isMobile && this.props.revisionList}
                    />
                </Container>
            </>
        );
    }
}

export default withRouter(withDevice(RevisionsLayout));
