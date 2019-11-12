/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/redux/ReduxReducer";
import { KnowledgeReducer, IKnowledgeAppStoreState } from "@knowledge/state/model";
import { reducerWithoutInitialState } from "typescript-fsa-reducers";
import produce from "immer";
import KnowledgeBaseActions, { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { createSelector } from "reselect";
import { IKbNavigationItem, KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { LoadStatus, ILoadable } from "@library/@types/api/core";
import { useSelector } from "react-redux";
import { useEffect } from "react";

export interface ILoadedProduct {
    kb: IKnowledgeBase;
    patchKB: ILoadable<IKnowledgeBase>;
    deleteKB: ILoadable<{}, IKnowledgeBase>;
}
export interface IKbFormState {
    title: string;
    url: string;
    product: string;
    description: string;
    icon: string;
    image: string;
    viewType: KbViewType;
    locale: string;
}
export interface IKnowledgeBasesState {
    knowledgeBasesByID: ILoadable<{
        [id: number]: IKnowledgeBase;
    }>;
    form: IKbFormState;
}

export enum KbViewType {
    HELP = "help",
    GUIDE = "guide",
}

export enum KnowledgeBaseSortMode {
    MANUAL = "manual",
    NAME = "name",
    DATE_INSERTED = "dateInserted",
    DATE_INSERTED_DESC = "dateInsertedDesc",
}
export const INTIAL_KB_FORM: IKbFormState = {
    title: "",
    url: "",
    product: "",
    description: "",
    icon: "",
    image: "",
    viewType: KbViewType.HELP,
    locale: "",
};

interface ISiteSection {
    basePath: string;
    contentLocale: string;
    sectionGroup: string;
    sectionID: string;
    name: string;
}

export enum KnowledgeBaseStatus {
    DELETED = "deleted",
    PUBLISHED = "published",
}

export interface IPatchKnowledgeBaseRequest {
    description: string;
    icon?: string;
    name: string;
    sortArticles?: KnowledgeBaseSortMode;
    sourceLocale?: string;
    urlCode: string;
    viewType: KbViewType;
}
export interface IPostKnowledgeBaseRequest {
    description: string;
    icon?: string;
    name: string;
    sortArticles?: KnowledgeBaseSortMode;
    sourceLocale?: string;
    urlCode: string;
    viewType: KbViewType;
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
    status: KnowledgeBaseStatus;
    bannerImage: string;
    sourceLocale: string;
    viewType: KbViewType;
    rootCategoryID: number;
    defaultArticleID: number | null;
    siteSections: ISiteSection[];
}

/**
 * Model for working with actions & data related to the /api/v2/knowledge-bases endpoint.
 */
export default class KnowledgeBaseModel implements ReduxReducer<IKnowledgeBasesState> {
    /**
     * Selector for our own state.
     */
    private static selectSelf = (state: IKnowledgeAppStoreState) => state.knowledge.knowledgeBases;

    /**
     * Selector for a list of loaded knowledge bases.
     */
    public static selectKnowledgeBases = createSelector(
        [KnowledgeBaseModel.selectSelf],
        selfState => Object.values(selfState.knowledgeBasesByID.data || {}),
    );

    public static selectKnowledgeBasesAsNavItems = createSelector(
        [KnowledgeBaseModel.selectKnowledgeBases],
        (kbs): IKbNavigationItem[] => {
            return kbs.map(kb => {
                const navItem: IKbNavigationItem = {
                    recordType: KbRecordType.KB,
                    recordID: kb.knowledgeBaseID,
                    knowledgeBaseID: kb.knowledgeBaseID,
                    name: kb.name,
                    url: kb.url,
                    parentID: -1,
                    sort: null,
                };
                return navItem;
            });
        },
    );

    public static selectByUrlCode = (state: IKnowledgeAppStoreState, urlCode: string) => {
        // We could index these by urlCode as well, but right now it doesn't seem necessary.
        const selfState = KnowledgeBaseModel.selectKnowledgeBases(state);
        return selfState.find(kb => kb.urlCode === urlCode) || null;
    };

    public static INITIAL_STATE: IKnowledgeBasesState = {
        knowledgeBasesByID: {
            status: LoadStatus.PENDING,
        },
        form: INTIAL_KB_FORM,
    };

    public initialState = KnowledgeBaseModel.INITIAL_STATE;
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
        })
        .case(KnowledgeBaseActions.postKB_ACs.started, (state, payload) => {
            state.knowledgeBasesByID[payload.kbID] = {
                status: LoadStatus.PENDING,
                data: payload,
            };
            return state;
        })
        .case(KnowledgeBaseActions.postKB_ACs.done, (state, payload) => {
            delete state.knowledgeBasesByID[payload.params.kbID];
            state.knowledgeBasesByID[payload.params.kbID] = {
                knowledgeBase: payload.result,
                patchKB: { status: LoadStatus.PENDING },
                deleteKB: { status: LoadStatus.PENDING },
            };
            return state;
        })
        .case(KnowledgeBaseActions.patchKB_ACs.started, (state, payload) => {
            const existingKB = state.knowledgeBasesByID[payload.kbID];
            existingKB.patchKB = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(KnowledgeBaseActions.patchKB_ACs.done, (state, payload) => {
            const existingKB = state.knowledgeBasesByID[payload.params.kbID];
            existingKB.kb = payload.result;
            existingKB.patchKB = {
                status: LoadStatus.SUCCESS,
            };
            return state;
        })
        .case(KnowledgeBaseActions.deleteKB_ACs.started, (state, payload) => {
            const existingKB = state.knowledgeBasesByID[payload.kbID];
            existingKB;
            existingKB.deleteKB.status = LoadStatus.LOADING;
            return state;
        })
        .case(KnowledgeBaseActions.deleteKB_ACs.done, (state, payload) => {
            delete state.knowledgeBasesByID[payload.params.kbID];
            return state;
        })
        .case(KnowledgeBaseActions.deleteKB_ACs.failed, (state, payload) => {
            const existingKB = state.knowledgeBasesByID[payload.params.kbID];
            existingKB.deleteKB.status = LoadStatus.ERROR;
            existingKB.deleteKB.error = payload.error.response.data;
            return state;
        });
}

type ReducerType = KnowledgeReducer<IKnowledgeBasesState>;

export function useKnowledgeBases(status: KnowledgeBaseStatus) {
    const { knowledgeBasesByID } = useSelector((state: IKnowledgeAppStoreState) => state.knowledge.knowledgeBases);
    const { getAll } = useKnowledgeBaseActions();

    useEffect(() => {
        if (knowledgeBasesByID.status === LoadStatus.PENDING) {
            getAll(status);
        }
    }, [knowledgeBasesByID, getAll]);

    return knowledgeBasesByID;
}
export function useKBData() {
    return useSelector((state: IKnowledgeAppStoreState) => state.knowledge.knowledgeBases);
}
