/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ISearchResult } from "@knowledge/@types/api/search";
import { LoadStatus } from "@library/@types/api/core";
import { hashString } from "@vanilla/utils";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import ResultList from "@library/result/ResultList";
import SearchPagination from "@knowledge/modules/search/components/SearchPagination";
import { IResult } from "@library/result/Result";
import { ResultMeta } from "@library/result/ResultMeta";
import Loader from "@library/loaders/Loader";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";
import KbErrorMessages from "@knowledge/modules/common/KbErrorMessages";
import { useSearchPageData } from "@knowledge/modules/search/unifySearchPageReducer";
import { useUnifySearchPageActions } from "@knowledge/modules/search/UnifySearchPageActions";
import { IUnifySearchResponseBody } from "@knowledge/@types/api/unifySearch";

interface IProps {}

export function SearchFormResults(props: IProps) {
    const { results, pages, form } = useSearchPageData();
    const { unifySearch, updateForm } = useUnifySearchPageActions();

    switch (results.status) {
        case LoadStatus.PENDING:
        case LoadStatus.LOADING:
            return <Loader />;
        case LoadStatus.ERROR:
            return <KbErrorMessages apiError={results.error} />;
        case LoadStatus.SUCCESS:
            const { next, prev } = pages;
            let paginationNextClick: React.MouseEventHandler | undefined;
            let paginationPreviousClick: React.MouseEventHandler | undefined;

            if (next) {
                paginationNextClick = e => {
                    updateForm({ page: next });
                    void unifySearch();
                };
            }
            if (prev) {
                paginationPreviousClick = e => {
                    updateForm({ page: prev });
                    void unifySearch();
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
function mapResult(searchResult: IUnifySearchResponseBody): IResult {
    const crumbs = searchResult.breadcrumbs || [];
    return {
        name: searchResult.name,
        excerpt: searchResult.body,
        meta: (
            <ResultMeta
                status={searchResult.status}
                type={searchResult.recordType}
                updateUser={searchResult.insertUser!}
                dateUpdated={searchResult.dateInserted}
                crumbs={crumbs}
            />
        ),
        url: searchResult.url,
        location: crumbs,
    };
}
