/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import EditorHeader from "@knowledge/modules/editor/components/EditorHeader";
import { t } from "@library/application";
import { RouteComponentProps, withRouter } from "react-router";
import Breadcrumbs, { ICrumb } from "@library/components/Breadcrumbs";

interface IProps extends IDeviceProps, RouteComponentProps<{}> {
    bodyHeading: React.ReactNode;
    bodyContent: React.ReactNode;
    revisionList: React.ReactNode;
    canSubmit: boolean;
    crumbs: ICrumb[];
}

/**
 * Implements the article's layout
 */
export class ArticleRevisionsLayout extends React.Component<IProps> {
    public render() {
        const { device } = this.props;
        const isFullWidth = device === (Devices.DESKTOP || Devices.NO_BLEED); // This compoment doesn't care about the no bleed, it's the same as desktop

        return (
            <>
                <EditorHeader
                    canSubmit={this.props.canSubmit}
                    isSubmitLoading={false}
                    className="richEditorRevisionsForm-header"
                    callToAction={t("Restore")}
                />
                <Container className="richEditorRevisionsForm-body">
                    <PanelLayout device={this.props.device}>
                        <PanelLayout.MiddleTop>
                            <PanelWidget>
                                <Breadcrumbs children={this.props.crumbs} />
                            </PanelWidget>
                            {this.props.bodyHeading}
                        </PanelLayout.MiddleTop>
                        <PanelLayout.MiddleBottom>
                            <PanelWidget>{this.props.bodyContent}</PanelWidget>
                        </PanelLayout.MiddleBottom>
                        <PanelLayout.RightTop>
                            <PanelWidget className="isSelfPadded">{this.props.revisionList}</PanelWidget>
                        </PanelLayout.RightTop>
                    </PanelLayout>
                </Container>
            </>
        );
    }
}

export default withRouter(withDevice<IProps>(ArticleRevisionsLayout));
