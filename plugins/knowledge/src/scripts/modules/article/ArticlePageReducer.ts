/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticle } from "@knowledge/@types/api";
import { LoadStatus, ILoadable } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";

export type IArticlePageState = ILoadable<{
    article: IArticle;
}>;

/**
 * Reducer for the article page.
 */
export default class ArticlePageReducer extends ReduxReducer<IArticlePageState> {
    public initialState: IArticlePageState = {
        status: LoadStatus.PENDING,
    };

    public reducer = (
        state: IArticlePageState = this.initialState,
        action: typeof ArticlePageActions.ACTION_TYPES,
    ): IArticlePageState => {
        switch (action.type) {
            case ArticlePageActions.GET_ARTICLE_REQUEST:
                return {
                    status: LoadStatus.LOADING,
                };
            case ArticlePageActions.GET_ARTICLE_RESPONSE:
                return {
                    status: LoadStatus.SUCCESS,
                    data: {
                        article: action.payload.data,
                    },
                };
            case ArticlePageActions.GET_ARTICLE_ERROR:
                return {
                    status: LoadStatus.ERROR,
                    error: action.payload,
                };
            case ArticlePageActions.RESET:
                return this.initialState;
            default:
                return state;
        }
    };
}
