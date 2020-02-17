/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ArticleActions from "@knowledge/modules/article/ArticleActions";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import { KNOWLEDGE_ACTION } from "@knowledge/state/model";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { produce } from "immer";
import clone from "lodash/clone";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IFeatureArticle } from "@knowledge/@types/api/article";

export interface IArticlePageState {
    articleID: number | null;
    articleLoadable: ILoadable<{}>;
    restoreStatus: LoadStatus;
    reactionLoadable: ILoadable<{
        reaction: "yes" | "no";
    }>;
}

export const ARTICLE_PAGE_INITIAL_STATE: IArticlePageState = {
    articleID: null,
    articleLoadable: {
        status: LoadStatus.PENDING,
    },
    reactionLoadable: {
        status: LoadStatus.PENDING,
    },
    restoreStatus: LoadStatus.PENDING,
};

export const articlePageReducer = produce(
    reducerWithInitialState<IArticlePageState>(clone(ARTICLE_PAGE_INITIAL_STATE))
        .case(ArticleActions.putReactACs.started, (nextState, payload) => {
            if (payload.articleID !== nextState.articleID) {
                return nextState;
            }
            nextState.reactionLoadable.status = LoadStatus.LOADING;
            nextState.reactionLoadable.data = {
                reaction: payload.helpful,
            };
            return nextState;
        })
        .case(ArticleActions.putReactACs.done, (nextState, payload) => {
            if (payload.params.articleID !== nextState.articleID) {
                return nextState;
            }
            nextState.reactionLoadable = ARTICLE_PAGE_INITIAL_STATE.reactionLoadable;
            return nextState;
        })
        .case(ArticleActions.putReactACs.failed, (nextState, payload) => {
            if (payload.params.articleID !== nextState.articleID) {
                return nextState;
            }
            nextState.reactionLoadable.status = LoadStatus.ERROR;
            nextState.reactionLoadable.error = payload.error;
            return nextState;
        })
        .case(ArticleActions.getArticleACs.started, (nextState, payload) => {
            if (nextState.articleID === payload.articleID) {
                nextState.articleLoadable.status = LoadStatus.LOADING;
            }
            return nextState;
        })
        .case(ArticleActions.getArticleACs.done, (nextState, payload) => {
            if (nextState.articleID === payload.params.articleID) {
                nextState.articleLoadable.status = LoadStatus.SUCCESS;
            }
            return nextState;
        })
        .case(ArticleActions.getArticleACs.failed, (nextState, payload) => {
            if (nextState.articleID === payload.params.articleID) {
                nextState.articleLoadable.status = LoadStatus.ERROR;
                nextState.articleLoadable.error = payload.error;
            }
            return nextState;
        })
        .default((nextState, action: KNOWLEDGE_ACTION) => {
            switch (action.type) {
                case ArticlePageActions.INIT:
                    const { preloaded, articleID } = action.payload;
                    nextState.articleID = articleID;
                    if (preloaded) {
                        nextState.articleLoadable.status = LoadStatus.SUCCESS;
                    }
                    break;
                case ArticlePageActions.RESET:
                    return ARTICLE_PAGE_INITIAL_STATE;
                case ArticleActions.PATCH_ARTICLE_STATUS_REQUEST:
                    if (nextState.articleID === action.meta.articleID) {
                        nextState.restoreStatus = LoadStatus.LOADING;
                    }
                    break;
                case ArticleActions.PATCH_ARTICLE_STATUS_RESPONSE:
                    if (nextState.articleID === action.meta.articleID) {
                        nextState.restoreStatus = LoadStatus.SUCCESS;
                    }
                    break;
                case ArticleActions.PATCH_ARTICLE_STATUS_ERROR:
                    if (nextState.articleID === action.meta.articleID) {
                        nextState.restoreStatus = LoadStatus.ERROR;
                    }
                    break;
            }

            return nextState;
        }),
);
