/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticle, IArticleFragment, IResponseArticleDraft, IRevision, IRevisionFragment } from "@knowledge/@types/api";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { IStoreState, KnowledgeReducer } from "@knowledge/state/model";
import ReduxReducer from "@library/redux/ReduxReducer";
import { produce } from "immer";
import { reducerWithoutInitialState } from "typescript-fsa-reducers";
import { article } from "@library/icons";

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
    draftsByID: {
        [key: number]: IResponseArticleDraft;
    };
}

type ReducerType = KnowledgeReducer<IArticleState>;

/**
 * Selectors and reducer for the article resources.
 */
export default class ArticleModel implements ReduxReducer<IArticleState> {
    /**
     * Select an article out of the stored articles.
     *
     * @param state A full state instance.
     * @param articleID The ID of the article to select.
     */
    public static selectArticle(state: IStoreState, articleID: number): IArticle | null {
        const stateSlice = this.stateSlice(state);
        return stateSlice.articlesByID[articleID] || null;
    }

    /**
     * Select a full revision out of the stored revisions.
     *
     * @param state A full state instance.
     * @param revisionID The ID of the revision to select.
     */
    public static selectRevision(state: IStoreState, revisionID: number): IRevision | null {
        const stateSlice = this.stateSlice(state);
        return stateSlice.revisionsByID[revisionID] || null;
    }

    /**
     * Select a revision revision fragment out of the stored fragments.
     *
     * @param state A full state instance.
     * @param revisionID The ID of the revision to select.
     */
    public static selectRevisionFragment(state: IStoreState, revisionID: number): IRevisionFragment | null {
        const stateSlice = this.stateSlice(state);
        return stateSlice.revisionFragmentsByID[revisionID] || null;
    }

    /**
     * Select an article draft out of the stored drafts.
     *
     * @param state A full state instance.
     * @param draftID The ID of the draft to select.
     */
    public static selectDraft(state: IStoreState, draftID: number): IResponseArticleDraft | null {
        const stateSlice = this.stateSlice(state);
        return stateSlice.draftsByID[draftID] || null;
    }

    /**
     * Get the slice of state that this model works with.
     *
     * @param state A full state instance.
     * @throws An error if the state wasn't initialized properly.
     */
    private static stateSlice(state: IStoreState): IArticleState {
        if (!state.knowledge || !state.knowledge.articles) {
            throw new Error(
                `It seems the ArticleModel's reducer was not properly configured. Expected to find 'knowledge.articles' in the passed state tree ${state}`,
            );
        }
        return state.knowledge.articles;
    }

    public static readonly INITIAL_STATE: IArticleState = {
        articlesByID: {},
        articleFragmentsByID: {},
        revisionsByID: {},
        revisionFragmentsByID: {},
        draftsByID: {},
    };

    public initialState: IArticleState = ArticleModel.INITIAL_STATE;

    private fsaReducer = reducerWithoutInitialState<IArticleState>().case(
        ArticleActions.putReactACs.done,
        (nextState, payload) => {
            const { articleID } = payload.params;
            const { reactions } = payload.result;
            const existingArticle = nextState.articlesByID[articleID];
            if (existingArticle) {
                existingArticle.reactions = reactions;
            }

            return nextState;
        },
    );

    public reducer: ReducerType = (state = this.initialState, action): IArticleState => {
        return produce(state, nextState => {
            nextState = this.fsaReducer(nextState, action);
            switch (action.type) {
                case ArticleActions.PATCH_ARTICLE_STATUS_RESPONSE:
                    const { articlesByID } = nextState;
                    const articleToUpdate = articlesByID[action.payload.data.articleID];
                    if (articleToUpdate) {
                        articleToUpdate.status = action.payload.data.status;
                    }
                    break;
                case ArticleActions.PATCH_ARTICLE_RESPONSE:
                    // We need to clear out all revisions that are related to the article.
                    // Their active state is now undefined.
                    nextState.revisionFragmentsByID = {};
                    nextState.revisionsByID = {};
                    break;
                case ArticleActions.GET_ARTICLE_RESPONSE: {
                    const { articleID } = action.payload.data;
                    nextState.articlesByID[articleID] = action.payload.data;
                    break;
                }
                case ArticleActions.GET_ARTICLE_REVISIONS_RESPONSE:
                    const revisions = action.payload.data;
                    revisions.forEach(rev => {
                        nextState.revisionFragmentsByID[rev.articleRevisionID] = rev;
                    });
                    break;
                case ArticleActions.GET_REVISION_RESPONSE:
                    const revision = action.payload.data;
                    nextState.revisionsByID[revision.articleRevisionID] = revision;
                    break;
                case ArticleActions.PATCH_DRAFT_RESPONSE:
                case ArticleActions.POST_DRAFT_RESPONSE:
                case ArticleActions.GET_DRAFT_RESPONSE:
                    const draft = action.payload.data;
                    nextState.draftsByID[draft.draftID] = draft;
                    break;
                case ArticleActions.GET_DRAFTS_RESPONSE:
                    const drafts = action.payload.data;
                    for (const currentDraft of drafts) {
                        nextState.draftsByID[currentDraft.draftID] = currentDraft;
                    }
                    break;
                case ArticleActions.DELETE_DRAFT_RESPONSE:
                    if (nextState.draftsByID[action.meta.draftID]) {
                        delete nextState.draftsByID[action.meta.draftID];
                    }
                    break;
            }
        });
    };
}
