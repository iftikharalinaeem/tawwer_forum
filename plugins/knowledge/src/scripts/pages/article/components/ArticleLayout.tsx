/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { IArticle } from "@knowledge/@types/api";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { Devices } from "@knowledge/components/DeviceChecker";
import { t } from "@library/application";
import ArticleBreadcrumbs from "@knowledge/pages/article/components/ArticleBreadcrumbs";
import ArticleActions from "@knowledge/pages/article/components/ArticleActions";
import ArticleNavigation from "@knowledge/pages/article/components/ArticleNavigation";
import ArticleTitle from "@knowledge/pages/article/components/ArticleTitle";
import ArticleContent from "@knowledge/pages/article/components/ArticleContent";
import ArticleTOC from "@knowledge/pages/article/components/ArticleTOC";
import RelatedArticles from "@knowledge/pages/article/components/RelatedArticles";
import { withDevice } from "@knowledge/contexts/DeviceContext";

interface IProps {
    article: IArticle;
    device: Devices;
}

interface IState {}

export class ArticleLayout extends React.Component<IProps, IState> {
    public render() {
        const { article } = this.props;
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
                        <ArticleTitle article={article} />
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <ArticleContent article={article} />
                    </PanelLayout.MiddleBottom>
                    <PanelLayout.RightTop>
                        <ArticleTOC />
                    </PanelLayout.RightTop>
                    <PanelLayout.RightBottom>
                        <RelatedArticles />
                    </PanelLayout.RightBottom>
                </PanelLayout>
            </Container>
        );
    }
}

export default withDevice<IProps>(ArticleLayout);
