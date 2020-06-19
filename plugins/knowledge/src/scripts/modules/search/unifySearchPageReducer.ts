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
import { IUnifySearchResponseBody, IUnifySearchRequestBody } from "@knowledge/@types/api/unifySearch";

export enum UnifySearchDomain {
    DISCUSSIONS = "discussions",
    ARTICLES = "articles",
    CATEGORIES_AND_GROUPS = "categories_and_groups",
    ALL_CONTENT = "all_content",
}

// Checkout UnifySearchPageActions ALL_FORM, DISCUSSIONS_FORM, ARTICLES_FORM
export interface IUnifySearchFormState {
    // These fields belong to all
    query?: string;
    title?: string;
    authors?: IComboBoxOption[];
    startDate?: string;
    endDate?: string;

    // These fields belong to discussions
    categoryID?: number;
    tags?: string[];
    followedCategories?: boolean;
    includeChildCategories?: boolean;
    includeArchivedCategories?: boolean;

    // These fields belong to knowledge base
    knowledgeBaseID?: number;
    includeDeleted?: boolean;
}

export interface IUnifySearchPageState {
    form: IUnifySearchFormState;
    query: IUnifySearchRequestBody;
    results: ILoadable<IUnifySearchResponseBody>;
    pages: ILinkPages;
}

export const INITIAL_SEARCH_FORM: IUnifySearchFormState = {
    query: "",
};

const INITIAL_SEARCH_QUERY: IUnifySearchRequestBody = {
    query: "",
    recordTypes: UnifySearchPageActions.getRecordType(UnifySearchDomain.ALL_CONTENT),
    page: 1,
};

export const INITIAL_SEARCH_STATE: IUnifySearchPageState = {
    form: INITIAL_SEARCH_FORM,
    query: INITIAL_SEARCH_QUERY,
    results: {
        status: LoadStatus.PENDING,
    },
    pages: {},
};

export const unifySearchPageReducer = produce(
    reducerWithInitialState<IUnifySearchPageState>(INITIAL_SEARCH_STATE)
        .case(UnifySearchPageActions.updateUnifyFormAC, (nextState, payload) => {
            nextState.form = {
                ...nextState.form,
                ...payload.entry,
            };
            nextState.query = {
                ...UnifySearchPageActions.toQuery(nextState.form),
                recordTypes: UnifySearchPageActions.getRecordType(payload.domain),
            };

            return nextState;
        })
        .case(UnifySearchPageActions.setUnifyFormAC, (nextState, payload) => {
            nextState.form = { ...payload.queryForm };
            nextState.query = {
                ...UnifySearchPageActions.toQuery(nextState.form),
                recordTypes: UnifySearchPageActions.getRecordType(payload.domain),
            };

            return nextState;
        })
        .case(UnifySearchPageActions.getUnifySearchACs.started, (nextState, payload) => {
            nextState.results.status = LoadStatus.LOADING;
            nextState.query = payload;

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
