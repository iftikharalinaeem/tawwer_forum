/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/application";
import { ILoadable, LoadStatus } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import SearchPageActions from "@knowledge/modules/search/SearchPageActions";
import produce from "immer";
import { IStoreState } from "@knowledge/state/model";
import { ISearchResponseBody } from "@knowledge/@types/api";
import { IComboBoxOption } from "@library/components/forms/select/SearchBar";
import SimplePagerModel, { ILinkPages } from "@library/simplePager/SimplePagerModel";

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

export const dateWithin = Object.values(SearchPeriod).map(period => ({
    label: t(period),
    value: period,
}));

export interface ISearchFormState {
    query: string;
    title: string;
    domain: SearchDomain;
    authors: IComboBoxOption[];
    fileName: string;
    startDate: string | undefined;
    endDate: string | undefined;
    includeDeleted: boolean;
    kb?: null;
}

export interface ISearchPageState {
    form: ISearchFormState;
    results: ILoadable<ISearchResponseBody>;
    pages: ILinkPages;
}

export default class SearchPageModel implements ReduxReducer<ISearchPageState> {
    public static readonly INITIAL_FORM = {
        query: "",
        title: "",
        domain: SearchDomain.ARTICLES,
        authors: [],
        fileName: "",
        startDate: undefined,
        endDate: undefined,
        includeDeleted: false,
        kb: null,
    };

    public static mapStateToProps(state: IStoreState): ISearchPageState {
        return SearchPageModel.stateSlice(state);
    }

    public static stateSlice(state: IStoreState) {
        if (!state.knowledge || !state.knowledge.searchPage) {
            throw new Error(`Could not find "knowledge.searchPage" in state ${state}`);
        }

        return state.knowledge.searchPage;
    }

    public readonly initialState: ISearchPageState = {
        results: {
            status: LoadStatus.PENDING,
        },
        form: SearchPageModel.INITIAL_FORM,
        pages: {},
    };

    public reducer = (
        state: ISearchPageState = this.initialState,
        action: typeof SearchPageActions.ACTION_TYPES,
    ): ISearchPageState => {
        return produce(state, next => {
            switch (action.type) {
                case SearchPageActions.UPDATE_FORM:
                    next.form = {
                        ...next.form,
                        ...action.payload.updates,
                    };
                    break;
                case SearchPageActions.GET_SEARCH_REQUEST:
                    next.results.status = LoadStatus.LOADING;
                    break;
                case SearchPageActions.GET_SEARCH_RESPONSE:
                    next.results.status = LoadStatus.SUCCESS;
                    next.results.data = action.payload.data;

                    if (action.payload.headers) {
                        const { link } = action.payload.headers;
                        if (link) {
                            next.pages = SimplePagerModel.parseLinkHeader(link, "page");
                        }
                    }

                    break;
                case SearchPageActions.RESET:
                    return this.initialState;
            }
        });
    };
}
