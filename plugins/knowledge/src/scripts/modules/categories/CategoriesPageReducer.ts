/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticleFragment, IKbCategory } from "@knowledge/@types/api";
import CategoriesPageActions from "@knowledge/modules/categories/CategoriesPageActions";
import { ILoadable, LoadStatus } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import produce from "immer";
import { KnowledgeReducer } from "@knowledge/state/model";
import SimplePagerModel, { ILinkPages } from "@library/simplePager/SimplePagerModel";

export interface ICategoriesPageState {
    articles: ILoadable<IArticleFragment[]>;
    category: IKbCategory | null;
    pages: ILinkPages;
}

type ReducerType = KnowledgeReducer<ICategoriesPageState>;

/**
 * Reducer for the categories page.
 */
export default class CategoriesPageReducer extends ReduxReducer<ICategoriesPageState> {
    public initialState: ICategoriesPageState = {
        articles: {
            status: LoadStatus.PENDING,
        },
        category: null,
        pages: {},
    };

    public reducer: ReducerType = (state = this.initialState, action) => {
        return produce(state, draft => {
            switch (action.type) {
                case CategoriesPageActions.GET_ARTICLES_REQUEST:
                    draft.articles.status = LoadStatus.LOADING;
                    break;
                case CategoriesPageActions.GET_ARTICLES_RESPONSE:
                    draft.articles.status = LoadStatus.SUCCESS;
                    draft.articles.data = action.payload.data;
                    if (action.payload.headers && action.payload.headers.link) {
                        draft.pages = SimplePagerModel.parseLinkHeader(action.payload.headers.link, "page", "limit");
                    } else {
                        draft.pages = {};
                    }
                    break;
                case CategoriesPageActions.GET_ARTICLES_ERROR:
                    draft.articles.status = LoadStatus.ERROR;
                    draft.articles.error = action.payload;
                    break;
                case CategoriesPageActions.RESET:
                    return this.initialState;
            }
        });
    };
}
