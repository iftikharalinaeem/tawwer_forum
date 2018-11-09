/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/application";
import { IUserFragment, ILoadable, LoadStatus } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import SearchPageActions from "@knowledge/modules/search/SearchPageActions";
import produce from "immer";
import { IStoreState } from "@knowledge/state/model";
import { ISearchResponseBody } from "@knowledge/@types/api";
import { IComboBoxOption } from "@library/components/forms/select/SearchBar";

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
}

export default class SearchPageModel implements ReduxReducer<ISearchPageState> {
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
        form: {
            query: "",
            title: "",
            domain: SearchDomain.ARTICLES,
            authors: [],
            fileName: "",
            startDate: undefined,
            endDate: undefined,
            includeDeleted: false,
            kb: null,
        },
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
                    next.results.status === LoadStatus.LOADING;
                    break;
                case SearchPageActions.GET_SEARCH_RESPONSE:
                    next.results.status = LoadStatus.SUCCESS;
                    next.results.data = action.payload.data;
                    break;
            }
        });
    };
}
