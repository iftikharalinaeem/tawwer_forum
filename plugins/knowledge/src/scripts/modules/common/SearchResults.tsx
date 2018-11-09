/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import SearchResult, { IResult } from "./SearchResult";
import Paragraph from "@library/components/Paragraph";
import Translate from "@library/components/translation/Translate";
import { ISearchResult } from "@knowledge/@types/api";
import { ILoadable, LoadStatus } from "@library/@types/api";
import FullPageLoader from "@library/components/FullPageLoader";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";

interface IProps {
    searchTerm?: string;
    results: ILoadable<ISearchResult[]>;
}

/**
 * Generates a single search result. Note that this template is used in other contexts, such as the flat category list
 */
export default class SearchResults extends React.Component<IProps> {
    public render(): React.ReactNode {
        const { results } = this.props;
        if (results.status === LoadStatus.PENDING) {
            return null;
        }

        if (results.status === LoadStatus.ERROR) {
            return (
                <Paragraph className="searchResults-noResults">
                    <Translate source="There was an error searching for '<0/>'." c0={this.props.searchTerm} />
                </Paragraph>
            );
        }

        if (results.status === LoadStatus.LOADING) {
            return <FullPageLoader />;
        }

        if (results.status === LoadStatus.SUCCESS && results.data) {
            return this.renderSuccess(results.data);
        }
    }

    private renderSuccess(data: ISearchResult[]): React.ReactNode {
        const { searchTerm } = this.props;
        const hasResults = data && data.length > 0;
        let content;

        if (hasResults) {
            content = data.map((result, i) => {
                return (
                    <SearchResult
                        name={result.name}
                        excerpt={result.body}
                        meta={
                            <ArticleMeta
                                updateUser={result.updateUser!}
                                dateUpdated={result.dateUpdated}
                                permaLink={result.url}
                            />
                        }
                        url={result.url}
                        location={result.knowledgeCategory!.breadcrumbs}
                        key={i}
                    />
                );
            });
        } else if (searchTerm === undefined || searchTerm === "") {
            content = <Paragraph className="searchResults-noResults">{t("No results.")}</Paragraph>;
        } else {
            content = (
                <Paragraph className="searchResults-noResults isEmpty">
                    <Translate source="No results for '<0/>'." c0={searchTerm} />
                </Paragraph>
            );
        }

        const Tag = hasResults ? `ul` : `div`;

        return <Tag className={classNames("searchResults")}>{content}</Tag>;
    }
}
