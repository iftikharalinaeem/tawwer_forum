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
import ArticleMenu from "@knowledge/modules/article/ArticleMenu";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { IPageHeading } from "@knowledge/modules/article/components/ArticleTOC";
import { InlineTypes } from "@library/components/Sentence";
import PageTitle from "@knowledge/modules/common/PageTitle";
import UserContent from "@library/components/UserContent";
import Modal from "@library/components/modal/Modal";
import ModalSizes from "@library/components/modal/ModalSizes";
import { RouteComponentProps } from "react-router";
import EditorHeader from "../../editor/components/EditorHeader";
import { t } from "@library/application";
import RevisionHistory, { IArticleRevisionWithUrl } from "./RevisionHistory";

interface IOwnProps
    extends RouteComponentProps<{
            id?: number;
        }> {}

interface IProps extends IDeviceProps, IOwnProps {
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
        const { article, messages, device } = this.props;
        const isFullWidth = device === (Devices.DESKTOP || Devices.NO_BLEED); // This compoment doesn't care about the no bleed, it's the same as desktop

        const revisions = <RevisionHistory>{this.props.revisionHistory}</RevisionHistory>;

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
                                <PageTitle
                                    title={article.articleRevision.name}
                                    menu={<ArticleMenu article={article} buttonClassName="pageTitle-menu" />}
                                    meta={this.metaData as any}
                                />
                                {messages && <div className="messages">{messages}</div>}
                            </PanelLayout.MiddleTop>
                            <PanelLayout.MiddleBottom>
                                <PanelWidget>
                                    <UserContent content={article.articleRevision.bodyRendered} />
                                </PanelWidget>
                            </PanelLayout.MiddleBottom>
                            <PanelLayout.RightTop />
                        </PanelLayout>
                    </Container>
                </form>
            </Modal>
        );
    }

    private articleTOC: IPageHeading[] = [
        {
            name: "Overview",
            anchor: "#overview",
        },
        {
            name: "Changing Themes",
            anchor: "#changing-themes",
        },
        {
            name: "Configuration Guide",
            anchor: "#configuration-guide",
        },
        {
            name: "Theming Guide for Designers",
            anchor: "#theming-guide-for-designers",
        },
    ];

    private get backLink(): string | null {
        const { state } = this.props.location;
        return state && state.lastLocation ? state.lastLocation.pathname : "/kb";
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
        if (this.backLink) {
            this.props.history.goBack();
        } else {
            this.props.history.push("/kb");
        }
    };

    /**
     * Form submit handler. Fetch the values out of the form and pass them to the callback prop.
     */
    private onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        this.props.submitHandler(this.state.revisionID, this.state.title);
    };

    private metaData = {
        children: [
            {
                children: "By Todd Burry",
                type: InlineTypes.TEXT,
            },
            {
                children: [
                    {
                        children: "Last Updated:" + String.fromCharCode(160),
                        type: InlineTypes.TEXT,
                    },
                    {
                        timeStamp: "2018-03-03",
                        type: InlineTypes.DATETIME,
                        children: [
                            {
                                children: "3 March 2018",
                                type: InlineTypes.TEXT,
                            },
                        ],
                    },
                ],
            },
            {
                children: "ID #1029384756",
                type: InlineTypes.TEXT,
            },
        ],
    };
}

export default withDevice<IProps>(ArticleRevisionsLayout);
