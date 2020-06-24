/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IResponseArticleDraft } from "@knowledge/@types/api/article";
import { IRevision, IRevisionFragment } from "@knowledge/@types/api/articleRevision";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import RevisionsPageActions from "@knowledge/modules/editor/RevisionsPageActions";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import ReduxReducer from "@library/redux/ReduxReducer";
import { produce } from "immer";
import DraftsPageActions from "../drafts/DraftsPageActions";
import SimplePagerModel from "@library/navigation/SimplePagerModel";
import { reducerWithInitialState } from "typescript-fsa-reducers";

export interface IRevisionsPageState {
    articleID: number | null;
    articleStatus: ILoadable<any>;
    draftIDs: number[];
    revisionIDs: number[];
    revisionsStatus: ILoadable<any>;
    draftsStatus: ILoadable<any>;
    selectedRevisionStatus: ILoadable<any>;
    selectedRevisionID: number | null;
    pagination: {
        revisions: {
            current: number;
            next?: number;
            prev?: number;
        };
    };
}

/**
 * Reducer for the article page.
 */
export default class RevisionsPageModel implements ReduxReducer<IRevisionsPageState> {
    /**
     * Select the latest revision from the currently loaded revisions.
     *
     * @param state A full state object.
     */
    public static selectLatestRevision(state: IKnowledgeAppStoreState): IRevisionFragment | null {
        const revs = this.selectRevisions(state);
        if (revs.length === 0) {
            return null;
        } else {
            return revs[0];
        }
    }

    /**
     * Select the currently selected revision.
     *
     * @param state A full state object.
     */
    public static selectActiveRevision(state: IKnowledgeAppStoreState): IRevision | null {
        const stateSlice = this.stateSlice(state);
        const { selectedRevisionID } = stateSlice;
        return selectedRevisionID ? ArticleModel.selectRevision(state, selectedRevisionID)! : null;
    }

    /**
     * Select all drafts currently loaded.
     *
     * @param state A full state object.
     */
    public static selectDrafts(state: IKnowledgeAppStoreState): IResponseArticleDraft[] {
        const stateSlice = this.stateSlice(state);
        return stateSlice.draftIDs.map(id => ArticleModel.selectDraft(state, id)!);
    }

    /**
     * Select all revisions currently loaded.
     *
     * @param state A full state object.
     */
    public static selectRevisions(state: IKnowledgeAppStoreState): IRevisionFragment[] {
        const stateSlice = this.stateSlice(state);
        return stateSlice.revisionIDs.map(id => ArticleModel.selectRevisionFragment(state, id)!);
    }

    /**
     * Get the slice of state that this model works with.
     *
     * @param state A full state instance.
     * @throws An error if the state wasn't initialized properly.
     */
    private static stateSlice(state: IKnowledgeAppStoreState): IRevisionsPageState {
        if (!state.knowledge || !state.knowledge.revisionsPage) {
            throw new Error(
                "The revision page model has not been wired up properly. Expected to find 'state.knowledge.revisionsPage'.",
            );
        }

        return state.knowledge.revisionsPage;
    }

    public static INITIAL_STATE: IRevisionsPageState = {
        articleID: null,
        articleStatus: {
            status: LoadStatus.PENDING,
        },
        draftIDs: [],
        draftsStatus: {
            status: LoadStatus.PENDING,
        },
        selectedRevisionID: null,
        selectedRevisionStatus: {
            status: LoadStatus.PENDING,
        },
        revisionIDs: [],
        revisionsStatus: {
            status: LoadStatus.PENDING,
        },
        pagination: {
            revisions: {
                current: 1,
            },
        },
    };

