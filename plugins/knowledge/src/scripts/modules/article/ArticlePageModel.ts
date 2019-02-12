/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticle } from "@knowledge/@types/api";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import { IStoreState, KnowledgeReducer } from "@knowledge/state/model";
import { ILoadable, LoadStatus } from "@library/@types/api";
import { ICrumb } from "@library/components/Breadcrumbs";
import ReduxReducer from "@library/state/ReduxReducer";
import { produce } from "immer";

export interface IArticlePageState {
    articleID: number | null;
    articleLoadable: ILoadable<{}>;
    restoreStatus: LoadStatus;
}

export interface IInjectableArticlePageState {
    loadable: ILoadable<{
        article: IArticle;
    }>;
    restoreStatus: LoadStatus;
}

type ReducerType = KnowledgeReducer<IArticlePageState>;

/**
 * Reducer for the article page.
 */
export default class ArticlePageModel implements ReduxReducer<IArticlePageState> {
    public static getInjectableState(state: IStoreState): IInjectableArticlePageState {
        const { articleID, articleLoadable, restoreStatus } = state.knowledge.articlePage;

        if (articleLoadable.status === LoadStatus.SUCCESS && articleID !== null) {
            const article = ArticleModel.selectArticle(state, articleID)!;
            return {
                restoreStatus,
                loadable: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        article,
                    },
                },
            };
        } else {
            return {
                restoreStatus,
                loadable: articleLoadable as any,
            };
        }
    }

    public initialState: IArticlePageState = {
        articleID: null,
        articleLoadable: {
            status: LoadStatus.PENDING,
        },
        restoreStatus: LoadStatus.PENDING,
    };

    public reducer: ReducerType = (state = this.initialState, action) => {
        return produce(state, nextState => {
            switch (action.type) {
                case ArticlePageActions.INIT:
                    const { preloaded, articleID } = action.payload;
                    nextState.articleID = articleID;
                    if (preloaded) {
                        nextState.articleLoadable.status = LoadStatus.SUCCESS;
                    }
                    break;
                case ArticlePageActions.RESET:
                    return this.initialState;
            }

            if (
                "meta" in action &&
                action.meta &&
                action.meta.articleID &&
                nextState.articleID === action.meta.articleID
            ) {
                switch (action.type) {
                    case ArticleActions.GET_ARTICLE_REQUEST:
                        nextState.articleLoadable.status = LoadStatus.LOADING;
                        break;
                    case ArticleActions.GET_ARTICLE_RESPONSE:
                        nextState.articleLoadable.status = LoadStatus.SUCCESS;
                        break;
                    case ArticleActions.GET_ARTICLE_ERROR:
                        nextState.articleLoadable.status = LoadStatus.ERROR;
                        break;
                    case ArticleActions.PATCH_ARTICLE_STATUS_REQUEST:
                        nextState.restoreStatus = LoadStatus.LOADING;
                        break;
                    case ArticleActions.PATCH_ARTICLE_STATUS_RESPONSE:
                        nextState.restoreStatus = LoadStatus.SUCCESS;
                        break;
                }
            }
        });
    };
}
