/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISearchResponseBody } from "@knowledge/@types/api/search";
import SearchPageActions from "@knowledge/modules/search/SearchPageActions";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import { t } from "@library/utility/appUtils";
import produce from "immer";
import { useSelector } from "react-redux";
import { reducerWithInitialState } from "typescript-fsa-reducers";

export enum SearchDomain {
    ARTICLES = "articles",
    EVERYWHERE = "everywhere",
}

export enum SearchPeriod {
    ONE_DAY = "1 day",
    THREE_DAYS = "3 days",
    ONE_WEEK = "1 week",
    TWO_WEEKS = "2 weeks",
    ONE_MONTH = "1 month",
    SIX_MONTHS = "6 months",
    ONE_YEAR = "1 year",
}

export interface ISearchFormState {
    query: string;
    title: string;
    domain: SearchDomain;
    authors: IComboBoxOption[];
    communityCategory: IComboBoxOption | undefined;
    fileName: string;
    startDate: string | undefined;
    endDate: string | undefined;
    includeDeleted: boolean;
    kb: IComboBoxOption | undefined;
    page: number;
    siteSectionGroup: string | null;
    knowledgeBaseID: string | undefined;
}

export interface ISearchPageState {
    form: ISearchFormState;
    results: ILoadable<ISearchResponseBody>;
    pages: ILinkPages;
}

export const INITIAL_SEARCH_FORM: ISearchFormState = {
    query: "",
    title: "",
    domain: SearchDomain.ARTICLES,
    authors: [],
    fileName: "",
    startDate: undefined,
    endDate: undefined,
    includeDeleted: false,
    kb: undefined,
    communityCategory: undefined,
    page: 1,
    siteSectionGroup: null,
    knowledgeBaseID: undefined,
};

export const INITIAL_SEARCH_STATE: ISearchPageState = {
    results: {
        status: LoadStatus.PENDING,
    },
    form: INITIAL_SEARCH_FORM,
    pages: {},
};

export const searchPageReducer = produce(
    reducerWithInitialState<ISearchPageState>(INITIAL_SEARCH_STATE)
        .case(SearchPageActions.updateFormAC, (nextState, payload) => {
            nextState.form = {
                ...nextState.form,
                ...payload,
            };
            return nextState;
        })
        .case(SearchPageActions.getSearchACs.started, (nextState, payload) => {
            nextState.results.status = LoadStatus.LOADING;
            if (payload.page != null) {
                nextState.form.page = payload.page;
            }
            return nextState;
        })
        .case(SearchPageActions.getSearchACs.done, (nextState, payload) => {
            nextState.results.status = LoadStatus.SUCCESS;
            nextState.results.data = payload.result.body;
            nextState.pages = payload.result.pagination;

            return nextState;
        })
        .case(SearchPageActions.getSearchACs.failed, (nextState, payload) => {
            nextState.results.status = LoadStatus.ERROR;
            nextState.results.error = payload.error;

            return nextState;
        })
        .case(SearchPageActions.resetAC, () => {
            return INITIAL_SEARCH_STATE;
        }),
);

export function useSearchPageData() {
    return useSelector((state: IKnowledgeAppStoreState) => state.knowledge.searchPage);
}