    public reducer = produce(
        reducerWithInitialState<IRevisionsPageState>(RevisionsPageModel.INITIAL_STATE)
            .case(ArticleActions.getArticleACs.started, (nextState, payload) => {
                if (payload.articleID === nextState.articleID) {
                    nextState.articleStatus.status = LoadStatus.LOADING;
                }
                return nextState;
            })
            .case(ArticleActions.getArticleACs.done, (nextState, payload) => {
                if (payload.params.articleID === nextState.articleID) {
                    nextState.articleStatus.status = LoadStatus.SUCCESS;
                    nextState.articleStatus.error = undefined;
                }
                return nextState;
            })
            .case(ArticleActions.getArticleACs.failed, (nextState, payload) => {
                if (payload.params.articleID === nextState.articleID) {
                    nextState.articleStatus.status = LoadStatus.ERROR;
                    nextState.articleStatus.error = payload.error;
                }
                return nextState;
            })
            .default((nextState, action) => {
                return this.fallbackReducer(nextState, action);
            }),
    );

    /**
     * Reducer implementation for the revisions page resources.
     */
    private fallbackReducer = (
        state: IRevisionsPageState = RevisionsPageModel.INITIAL_STATE,
        action: typeof ArticleActions.ACTION_TYPES | typeof RevisionsPageActions.ACTION_TYPES,
    ): IRevisionsPageState => {
        return produce(state, draft => {
            switch (action.type) {
                case RevisionsPageActions.SET_ARTICLE:
                    draft.articleID = action.payload.articleID;
                    break;
                case RevisionsPageActions.SET_REVISION:
                    draft.selectedRevisionID = action.payload.revisionID;
                    break;
                case RevisionsPageActions.RESET:
                    return RevisionsPageModel.INITIAL_STATE;
            }

            // Handle some revision actions if they pertain to our own revision.
            if (action.meta && action.meta.revisionID && draft.selectedRevisionID === action.meta.revisionID) {
                switch (action.type) {
                    case ArticleActions.GET_REVISION_REQUEST:
                        draft.selectedRevisionStatus.status = LoadStatus.LOADING;
                        break;
                    case ArticleActions.GET_REVISION_RESPONSE:
                        draft.selectedRevisionStatus.status = LoadStatus.SUCCESS;
                        break;
                    case ArticleActions.GET_REVISION_ERROR:
                        draft.selectedRevisionStatus.status = LoadStatus.ERROR;
                        draft.selectedRevisionStatus.error = action.payload;
                        break;
                }
            }

            if (action.meta && action.meta.identifier && action.meta.identifier === DraftsPageActions.IDENTIFIER) {
                switch (action.type) {
                    case ArticleActions.GET_DRAFTS_REQUEST:
                        draft.draftsStatus.status = LoadStatus.LOADING;
                        break;
                    case ArticleActions.GET_DRAFTS_RESPONSE:
                        draft.draftsStatus.status = LoadStatus.SUCCESS;
                        draft.draftIDs = action.payload.data.map(articleDraft => articleDraft.draftID);
                        break;
                    case ArticleActions.GET_DRAFTS_ERROR:
                        draft.draftsStatus.status = LoadStatus.ERROR;
                        draft.draftsStatus.error = action.payload;
                        break;
                }
            }

            // Handle some revision actions if they pertain to our own article.
            if (action.meta && action.meta.articleID && draft.articleID === action.meta.articleID) {
                switch (action.type) {
                    case ArticleActions.GET_ARTICLE_REVISIONS_REQUEST:
                        draft.revisionsStatus.status = LoadStatus.LOADING;
                        break;
                    case ArticleActions.GET_ARTICLE_REVISIONS_RESPONSE:
                        draft.revisionsStatus.status = LoadStatus.SUCCESS;
                        const newRevisions = action.payload.data.map(rev => rev.articleRevisionID);
                        draft.revisionIDs = state.revisionIDs.concat(newRevisions);

                        const { link } = action.payload.headers;
                        const currentPage = action.meta.page || 1;
                        if (link) {
                            const pages = SimplePagerModel.parseLinkHeader(link, "page");
                            draft.pagination.revisions = {
                                current: currentPage,
                                next: pages.next,
                                prev: pages.prev,
                            };
                        }
                        break;
                    case ArticleActions.GET_ARTICLE_REVISIONS_ERROR:
                        draft.revisionsStatus.status = LoadStatus.ERROR;
                        draft.revisionsStatus.error = action.payload;
                        break;
                }
            }
        });
    };
}
