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
import { IPageHeading } from "@knowledge/modules/article/components/ArticleTOC";
import { InlineTypes } from "@library/components/Sentence";
import Breadcrumbs, { ICrumb } from "@library/components/Breadcrumbs";
import PageTitle from "@knowledge/modules/common/PageTitle";
import UserContent from "@library/components/UserContent";
import OtherLanguages from "@knowledge/modules/article/components/OtherLanguages";
import { dummyOtherLanguagesData } from "../../categories/state/dummyOtherLanguages";
import { dummyNavData } from "../../categories/state/dummyNavData";
import ArticleActionsPanel from "@knowledge/modules/article/components/ArticleActionsPanel";
import ArticleDeletedNotice from "@knowledge/modules/article/components/ArticleDeletedNotice";

interface IProps {
    article: IArticle;
    device: Devices;
    breadcrumbData: ICrumb[];
}

interface IState {}

/**
 * Implements the article's layout
 */
export class ArticleLayout extends React.Component<IProps, IState> {
    public render() {
        const { article } = this.props;

        return (
            <Container>
                <PanelLayout device={this.props.device}>
                    <PanelLayout.Breadcrumbs>
                        <PanelWidget>
                            <Breadcrumbs>{this.props.breadcrumbData}</Breadcrumbs>
                        </PanelWidget>
                    </PanelLayout.Breadcrumbs>
                    <PanelLayout.LeftTop>
                        <ArticleActionsPanel />
                    </PanelLayout.LeftTop>
                    <PanelLayout.LeftBottom>
                        <SiteNav>{dummyNavData}</SiteNav>
                    </PanelLayout.LeftBottom>
                    <PanelLayout.MiddleTop>
                        <PageTitle
                            title={article.articleRevision.name}
                            menu={<ArticleMenu article={article} buttonClassName="pageTitle-menu" />}
                            meta={this.metaData as any}
                        />
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <PanelWidget>
                            {article.status !== ArticleStatus.PUBLISHED && <ArticleDeletedNotice />}
                            <UserContent content={article.articleRevision.bodyRendered} />
                        </PanelWidget>
                    </PanelLayout.MiddleBottom>
                    <PanelLayout.RightTop>
                        <ArticleTOC children={this.articleTOC} />
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

export default withDevice<IProps>(ArticleLayout);
