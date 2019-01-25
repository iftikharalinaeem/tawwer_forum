/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { IStoreState, KnowledgeReducer } from "@knowledge/state/model";
import { ILoadable, LoadStatus } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import produce from "immer";

export interface IArticleMenuState {
    delete: ILoadable<{}>;
}

type ReducerType = KnowledgeReducer<IArticleMenuState>;

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

    public reducer: ReducerType = (state = this.initialState, action) => {
        return produce(state, draft => {
            switch (action.type) {
                case ArticleActions.PATCH_ARTICLE_STATUS_REQUEST:
                    draft.delete.status = LoadStatus.LOADING;
                    break;
                case ArticleActions.PATCH_ARTICLE_STATUS_RESPONSE:
                    draft.delete.status = LoadStatus.SUCCESS;
                    break;
                case ArticleActions.PATCH_ARTICLE_STATUS_ERROR:
                    draft.delete.status = LoadStatus.ERROR;
                    draft.delete.error = action.payload;
                    break;
            }
        });
    };
}
