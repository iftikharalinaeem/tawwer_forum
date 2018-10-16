/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticle, ArticleStatus } from "@knowledge/@types/api";
import { LoadStatus, ILoadable } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { produce } from "immer";

export interface IArticlePageState {
    article: ILoadable<IArticle>;
}

/**
 * Reducer for the article page.
 */
export default class ArticlePageReducer extends ReduxReducer<IArticlePageState> {
    public initialState: IArticlePageState = {
        article: {
            status: LoadStatus.PENDING,
        },
    };

    public reducer = (
        state: IArticlePageState = this.initialState,
        action: typeof ArticlePageActions.ACTION_TYPES | typeof ArticleActions.ACTION_TYPES,
    ): IArticlePageState => {
        return produce(state, draft => {
            switch (action.type) {
                case ArticlePageActions.GET_ARTICLE_REQUEST:
                    draft.article.status = LoadStatus.LOADING;
                    break;
                case ArticlePageActions.GET_ARTICLE_RESPONSE:
                    draft.article.status = LoadStatus.SUCCESS;
                    draft.article.data = action.payload.data;
                    break;
                case ArticlePageActions.GET_ARTICLE_ERROR:
                    draft.article.status = LoadStatus.ERROR;
                    draft.article.error = action.payload;
                    break;
                case ArticleActions.DELETE_ARTICLE_RESPONSE:
                    if (
                        state.article.status === LoadStatus.SUCCESS &&
                        draft.article.data &&
                        state.article.data.articleID === action.meta.articleID
                    ) {
                        draft.article.data.status = ArticleStatus.DELETED;
                    }
                    break;
                case ArticleActions.RESTORE_ARTICLE_RESPONSE:
                    if (
                        state.article.status === LoadStatus.SUCCESS &&
                        draft.article.data &&
                        state.article.data.articleID === action.meta.articleID
                    ) {
                        draft.article.data.status = ArticleStatus.PUBLISHED;
                    }
                    break;
                case ArticlePageActions.RESET:
                    return this.initialState;
            }
        });
    };
}
