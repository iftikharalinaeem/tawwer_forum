/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { IRevisionFragment, IRevision } from "@knowledge/@types/api";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import Modal from "@library/components/modal/Modal";
import ModalSizes from "@library/components/modal/ModalSizes";
import EditorHeader from "@knowledge/modules/editor/components/EditorHeader";
import { t } from "@library/application";
import RevisionsList from "@knowledge/modules/editor/components/RevisionsList";
import RevisionsListItem from "./RevisionsListItem";
import { LoadStatus } from "@library/@types/api";
import { makeRevisionsUrl, makeEditUrl } from "../route";
import UserContent from "@library/components/UserContent";
import { IInjectableRevisionsState } from "../RevisionsPageModel";
import PageTitle from "@knowledge/modules/common/PageTitle";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";
import { RouteComponentProps, withRouter } from "react-router";

interface IProps extends IDeviceProps, RouteComponentProps<{}> {
    bodyHeading: React.ReactNode;
    bodyContent: React.ReactNode;
    revisionList: React.ReactNode;
    canSubmit: boolean;
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
                    <PanelLayout device={this.props.device} forceRenderLeftTop={isFullWidth}>
                        <PanelLayout.MiddleTop>{this.props.bodyHeading}</PanelLayout.MiddleTop>
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
