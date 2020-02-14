/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { IKnowledgeAppStoreState, KnowledgeReducer } from "@knowledge/state/model";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import ReduxReducer from "@library/redux/ReduxReducer";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IFeatureArticle } from "@knowledge/@types/api/article";

export interface IArticleMenuState {
    delete: ILoadable<{}>;
    featured: ILoadable<IFeatureArticle>;
}

type ReducerType = KnowledgeReducer<IArticleMenuState>;

/**
 * Model and reducer for the ArticleMenu
 */
export default class ArticleMenuModel implements ReduxReducer<IArticleMenuState> {
    public static mapStateToProps(storeState: IKnowledgeAppStoreState): IArticleMenuState {
        return storeState.knowledge.articleMenu;
    }

    public static INITIAL_STATE: IArticleMenuState = {
        delete: {
            status: LoadStatus.PENDING,
        },
        featured: {
            status: LoadStatus.PENDING,
        },
    };

    public internalReducer: ReducerType = (nextState = ArticleMenuModel.INITIAL_STATE, action) => {
        switch (action.type) {
            case ArticleActions.PATCH_ARTICLE_STATUS_REQUEST:
                nextState.delete.status = LoadStatus.LOADING;
                break;
            case ArticleActions.PATCH_ARTICLE_STATUS_RESPONSE:
                nextState.delete.status = LoadStatus.SUCCESS;
                break;
            case ArticleActions.PATCH_ARTICLE_STATUS_ERROR:
                nextState.delete.status = LoadStatus.ERROR;
                nextState.delete.error = action.payload;
                break;
        }

        return nextState;
    };
    public reducer = produce(
        reducerWithInitialState<IArticleMenuState>(ArticleMenuModel.INITIAL_STATE)
            .case(ArticleActions.putFeaturedArticles.started, (nextState, payload) => {
                nextState.featured.status = LoadStatus.PENDING;
                return nextState;
            })
            .case(ArticleActions.putFeaturedArticles.done, (nextState, payload) => {
                if (nextState.featured.data) {
                    nextState.featured.data.featured = payload.params.featured;
                    nextState.featured.status = LoadStatus.SUCCESS;
                }
                return nextState;
            })
            .case(ArticleActions.putFeaturedArticles.failed, (nextState, payload) => {
                nextState.featured.status = LoadStatus.ERROR;
                return nextState;
            })
            .default(this.internalReducer),
    );
}
