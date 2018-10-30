/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticle, ArticleStatus, IKbCategoryFragment } from "@knowledge/@types/api";
import { LoadStatus, ILoadable } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { produce } from "immer";
import { ICrumb } from "@library/components/Breadcrumbs";
import { IStoreState } from "@knowledge/state/model";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";

export interface IArticlePageState {
    articleID: number | null;
    articleLoadable: ILoadable<{}>;
    restoreStatus: LoadStatus;
}

export interface IInjectableArticlePageState {
    loadable: ILoadable<{
        article: IArticle;
        breadcrumbs: ICrumb[];
    }>;
    restoreStatus: LoadStatus;
}

/**
 * Reducer for the article page.
 */
export default class ArticlePageModel implements ReduxReducer<IArticlePageState> {
    public static getInjectableState(state: IStoreState): IInjectableArticlePageState {
        const { articleID, articleLoadable, restoreStatus } = state.knowledge.articlePage;

        if (articleLoadable.status === LoadStatus.SUCCESS && articleID !== null) {
            const article = ArticleModel.selectArticle(state, articleID)!;
            const breadcrumbs = CategoryModel.selectKbCategoryBreadcrumb(state, article.knowledgeCategoryID || 1);

            return {
                restoreStatus,
                loadable: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        breadcrumbs,
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

    public reducer = (
        state: IArticlePageState = this.initialState,
        action: typeof ArticlePageActions.ACTION_TYPES | typeof ArticleActions.ACTION_TYPES,
    ): IArticlePageState => {
        return produce(state, draft => {
            switch (action.type) {
                case ArticlePageActions.INIT:
                    const { preloaded, articleID } = action.payload;
                    draft.articleID = articleID;
                    if (preloaded) {
                        draft.articleLoadable.status = LoadStatus.SUCCESS;
                    }
                    break;
                case ArticlePageActions.RESET:
                    return this.initialState;
            }

            if (action.meta && action.meta.articleID && draft.articleID === action.meta.articleID) {
                switch (action.type) {
                    case ArticleActions.GET_ARTICLE_REQUEST:
                        draft.articleLoadable.status = LoadStatus.LOADING;
                        break;
                    case ArticleActions.GET_ARTICLE_RESPONSE:
                        draft.articleLoadable.status = LoadStatus.SUCCESS;
                        break;
                    case ArticleActions.GET_ARTICLE_ERROR:
                        draft.articleLoadable.status = LoadStatus.ERROR;
                        break;
                    case ArticleActions.PATCH_ARTICLE_STATUS_REQUEST:
                        draft.restoreStatus = LoadStatus.LOADING;
                        break;
                    case ArticleActions.PATCH_ARTICLE_STATUS_RESPONSE:
                        draft.restoreStatus = LoadStatus.SUCCESS;
                        break;
                }
            }
        });
    };
}
