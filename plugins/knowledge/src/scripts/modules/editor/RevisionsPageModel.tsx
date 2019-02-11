/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/state/ReduxReducer";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { produce } from "immer";
import { LoadStatus, ILoadable } from "@library/@types/api";
import { IRevisionFragment, IRevision, IArticle } from "@knowledge/@types/api";
import { IStoreState } from "@knowledge/state/model";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import RevisionsPageActions from "@knowledge/modules/editor/RevisionsPageActions";
import { ICrumb } from "@library/components/Breadcrumbs";

export interface IRevisionsPageState {
    articleID: number | null;
    articleStatus: ILoadable<any>;
    revisionIDs: number[];
    revisionsStatus: ILoadable<any>;
    selectedRevisionStatus: ILoadable<any>;
    selectedRevisionID: number | null;
}

export interface IInjectableRevisionsState {
    article: ILoadable<IArticle>;
    revisions: ILoadable<IRevisionFragment[]>;
    selectedRevision: ILoadable<IRevision | null>;
    selectedRevisionID: number | null;
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
    public static selectLatestRevision(state: IStoreState): IRevisionFragment | null {
        const revs = this.selectRevisions(state);
        if (revs.length === 0) {
            return null;
        } else {
            return revs[revs.length - 1];
        }
    }

    /**
     * Select the currently selected revision.
     *
     * @param state A full state object.
     */
    public static selectActiveRevision(state: IStoreState): IRevision | null {
        const stateSlice = this.stateSlice(state);
        const { selectedRevisionID } = stateSlice;
        return selectedRevisionID ? ArticleModel.selectRevision(state, selectedRevisionID)! : null;
    }

    /**
     * Select all revisions currently loaded.
     *
     * @param state A full state object.
     */
    public static selectRevisions(state: IStoreState): IRevisionFragment[] {
        const stateSlice = this.stateSlice(state);
        return stateSlice.revisionIDs.map(id => ArticleModel.selectRevisionFragment(state, id)!);
    }

    /**
     * Get props for injecting into react.
     *
     * @param state A full state object.
     */
    public static getInjectableProps(state: IStoreState): IInjectableRevisionsState {
        const stateSlice = RevisionsPageModel.stateSlice(state);
        const { selectedRevisionID, selectedRevisionStatus, revisionsStatus, articleID, articleStatus } = stateSlice;
        const article = articleID ? ArticleModel.selectArticle(state, articleID) : null;
        return {
            // article:
            revisions: {
                ...revisionsStatus,
                data: RevisionsPageModel.selectRevisions(state),
            },
            article: {
                ...articleStatus,
                data: article || undefined,
            },
            selectedRevision: {
                ...selectedRevisionStatus,
                data: RevisionsPageModel.selectActiveRevision(state),
            },
            selectedRevisionID,
        };
    }

    /**
     * Get the slice of state that this model works with.
     *
     * @param state A full state instance.
     * @throws An error if the state wasn't initialized properly.
     */
    private static stateSlice(state: IStoreState): IRevisionsPageState {
        if (!state.knowledge || !state.knowledge.revisionsPage) {
            throw new Error(
                "The revision page model has not been wired up properly. Expected to find 'state.knowledge.revisionsPage'.",
            );
        }

        return state.knowledge.revisionsPage;
    }

    public initialState: IRevisionsPageState = {
        articleID: null,
        articleStatus: {
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
    };

    /**
     * Reducer implementation for the revisions page resources.
     */
    public reducer = (
        state: IRevisionsPageState = this.initialState,
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
                    return this.initialState;
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

            // Handle some revision actions if they pertain to our own article.
            if (action.meta && action.meta.articleID && draft.articleID === action.meta.articleID) {
                switch (action.type) {
                    case ArticleActions.GET_ARTICLE_REQUEST:
                        draft.articleStatus.status = LoadStatus.LOADING;
                        break;
                    case ArticleActions.GET_ARTICLE_RESPONSE:
                        draft.articleStatus.status = LoadStatus.SUCCESS;
                        break;
                    case ArticleActions.GET_ARTICLE_ERROR:
                        draft.articleStatus.status = LoadStatus.ERROR;
                        draft.articleStatus.error = action.payload;
                        break;
                    case ArticleActions.GET_ARTICLE_REVISIONS_REQUEST:
                        draft.revisionsStatus.status = LoadStatus.LOADING;
                        break;
                    case ArticleActions.GET_ARTICLE_REVISIONS_RESPONSE:
                        draft.revisionsStatus.status = LoadStatus.SUCCESS;
                        draft.revisionIDs = action.payload.data.map(rev => rev.articleRevisionID);
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
