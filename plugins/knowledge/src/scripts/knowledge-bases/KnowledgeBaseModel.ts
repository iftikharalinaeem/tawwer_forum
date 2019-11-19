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
import { getCurrentLocale } from "@vanilla/i18n";
import clone from "lodash/clone";

export interface ILoadedProduct {
    kb: IKnowledgeBase;
    patchKB: ILoadable<IKnowledgeBase>;
    deleteKB: ILoadable<{}, IKnowledgeBase>;
}
export interface IKbFormState {
    knowledgeBaseID?: number;
    name: string;
    urlCode: string;
    siteSectionGroup: string | null;
    description: string;
    icon: string | null;
    bannerImage: string | null;
    viewType: KbViewType;
    sourceLocale: string | null;
    sortArticles: KnowledgeBaseSortMode;
}
export interface IKnowledgeBasesState {
    knowledgeBasesByID: ILoadable<{
        [id: number]: IKnowledgeBase;
    }>;
    form: IKbFormState;
    formSubmit: ILoadable<{}>;
    deleteSubmit: ILoadable<{}>;
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
export const INITIAL_KB_FORM: IKbFormState = {
    name: "",
    urlCode: "",
    siteSectionGroup: "",
    description: "",
    icon: null,
    bannerImage: null,
    viewType: KbViewType.GUIDE,
    sortArticles: KnowledgeBaseSortMode.MANUAL,
    sourceLocale: getCurrentLocale(),
};

export interface ISiteSection {
    basePath: string;
    contentLocale: string;
    siteSectionGroup: string;
    sectionID: string;
    name: string;
}

export enum KnowledgeBaseStatus {
    DELETED = "deleted",
    PUBLISHED = "published",
}

export interface IPatchKnowledgeBaseRequest {
    knowledgeBaseID: number;
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
    siteSectionGroup: string | null;
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
    public static selectKnowledgeBases = createSelector([KnowledgeBaseModel.selectSelf], selfState =>
        Object.values(selfState.knowledgeBasesByID.data || {}),
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
        form: INITIAL_KB_FORM,
        formSubmit: {
            status: LoadStatus.PENDING,
        },
        deleteSubmit: {
            status: LoadStatus.PENDING,
        },
    };

    public initialState = KnowledgeBaseModel.INITIAL_STATE;
    public reducer: ReducerType = (state = clone(this.initialState), action) => {
        return produce(state, nextState => {
            return this.internalReducer(nextState, action);
        });
    };

    /**
     * Reducer factory for knowledge base items.
     */
    private internalReducer = reducerWithoutInitialState<IKnowledgeBasesState>()
        .case(KnowledgeBaseActions.initFormAC, (state, payload) => {
            if (payload.kbID != null) {
                const existingKB = {
                    ...state.knowledgeBasesByID.data![payload.kbID],
                };
                state.form = existingKB;
            } else {
                console.log("restoring to initial");
                state.form = INITIAL_KB_FORM;
            }

            return state;
        })
        .case(KnowledgeBaseActions.updateFormAC, (state, payload) => {
            if (payload.viewType === KbViewType.GUIDE) {
                payload.sortArticles = KnowledgeBaseSortMode.MANUAL;
            } else if (payload.viewType === KbViewType.HELP) {
                payload.sortArticles = KnowledgeBaseSortMode.DATE_INSERTED_DESC;
            }

            state.form = {
                ...state.form,
                ...payload,
            };
            return state;
        })
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
        .case(KnowledgeBaseActions.clearErrorAC, state => {
            state.formSubmit = {
                status: LoadStatus.PENDING,
            };
            return state;
        })
        .case(KnowledgeBaseActions.GET_ACS.failed, (state, action) => {
            state.knowledgeBasesByID.error = action.error;
            return state;
        })
        .case(KnowledgeBaseActions.postKB_ACs.started, (state, payload) => {
            state.formSubmit.status = LoadStatus.LOADING;
            return state;
        })
        .case(KnowledgeBaseActions.postKB_ACs.failed, (state, payload) => {
            state.formSubmit.status = LoadStatus.ERROR;
            state.formSubmit.error = payload.error;
            return state;
        })
        .case(KnowledgeBaseActions.postKB_ACs.done, (state, payload) => {
            state.formSubmit.status = LoadStatus.SUCCESS;
            state.knowledgeBasesByID[payload.result.knowledgeBaseID] = payload.result;
            return state;
        })
        .case(KnowledgeBaseActions.patchKB_ACs.started, (state, payload) => {
            state.formSubmit.status = LoadStatus.LOADING;
            return state;
        })
        .case(KnowledgeBaseActions.patchKB_ACs.failed, (state, payload) => {
            state.formSubmit.status = LoadStatus.ERROR;
            state.formSubmit.error = payload.error;
            return state;
        })
        .case(KnowledgeBaseActions.patchKB_ACs.done, (state, payload) => {
            state.formSubmit.status = LoadStatus.SUCCESS;
            state.knowledgeBasesByID.data![payload.result.knowledgeBaseID] = payload.result;
            return state;
        })
        .case(KnowledgeBaseActions.deleteKB_ACs.started, (state, payload) => {
            state.deleteSubmit.status = LoadStatus.LOADING;
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
