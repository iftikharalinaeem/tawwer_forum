/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { Devices } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import Breadcrumbs, { ICrumb } from "@library/components/Breadcrumbs";
import PageTitle from "@knowledge/modules/common/PageTitle";
import CategoryMenu from "@knowledge/modules/categories/components/CategoryMenu";
import SearchResults from "@knowledge/modules/common/SearchResults";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { IArticleFragment, IKbCategoryFragment } from "@knowledge/@types/api";
import { dummyArticles } from "@knowledge/modules/categories/state/dummyArticles";
import { SearchResultMeta } from "@knowledge/modules/common/SearchResultMeta";

interface IProps {
    breadcrumbData: ICrumb[];
    category: IKbCategoryFragment;
    articles: IArticleFragment[];
    device: Devices;
}

export class CategoriesLayout extends React.Component<IProps> {
    public render() {
        const { category } = this.props;

        return (
            <Container>
                <PanelLayout device={this.props.device}>
                    <PanelLayout.Breadcrumbs>
                        <PanelWidget>
                            <Breadcrumbs>{this.props.breadcrumbData}</Breadcrumbs>
                        </PanelWidget>
                    </PanelLayout.Breadcrumbs>
                    <PanelLayout.MiddleTop>
                        <PageTitle backUrl="#Back" title={category.name} menu={<CategoryMenu />} />
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <SearchResults results={this.getSearchResults()} />
                    </PanelLayout.MiddleBottom>
                </PanelLayout>
            </Container>
        );
    }

    private getSearchResults(): IResult[] {
        const { articles } = this.props;
        return articles
            .map(article => {
                return {
                    name: article.name || "",
                    meta: <SearchResultMeta updateUser={article.updateUser} dateUpdated={article.dateUpdated} />,
                    url: article.url,
                    excerpt: article.excerpt || "",
                    attachments: [] as any,
                };
            })
            .concat(dummyArticles as any);
    }
}

export default withDevice<IProps>(CategoriesLayout);
