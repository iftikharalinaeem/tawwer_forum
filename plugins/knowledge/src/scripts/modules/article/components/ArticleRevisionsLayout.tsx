/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { IArticle, ArticleStatus } from "@knowledge/@types/api";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { IInternalLink } from "@knowledge/modules/article/components/RelatedArticles";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { IPageHeading } from "@knowledge/modules/article/components/ArticleTOC";
import Modal from "@library/components/modal/Modal";
import ModalSizes from "@library/components/modal/ModalSizes";
import EditorHeader from "../../editor/components/EditorHeader";
import { t } from "@library/application";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";
import { IArticleRevisionWithUrl } from "@knowledge/modules/article/components/RevisionsListItem";
import RevisionsList from "./RevisionsList";

interface IProps extends IDeviceProps {
    article: IArticle;
    backUrl: string | null;
    submitHandler: (revisionID: string, title: string) => void;
    messages?: React.ReactNode;
    isSubmitLoading: boolean;
    revisionHistory: IArticleRevisionWithUrl[];
}

interface IState {
    revisionID: string;
    title: string;
}

/**
 * Implements the article's layout
 */
export class ArticleRevisionsLayout extends React.Component<IProps, IState> {
    public render() {
        const { article, device } = this.props;

        const isFullWidth = device === (Devices.DESKTOP || Devices.NO_BLEED); // This compoment doesn't care about the no bleed, it's the same as desktop

        return (
            <Modal size={ModalSizes.FULL_SCREEN} exitHandler={this.navigateToBacklink} label={t("Article Revisions")}>
                <form className="richEditorForm inheritHeight" onSubmit={this.onSubmit}>
                    <EditorHeader
                        backUrl={this.props.backUrl}
                        device={this.props.device}
                        canSubmit={this.canSubmit}
                        isSubmitLoading={this.props.isSubmitLoading}
                        className="richEditorRevisionsForm-header"
                        callToAction={t("Restore")}
                    />
                    <Container className="richEditorRevisionsForm-body">
                        <PanelLayout device={this.props.device} forceRenderLeftTop={isFullWidth}>
                            <PanelLayout.MiddleTop>
                                {/*<PageTitle*/}
                                {/*title={article}*/}
                                {/*menu={<ArticleMenu article={article} buttonClassName="pageTitle-menu" />}*/}
                                {/*meta={*/}
                                {/*<ArticleMeta*/}
                                {/*updateUser={article.updateUser!}*/}
                                {/*dateUpdated={article.dateUpdated}*/}
                                {/*permaLink={article.url}*/}
                                {/*/>*/}
                                {/*}*/}
                                {/*/>*/}
                            </PanelLayout.MiddleTop>
                            <PanelLayout.MiddleBottom>
                                {article && <PanelWidget>{/*<UserContent content= />*/}</PanelWidget>}
                            </PanelLayout.MiddleBottom>
                            <PanelLayout.RightTop>
                                {this.props.revisionHistory && (
                                    <PanelWidget className="isSelfPadded">
                                        <RevisionsList>{this.props.revisionHistory}</RevisionsList>
                                    </PanelWidget>
                                )}
                            </PanelLayout.RightTop>
                        </PanelLayout>
                    </Container>
                </form>
            </Modal>
        );
    }

    /**
     * Whether or not we have all of the data we need to submit the form.
     */
    private get canSubmit(): boolean {
        return false;
    }

    /**
     * Route back to the previous location if its available.
     */
    private navigateToBacklink = () => {
        return;
    };

    /**
     * Form submit handler. Fetch the values out of the form and pass them to the callback prop.
     */
    private onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        this.props.submitHandler(this.state.revisionID, this.state.title);
    };

    private metaData: IInternalLink[] = [
        {
            name: "Overview",
            to: "/kb",
        },
        {
            name: "Changing Themes",
            to: "/kb",
        },
        {
            name: "Configuration Guide",
            to: "/kb",
        },
        {
            name: "Theming Guide for Designers",
            to: "/kb",
        },
    ];
}

export default withDevice<IProps>(ArticleRevisionsLayout);
