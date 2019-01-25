/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/state/ReduxReducer";
import { KnowledgeReducer, IStoreState } from "@knowledge/state/model";
import { reducerWithoutInitialState } from "typescript-fsa-reducers";
import produce from "immer";
import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { ILoadable, LoadStatus } from "@library/@types/api";
import { createSelector } from "reselect";

/**
 * Model for working with actions & data related to the /api/v2/knowledge-bases endpoint.
 */
export default class KnowledgeBaseModel implements ReduxReducer<IKnowledgeBasesState> {
    /**
     * Selector for our own state.
     */
    private static selectSelf = (state: IStoreState) => state.knowledge.knowledgeBases;

    /**
     * Selector for a list of loaded knowledge bases.
     */
    public static selectKnowledgeBases = createSelector([KnowledgeBaseModel.selectSelf], selfState =>
        Object.values(selfState.knowledgeBasesByID.data || {}),
    );

    public initialState: IKnowledgeBasesState = {
        knowledgeBasesByID: {
            status: LoadStatus.PENDING,
        },
    };

    public reducer: ReducerType = (state = this.initialState, action) => {
        return produce(state, nextState => {
            return this.internalReducer(nextState, action);
        });
    };

    /**
     * Reducer factory for knowledge base items.
     */
    private internalReducer = reducerWithoutInitialState<IKnowledgeBasesState>()
        .case(KnowledgeBaseActions.GET_ACS.started, state => {
            state.knowledgeBasesByID.status = LoadStatus.LOADING;
            return state;
        })
        .case(KnowledgeBaseActions.GET_ACS.done, (state, payload) => {
            const normalized: { [id: number]: IKnowledgeBase } = {};
            for (const kb of payload.result) {
                normalized[kb.knowledgeBaseID] = kb;
            }
            state.knowledgeBasesByID.status = LoadStatus.SUCCESS;
            state.knowledgeBasesByID.data = normalized;
            return state;
        })
        .case(KnowledgeBaseActions.GET_ACS.failed, (state, action) => {
            state.knowledgeBasesByID.error = action.error;
            return state;
        });
}

export interface IKnowledgeBasesState {
    knowledgeBasesByID: ILoadable<{
        [id: number]: IKnowledgeBase;
    }>;
}

export enum KnowledgeBaseDisplayType {
    HELP = "help",
    GUIDE = "guide",
}

export enum KnowledgeBaseSortMode {
    MANUAL = "manual",
    NAME = "name",
    DATE_INSERTED = "dateInserted",
    DATE_INSERTED_DESC = "dateInsertedDesc",
}

/**
 * Interface representing a knowledge base resource.
 */
export interface IKnowledgeBase {
    knowledgeBaseID: number;
    name: string;
    description: string;
    sortArticles: KnowledgeBaseSortMode;
    insertUserID: number;
    dateInserted: string;
    updateUserID: number;
    dateUpdated: string;
    countArticles: number;
    countCategories: number;
    urlCode: string;
    url: string;
    icon: string;
    sourceLocale: string;
    viewType: KnowledgeBaseDisplayType;
    rootCategoryID: number;
}

type ReducerType = KnowledgeReducer<IKnowledgeBasesState>;
