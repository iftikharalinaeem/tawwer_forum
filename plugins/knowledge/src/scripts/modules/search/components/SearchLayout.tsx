/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Container from "@knowledge/layouts/components/Container";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { IArticleFragment, IKbCategoryFragment } from "@knowledge/@types/api";
import { dummyArticles } from "@knowledge/modules/categories/state/dummyArticles";
import { SearchResultMeta } from "@knowledge/modules/common/SearchResultMeta";

interface IProps extends IDeviceProps {
    children: IArticleFragment[];
}

export class SearchLayout extends React.Component<IProps> {
    public render() {
        return (
            <Container>
                {this.getSearchResults()}
                {/*<PanelLayout device={this.props.device}>*/}
                {/*<PanelLayout.Breadcrumbs>*/}
                {/*<PanelWidget>*/}
                {/*<Breadcrumbs>{this.props.breadcrumbData}</Breadcrumbs>*/}
                {/*</PanelWidget>*/}
                {/*</PanelLayout.Breadcrumbs>*/}
                {/*<PanelLayout.MiddleTop>*/}
                {/*<PageTitle backUrl="#Back" title={category.name} menu={<CategoryMenu />} />*/}
                {/*</PanelLayout.MiddleTop>*/}
                {/*<PanelLayout.MiddleBottom>*/}
                {/*<SearchResults results={this.getSearchResults()} />*/}
                {/*</PanelLayout.MiddleBottom>*/}
                {/*</PanelLayout>*/}
            </Container>
        );
    }

    private getSearchResults(): IResult[] {
        const { children } = this.props;
        return children
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

export default withDevice<IProps>(SearchLayout);
