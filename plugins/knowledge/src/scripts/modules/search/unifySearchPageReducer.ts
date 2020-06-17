/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import produce from "immer";
import { useSelector } from "react-redux";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import UnifySearchPageActions from "@knowledge/modules/search/UnifySearchPageActions";
import { IUnifySearchResponseBody } from "@knowledge/@types/api/unifySearch";

export enum UnifySearchDomain {
    DISCUSSIONS = "discussions",
    ARTICLES = "articles",
    CATEGORIES_AND_GROUPS = "categories_and_groups",
    EVERYWHERE = "everywhere",
}

export interface IUnifySearchFormState {
    // These fields belong to all
    query?: string;
    title?: string;
    authors?: IComboBoxOption[];
    startDate?: string;
    endDate?: string;
    includeDeleted?: boolean;
    page?: number;

    // These fields belong to discussions
    categoryID?: number;
    tags?: string[];
    followedCategories?: boolean;
    includeChildCategories?: boolean;
    includeArchivedCategories?: boolean;

    // These fields belong to knowledge base
    knowledgeBaseID?: number;
}

export interface IUnifySearchPageState {
    form: IUnifySearchFormState;
    results: ILoadable<IUnifySearchResponseBody>;
    pages: ILinkPages;
}

export const INITIAL_SEARCH_FORM: IUnifySearchFormState = {
    query: "",
    title: undefined,
    authors: [],
    startDate: undefined,
    endDate: undefined,
    includeDeleted: true,
    page: 1,
};

export const INITIAL_SEARCH_STATE: IUnifySearchPageState = {
    results: {
        status: LoadStatus.PENDING,
    },
    form: INITIAL_SEARCH_FORM,
    pages: {},
};

export const unifySearchPageReducer = produce(
    reducerWithInitialState<IUnifySearchPageState>(INITIAL_SEARCH_STATE)
        .case(UnifySearchPageActions.updateUnifyFormAC, (nextState, payload) => {
            nextState.form = {
                ...nextState.form,
                ...payload,
            };
            return nextState;
        })
        .case(UnifySearchPageActions.setUnifyFormAC, (nextState, payload) => {
            nextState.form = { ...payload };
            return nextState;
        })
        .case(UnifySearchPageActions.getUnifySearchACs.started, (nextState, payload) => {
            nextState.results.status = LoadStatus.LOADING;

            if (payload.page !== null) {
                nextState.form.page = payload.page;
            }
            return nextState;
        })
        .case(UnifySearchPageActions.getUnifySearchACs.done, (nextState, payload) => {
            nextState.results.status = LoadStatus.SUCCESS;
            nextState.results.data = payload.result.body;
            nextState.pages = payload.result.pagination;

            return nextState;
        })
        .case(UnifySearchPageActions.getUnifySearchACs.failed, (nextState, payload) => {
            nextState.results.status = LoadStatus.ERROR;
            nextState.results.error = payload.error;

            return nextState;
        })
        .case(UnifySearchPageActions.resetAC, () => {
            return INITIAL_SEARCH_STATE;
        }),
);

export function useSearchPageData() {
    return useSelector((state: IKnowledgeAppStoreState) => state.knowledge.unifySearchPage);
}
