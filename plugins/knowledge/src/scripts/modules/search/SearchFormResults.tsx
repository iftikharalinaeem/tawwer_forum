/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { useSearchPageData } from "@knowledge/modules/search/searchPageReducer";
import { ISearchResult } from "@knowledge/@types/api/search";
import { LoadStatus } from "@library/@types/api/core";
import { hashString } from "@vanilla/utils";
import { useSearchPageActions } from "@knowledge/modules/search/SearchPageActions";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import ResultList from "@library/result/ResultList";
import SearchPagination from "@knowledge/modules/search/components/SearchPagination";
import { IResult } from "@library/result/Result";
import { ResultMeta } from "@library/result/ResultMeta";
import Loader from "@library/loaders/Loader";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";

interface IProps {}

export function SearchFormResults(props: IProps) {
    const { results, pages, form } = useSearchPageData();
    const { search } = useSearchPageActions();

    switch (results.status) {
        case LoadStatus.PENDING:
        case LoadStatus.LOADING:
            return <Loader />;
        case LoadStatus.ERROR:
            return <KbErrorPage apiError={results.error} />;
        case LoadStatus.SUCCESS:
            const { next, prev } = pages;
            let paginationNextClick: React.MouseEventHandler | undefined;
            let paginationPreviousClick: React.MouseEventHandler | undefined;

            if (next) {
                paginationNextClick = e => {
                    void search(next);
                };
            }
            if (prev) {
                paginationPreviousClick = e => {
                    void search(prev);
                };
            }
            return (
                <>
                    <AnalyticsData uniqueKey={hashString(form.query + JSON.stringify(pages))} />
                    <ResultList results={results.data!.map(mapResult)} />
                    <SearchPagination onNextClick={paginationNextClick} onPreviousClick={paginationPreviousClick} />
                </>
            );
    }
}

/**
 * Map a search API response into what the <SearchResults /> component is expecting.
 *
 * @param searchResult The API search result to map.
 */
function mapResult(searchResult: ISearchResult): IResult {
    const crumbs = searchResult.breadcrumbs || [];
    return {
        name: searchResult.name,
        excerpt: searchResult.body,
        meta: (
            <ResultMeta
                status={searchResult.status}
                type={searchResult.recordType}
                updateUser={searchResult.updateUser!}
                dateUpdated={searchResult.dateUpdated}
                crumbs={crumbs}
            />
        ),
        url: searchResult.url,
        location: crumbs,
    };
}
