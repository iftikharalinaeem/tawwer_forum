/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticle, IArticleFragment, IRevision, IRevisionFragment } from "@knowledge/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { produce } from "immer";
import { IStoreState } from "@knowledge/state/model";

export interface IArticleState {
    articlesByID: {
        [key: number]: IArticle;
    };
    articleFragmentsByID: {
        [key: number]: IArticleFragment;
    };
    revisionsByID: {
        [key: number]: IRevision;
    };
    revisionFragmentsByID: {
        [key: number]: IRevisionFragment;
    };
}

/**
 * Reducer for the article page.
 */
export default class ArticleModel implements ReduxReducer<IArticleState> {
    public static selectArticle(state: IStoreState, articleID): IArticle | null {
        const stateSlice = this.stateSlice(state);
        return stateSlice.articlesByID[articleID] || null;
    }

    public static selectRevision(state: IStoreState, revisionID): IRevision | null {
        const stateSlice = this.stateSlice(state);
        return stateSlice.revisionsByID[revisionID] || null;
    }

    public static selectRevisionFragment(state: IStoreState, revisionID): IRevisionFragment | null {
        const stateSlice = this.stateSlice(state);
        return stateSlice.revisionFragmentsByID[revisionID] || null;
    }

    private static stateSlice(state: IStoreState): IArticleState {
        if (!state.knowledge || !state.knowledge.articles) {
            throw new Error(
                `It seems the ArticleModel's reducer was not properly configured. Expected to find 'knowledge.articles' in the passed state tree ${state}`,
            );
        }
        return state.knowledge.articles;
    }

    public initialState: IArticleState = {
        articlesByID: {},
        articleFragmentsByID: {},
        revisionsByID: {},
        revisionFragmentsByID: {},
    };

    public reducer = (
        state: IArticleState = this.initialState,
        action: typeof ArticleActions.ACTION_TYPES,
    ): IArticleState => {
        return produce(state, draft => {
            switch (action.type) {
                case ArticleActions.PATCH_ARTICLE_STATUS_RESPONSE:
                    try {
                        const { articlesByID } = draft;
                        let articleToUpdate = articlesByID[action.payload.data.articleID];
                        if (articleToUpdate) {
                            articleToUpdate.status = action.payload.data.status;
                        }
                    } catch (e) {
                        console.error(e);
                    }

                    break;
                case ArticleActions.GET_ARTICLE_RESPONSE: {
                    const { articleID } = action.payload.data;
                    draft.articlesByID[articleID] = action.payload.data;
                    break;
                }
                case ArticleActions.GET_ARTICLE_REVISIONS_RESPONSE:
                    const revisions = action.payload.data;
                    revisions.forEach(rev => {
                        draft.revisionFragmentsByID[rev.articleRevisionID] = rev;
                    });
                    break;
                case ArticleActions.GET_REVISION_RESPONSE:
                    const revision = action.payload.data;
                    draft.revisionsByID[revision.articleRevisionID] = revision;
            }
        });
    };
}
