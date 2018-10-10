/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { IArticle } from "@knowledge/@types/api";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { Devices } from "@library/components/DeviceChecker";
import {
    ArticleBreadcrumbs,
    ArticleActions,
    ArticleNavigation,
    ArticleTitle,
    ArticleContent,
    ArticleTOC,
    RelatedArticles,
    ArticleMenu,
} from "@knowledge/modules/article/components";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { IPageHeading } from "@knowledge/modules/article/components/ArticleTOC";
import { IInternalLink } from "@knowledge/modules/article/components/ArticleRelatedArticles";
import { InlineTypes } from "@library/components/Sentence";
import { dummyNavData } from "../../categories/state/dummyNavData";
import SiteNav from "@library/components/siteNav/SiteNav";

interface IProps {
    article: IArticle;
    device: Devices;
}

interface IState {}

export class ArticleLayout extends React.Component<IProps, IState> {
    public render() {
        const { article } = this.props;

        const articleTOC: IPageHeading[] = [
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

        const articleRelatedArticles: IInternalLink[] = [
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

        const metaData = {
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

        return (
            <Container>
                <PanelLayout device={this.props.device}>
                    <PanelLayout.Breadcrumbs>
                        <ArticleBreadcrumbs />
                    </PanelLayout.Breadcrumbs>
                    <PanelLayout.LeftBottom>
                        <PanelWidget>
                            <SiteNav {...dummyNavData} />
                        </PanelWidget>
                    </PanelLayout.LeftBottom>
                    <PanelLayout.MiddleTop>
                        <ArticleTitle
                            article={article}
                            menu={<ArticleMenu article={article} />}
                            meta={metaData as any}
                        />
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <ArticleContent article={article} />
                    </PanelLayout.MiddleBottom>
                    <PanelLayout.RightTop>
                        <ArticleTOC children={articleTOC} />
                    </PanelLayout.RightTop>
                    <PanelLayout.RightBottom>
                        <RelatedArticles children={articleRelatedArticles} />
                    </PanelLayout.RightBottom>
                </PanelLayout>
            </Container>
        );
    }
}

export default withDevice<IProps>(ArticleLayout);
