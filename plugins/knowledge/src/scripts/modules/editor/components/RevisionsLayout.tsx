/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
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
                    <PanelLayout
                        device={this.props.device}
                        topPadding={this.props.device !== Devices.MOBILE}
                        breadcrumbs={
                            this.props.device !== Devices.MOBILE && (
                                <PanelWidget>
                                    <Breadcrumbs children={crumbs} />
                                </PanelWidget>
                            )
                        }
                        leftTop={isDesktop && <></>}
                        middleTop={<PanelWidget>{bodyHeading}</PanelWidget>}
                        middleBottom={<PanelWidget>{bodyContent}</PanelWidget>}
                        rightTop={
                            !isMobile && (
                                <PanelWidgetVerticalPadding>{this.props.revisionList}</PanelWidgetVerticalPadding>
                            )
                        }
                    />
                </Container>
            </>
        );
    }
}

export default withRouter(withDevice<IProps>(ArticleRevisionsLayout));
