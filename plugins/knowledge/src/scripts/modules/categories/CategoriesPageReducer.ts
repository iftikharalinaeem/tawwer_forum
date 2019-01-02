/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticleFragment, IKbCategory } from "@knowledge/@types/api";
import CategoriesPageActions from "@knowledge/modules/categories/CategoriesPageActions";
import { ILoadable, LoadStatus } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import produce from "immer";

export interface ICategoriesPageState {
    articles: ILoadable<IArticleFragment[]>;
    category: IKbCategory | null;
}

/**
 * Reducer for the categories page.
 */
export default class CategoriesPageReducer extends ReduxReducer<ICategoriesPageState> {
    public initialState: ICategoriesPageState = {
        articles: {
            status: LoadStatus.PENDING,
        },
        category: null,
    };

    public reducer = (
        state: ICategoriesPageState = this.initialState,
        action: typeof CategoriesPageActions.ACTION_TYPES,
    ): ICategoriesPageState => {
        return produce(state, draft => {
            switch (action.type) {
                case CategoriesPageActions.GET_ARTICLES_REQUEST:
                    draft.articles.status = LoadStatus.LOADING;
                    break;
                case CategoriesPageActions.GET_ARTICLES_RESPONSE:
                    draft.articles.status = LoadStatus.SUCCESS;
                    draft.articles.data = action.payload.data;
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
