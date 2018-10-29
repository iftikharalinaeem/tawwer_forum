/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/state/ReduxReducer";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { produce } from "immer";
import { LoadStatus, ILoadable } from "@library/@types/api";
import { number } from "prop-types";
import RevisionsPageActions from "./RevisionsPageActions";
import { IRevisionFragment, IRevision } from "@knowledge/@types/api";
import { IStoreState } from "@knowledge/state/model";
import ArticleModel from "../article/ArticleModel";

export interface IRevisionsPageState {
    articleID: number | null;
    revisionIDs: number[];
    revisionsStatus: ILoadable<any>;
    selectedRevisionStatus: ILoadable<any>;
    selectedRevisionID: number | null;
}

export interface IInjectableRevisionsState {
    revisions: ILoadable<IRevisionFragment[]>;
    selectedRevision: ILoadable<IRevision | null>;
    selectedRevisionID: number | null;
}

/**
 * Reducer for the article page.
 */
export default class RevisionsPageModel implements ReduxReducer<IRevisionsPageState> {
    public static selectLatestRevision(state: IStoreState): IRevisionFragment | null {
        const revs = this.selectRevisions(state);
        if (revs.length === 0) {
            return null;
        } else {
            return revs[revs.length - 1];
        }
    }

    public static selectRevisions(state: IStoreState): IRevisionFragment[] {
        const stateSlice = this.stateSlice(state);
        return stateSlice.revisionIDs.map(id => ArticleModel.selectRevisionFragment(state, id)!);
    }

    public static getInjectableProps(state: IStoreState): IInjectableRevisionsState {
        const stateSlice = RevisionsPageModel.stateSlice(state);
        const { selectedRevisionID, selectedRevisionStatus, revisionsStatus } = stateSlice;
        return {
            revisions: {
                ...revisionsStatus,
                data: RevisionsPageModel.selectRevisions(state),
            },
            selectedRevision: {
                ...selectedRevisionStatus,
                data: ArticleModel.selectRevision(state, selectedRevisionID)!,
            },
            selectedRevisionID,
        };
    }

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
        selectedRevisionID: null,
        selectedRevisionStatus: {
            status: LoadStatus.PENDING,
        },
        revisionIDs: [],
        revisionsStatus: {
            status: LoadStatus.PENDING,
        },
    };

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

            if (action.meta && action.meta.articleID && draft.articleID === action.meta.articleID) {
                switch (action.type) {
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
