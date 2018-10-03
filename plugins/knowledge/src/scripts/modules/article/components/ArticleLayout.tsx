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
import { t } from "@library/application";
import {
    ArticleBreadcrumbs,
    ArticleActions,
    ArticleNavigation,
    ArticleTitle,
    ArticleContent,
    ArticleTOC,
    RelatedArticles,
} from "@knowledge/modules/article/components";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { IPageHeading } from "@knowledge/modules/article/components/ArticleTOC";
import { IInternalLink } from "@knowledge/modules/article/components/ArticleRelatedArticles";
import { InlineTypes, ISentence } from "@library/components/Sentence";

interface IProps {
    article: IArticle;
    device: Devices;
    menu: JSX.Element;
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

        const metaData: ISentence = {
            children: [
                {
                    children: "By Todd Burry",
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
                    <PanelLayout.LeftTop>
                        <ArticleActions />
                    </PanelLayout.LeftTop>
                    <PanelLayout.LeftBottom>
                        <ArticleNavigation />
                    </PanelLayout.LeftBottom>
                    <PanelLayout.MiddleTop>
                        <ArticleTitle article={article} menu={this.props.menu} meta={metaData} />
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
