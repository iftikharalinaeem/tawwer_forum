/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus, ILoadable } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import produce from "immer";
import { IStoreState } from "@knowledge/state/model";

export interface IArticleMenuState {
    delete: ILoadable<{}>;
}

/**
 * Model and reducer for the ArticleMenu
 */
export default class ArticleMenuModel implements ReduxReducer<IArticleMenuState> {
    public static mapStateToProps(storeState: IStoreState): IArticleMenuState {
        return storeState.knowledge.articleMenu;
    }

    public initialState: IArticleMenuState = {
        delete: {
            status: LoadStatus.PENDING,
        },
    };

    public reducer = (
        state: IArticleMenuState = this.initialState,
        action: typeof ArticleActions.ACTION_TYPES,
    ): IArticleMenuState => {
        return produce(state, draft => {
            switch (action.type) {
                case ArticleActions.DELETE_ARTICLE_REQUEST:
                    draft.delete.status = LoadStatus.LOADING;
                    break;
                case ArticleActions.DELETE_ARTICLE_RESPONSE:
                    draft.delete.status = LoadStatus.SUCCESS;
                    break;
                case ArticleActions.DELETE_ARTICLE_ERROR:
                    draft.delete.status = LoadStatus.ERROR;
                    draft.delete.error = action.payload;
                    break;
            }
        });
    };
}
