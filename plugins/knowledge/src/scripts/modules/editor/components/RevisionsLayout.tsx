/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import Container from "@library/components/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@library/components/layouts/PanelLayout";
import { withDevice } from "@library/contexts/DeviceContext";
import EditorHeader from "@knowledge/modules/editor/components/EditorHeader";
import { t } from "@library/application";
import { RouteComponentProps, withRouter } from "react-router";
import Breadcrumbs, { ICrumb } from "@library/components/Breadcrumbs";
import { PanelWidgetVerticalPadding } from "@library/components/layouts/PanelLayout";

interface IProps extends IDeviceProps, RouteComponentProps<{}> {
    bodyHeading: React.ReactNode;
    bodyContent: React.ReactNode;
    revisionList: React.ReactNode;
    canSubmit: boolean;
    crumbs: ICrumb[];
    mobileDropDownTitle?: string;
}

/**
 * Implements the article's layout
 */
export class ArticleRevisionsLayout extends React.Component<IProps> {
    public render() {
        const { device, mobileDropDownTitle, bodyHeading, bodyContent, crumbs } = this.props;
        const isDesktop = device === Devices.DESKTOP;
        const isMobile = device === Devices.MOBILE;
        const mobileTitle = mobileDropDownTitle ? mobileDropDownTitle : t("Revisions");

        return (
            <>
                <EditorHeader
                    canSubmit={this.props.canSubmit}
                    isSubmitLoading={false}
                    className="richEditorRevisionsForm-header"
                    callToAction={t("Restore")}
                    mobileDropDownTitle={mobileTitle}
                    mobileDropDownContent={this.props.revisionList}
                />
                <Container className="richEditorRevisionsForm-body">
                    <PanelLayout device={this.props.device} topPadding={true}>
                        {this.props.device !== Devices.MOBILE && (
                            <PanelLayout.Breadcrumbs>
                                <PanelWidget>
                                    <Breadcrumbs children={crumbs} />
                                </PanelWidget>
                            </PanelLayout.Breadcrumbs>
                        )}
                        {isDesktop && <PanelLayout.LeftTop />}
                        <PanelLayout.MiddleTop>
                            <PanelWidget>{bodyHeading}</PanelWidget>
                        </PanelLayout.MiddleTop>
                        <PanelLayout.MiddleBottom>
                            <PanelWidget>{bodyContent}</PanelWidget>
                        </PanelLayout.MiddleBottom>
                        {!isMobile && (
                            <PanelLayout.RightTop>
                                <PanelWidgetVerticalPadding>{this.props.revisionList}</PanelWidgetVerticalPadding>
                            </PanelLayout.RightTop>
                        )}
                    </PanelLayout>
                </Container>
            </>
        );
    }
}

export default withRouter(withDevice<IProps>(ArticleRevisionsLayout));
