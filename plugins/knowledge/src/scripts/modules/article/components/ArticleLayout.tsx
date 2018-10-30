/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import SiteNav from "@library/components/siteNav/SiteNav";
import { Devices } from "@library/components/DeviceChecker";
import { IArticle, ArticleStatus } from "@knowledge/@types/api";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import ArticleTOC from "@knowledge/modules/article/components/ArticleTOC";
import RelatedArticles, { IInternalLink } from "@knowledge/modules/article/components/RelatedArticles";
import ArticleMenu from "@knowledge/modules/article/ArticleMenu";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import Breadcrumbs, { ICrumb } from "@library/components/Breadcrumbs";
import PageTitle from "@knowledge/modules/common/PageTitle";
import UserContent from "@library/components/UserContent";
import OtherLanguages from "@knowledge/modules/article/components/OtherLanguages";
import { dummyOtherLanguagesData } from "../../categories/state/dummyOtherLanguages";
import { dummyNavData } from "../../categories/state/dummyNavData";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";

interface IProps {
    article: IArticle;
    device: Devices;
    breadcrumbData: ICrumb[];
    messages?: React.ReactNode;
}

interface IState {}

/**
 * Implements the article's layout
 */
export class ArticleLayout extends React.Component<IProps, IState> {
    public render() {
        const { article, messages } = this.props;

        return (
            <Container>
                <PanelLayout device={this.props.device}>
                    {this.props.breadcrumbData.length > 1 && (
                        <PanelLayout.Breadcrumbs>
                            <PanelWidget>
                                <Breadcrumbs>{this.props.breadcrumbData}</Breadcrumbs>
                            </PanelWidget>
                        </PanelLayout.Breadcrumbs>
                    )}
                    <PanelLayout.LeftBottom>
                        <SiteNav>{dummyNavData}</SiteNav>
                    </PanelLayout.LeftBottom>
                    <PanelLayout.MiddleTop>
                        <PanelWidget>
                            <PageTitle
                                title={article.name}
                                actions={<ArticleMenu article={article} buttonClassName="pageTitle-menu" />}
                                meta={
                                    <ArticleMeta
                                        updateUser={article.updateUser!}
                                        dateUpdated={article.dateUpdated}
                                        permaLink={article.url}
                                    />
                                }
                            />
                            {messages && <div className="messages">{messages}</div>}
                        </PanelWidget>
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <PanelWidget>
                            <UserContent content={article.body} />
                        </PanelWidget>
                    </PanelLayout.MiddleBottom>
                    <PanelLayout.RightTop>
                        <ArticleTOC items={article.outline} />
                    </PanelLayout.RightTop>
                    <PanelLayout.RightBottom>
                        <OtherLanguages selectedKey={dummyOtherLanguagesData.selected}>
                            {dummyOtherLanguagesData.children as any}
                        </OtherLanguages>
                        <RelatedArticles children={this.articleRelatedArticles} />
                    </PanelLayout.RightBottom>
                </PanelLayout>
            </Container>
        );
    }

    private articleRelatedArticles: IInternalLink[] = [
        {
            name: "Overview",
            to: "#overview",
        },
        {
            name: "Changing Themes",
            to: "#changing-themes",
        },
        {
            name: "Configuration Guide",
            to: "#configuration-guide",
        },
        {
            name: "Theming Guide for Designers",
            to: "#theming-guide-for-designers",
        },
    ];
}

export default withDevice<IProps>(ArticleLayout);
