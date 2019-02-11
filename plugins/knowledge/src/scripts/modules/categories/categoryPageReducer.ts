/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticleFragment } from "@knowledge/@types/api";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import CategoriesPageActions from "@knowledge/modules/categories/CategoriesPageActions";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import { KNOWLEDGE_ACTION } from "@knowledge/state/model";
import { ILoadable, LoadStatus } from "@library/@types/api";
import SimplePagerModel, { ILinkPages } from "@library/simplePager/SimplePagerModel";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";

export interface ICategoriesPageState {
    articles: ILoadable<IArticleFragment[]>;
    categoryID: number | null;
    categoryLoadStatus: ILoadable<never>;
    pages: ILinkPages;
}

const INITIAL_STATE: ICategoriesPageState = {
    articles: {
        status: LoadStatus.PENDING,
    },
    categoryID: null,
    categoryLoadStatus: {
        status: LoadStatus.PENDING,
    },
    pages: {},
};

/**
 * Reducer for the categories page.
 */

export const categoryPageReducer = produce(
    reducerWithInitialState(INITIAL_STATE)
        .case(CategoriesPageActions.resetAction, () => {
            return INITIAL_STATE;
        })
        // The active category is the only one we care about in this page reducer.
        .case(CategoriesPageActions.setCategoryIDAction, (nextState, categoryID) => {
            nextState.categoryID = categoryID;
            return nextState;
        })
        // Tracking the load status of our category.
        .case(CategoryActions.getCategoryACs.started, (nextState, payload) => {
            if (payload.id !== null && payload.id === nextState.categoryID) {
                nextState.categoryLoadStatus.status = LoadStatus.LOADING;
            }
            return nextState;
        })
        .case(CategoryActions.getCategoryACs.done, (nextState, payload) => {
            if (payload.params.id !== null && payload.params.id === nextState.categoryID) {
                nextState.categoryLoadStatus.status = LoadStatus.SUCCESS;
            }
            return nextState;
        })
        .case(CategoryActions.getCategoryACs.failed, (nextState, payload) => {
            if (payload.params.id !== null && payload.params.id === nextState.categoryID) {
                nextState.categoryLoadStatus.status = LoadStatus.ERROR;
                nextState.categoryLoadStatus.error = payload.error;
            }
            return nextState;
        })
        .default((nextState, action: KNOWLEDGE_ACTION) => {
            // Handle non-FSA actions.
            switch (action.type) {
                case ArticleActions.GET_ARTICLES_REQUEST:
                    nextState.articles.status = LoadStatus.LOADING;
                    break;
                case ArticleActions.GET_ARTICLES_RESPONSE:
                    nextState.articles.status = LoadStatus.SUCCESS;
                    nextState.articles.data = action.payload.data;
                    if (action.payload.headers && action.payload.headers.link) {
                        nextState.pages = SimplePagerModel.parseLinkHeader(
                            action.payload.headers.link,
                            "page",
                            "limit",
                        );
                    } else {
                        nextState.pages = {};
                    }
                    break;
                case ArticleActions.GET_ARTICLES_ERROR:
                    nextState.articles.status = LoadStatus.ERROR;
                    nextState.articles.error = action.payload;
                    break;
            }
            return nextState;
        }),
);
