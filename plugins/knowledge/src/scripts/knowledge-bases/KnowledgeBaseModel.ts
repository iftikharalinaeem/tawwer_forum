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
import { LoadStatus, ILoadable, ILinkListData } from "@library/@types/api/core";
import { useSelector } from "react-redux";
import { useEffect, useDebugValue } from "react";
import { getCurrentLocale } from "@vanilla/i18n";
import clone from "lodash/clone";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import { IKbCategory, IKbCategoryFragment } from "@knowledge/@types/api/kbCategory";
import { useNavigationActions } from "@knowledge/navigation/state/NavigationActions";

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
    bannerImage?: string | null;
    bannerContentImage?: string | null;
    viewType: KbViewType;
    sourceLocale: string | null;
    sortArticles: KnowledgeBaseSortMode;
    hasCustomPermission: boolean;
    viewRoleIDs: number[];
    editRoleIDs: number[];
    isUniversalSource: boolean;
    universalTargetIDs: number[];
}
export interface IKnowledgeBasesState {
    knowledgeBasesByID: ILoadable<{
        [id: number]: IKnowledgeBase;
    }>;
    getStatusesByID: {
        [id: number]: ILoadable<{}>;
    };
    form: IKbFormState;
    patchStatusesByID: {
        [kbID: number]: ILoadable<{}>;
    };
    formSubmit: ILoadable<{}>;
    deletesByID: {
        [kbID: number]: ILoadable<{}>;
    };
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
    bannerContentImage: null,
    viewType: KbViewType.GUIDE,
    sortArticles: KnowledgeBaseSortMode.MANUAL,
    sourceLocale: getCurrentLocale(),
    hasCustomPermissions: false,
    isUniversalSource: false,
    universalTargetIDs: [],
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
    description?: string;
    icon?: string;
    name?: string;
    status?: KnowledgeBaseStatus;
    sortArticles?: KnowledgeBaseSortMode;
    sourceLocale?: string;
    urlCode?: string;
    viewType?: KbViewType;
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
    bannerImage?: string;
    bannerContentImage?: string;
    sourceLocale: string;
    viewType: KbViewType;
    rootCategoryID: number;
    defaultArticleID: number | null;
    siteSectionGroup: string | null;
    siteSections: ISiteSection[];
    hasCustomPermissions: boolean;
    isUniversalSource: boolean;
    universalTargetIDs: number[];
    universalSources: IKnowledgeBaseFragment[];
}

export type IKnowledgeBaseFragment = Pick<
    IKnowledgeBase,
    "knowledgeBaseID" | "name" | "url" | "icon" | "sortArticles" | "viewType" | "siteSectionGroup" | "description"
>;

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
        getStatusesByID: {},
        form: INITIAL_KB_FORM,
        formSubmit: {
            status: LoadStatus.PENDING,
        },
        patchStatusesByID: {},
        deletesByID: {},
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
        // Fetching Multiple
        .case(KnowledgeBaseActions.getAllACs.started, (state, params) => {
            state.knowledgeBasesByID.status = LoadStatus.LOADING;
            return state;
        })
        .case(KnowledgeBaseActions.getAllACs.done, (state, payload) => {
            const normalized: { [id: number]: IKnowledgeBase } = {
                ...(state.knowledgeBasesByID.data ?? {}), // Make sure any single selects are merged in.
            };
            for (const kb of payload.result) {
                normalized[kb.knowledgeBaseID] = kb;
                state.getStatusesByID[kb.knowledgeBaseID] = { status: LoadStatus.SUCCESS };
            }
            state.knowledgeBasesByID.status = LoadStatus.SUCCESS;
            state.knowledgeBasesByID.data = normalized;
            return state;
        })
        .case(KnowledgeBaseActions.getAllACs.failed, (state, action) => {
            state.knowledgeBasesByID.error = action.error;
            return state;
        })
        // Fetching One
        .case(KnowledgeBaseActions.getSingleACs.started, (state, params) => {
            state.getStatusesByID[params.kbID] = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(KnowledgeBaseActions.getSingleACs.done, (state, action) => {
            const { params, result } = action;
            state.getStatusesByID[params.kbID] = {
                status: LoadStatus.SUCCESS,
            };
            if (!state.knowledgeBasesByID.data) {
                state.knowledgeBasesByID.data = {
                    [params.kbID]: result,
                };
            } else {
                state.knowledgeBasesByID.data[params.kbID] = result;
            }
            return state;
        })
        .case(KnowledgeBaseActions.getSingleACs.failed, (state, action) => {
            const { params, error } = action;
            state.getStatusesByID[params.kbID] = {
                status: LoadStatus.ERROR,
                error,
            };
            return state;
        })

        // Form
        .case(KnowledgeBaseActions.initFormAC, (state, payload) => {
            if (payload.kbID != null) {
                const existingKB = {
                    ...state.knowledgeBasesByID.data![payload.kbID],
                };
                state.form = existingKB;
            } else {
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
        .case(KnowledgeBaseActions.clearErrorAC, state => {
            state.formSubmit = {
                status: LoadStatus.PENDING,
            };
            return state;
        })

        // Posting back to API.
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
            state.knowledgeBasesByID.data![payload.result.knowledgeBaseID] = payload.result;
            return state;
        })
        .case(KnowledgeBaseActions.patchKB_ACs.started, (state, payload) => {
            state.formSubmit.status = LoadStatus.LOADING;
            state.patchStatusesByID[payload.knowledgeBaseID] = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(KnowledgeBaseActions.patchKB_ACs.failed, (state, payload) => {
            state.formSubmit.status = LoadStatus.ERROR;
            state.formSubmit.error = payload.error;
            state.patchStatusesByID[payload.params.knowledgeBaseID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(KnowledgeBaseActions.patchKB_ACs.done, (state, payload) => {
            state.formSubmit.status = LoadStatus.SUCCESS;
            state.knowledgeBasesByID.data![payload.result.knowledgeBaseID] = payload.result;
            state.patchStatusesByID[payload.params.knowledgeBaseID] = {
                status: LoadStatus.SUCCESS,
            };
            return state;
        })
        .case(KnowledgeBaseActions.clearPatchStatusAC, (state, { kbID }) => {
            delete state.patchStatusesByID[kbID];
            return state;
        })
        .case(KnowledgeBaseActions.clearDeleteStatus, (state, { kbID }) => {
            delete state.deletesByID[kbID];
            return state;
        })

        // Deletion
        .case(KnowledgeBaseActions.deleteKB_ACs.started, (state, payload) => {
            state.deletesByID[payload.kbID] = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(KnowledgeBaseActions.deleteKB_ACs.done, (state, payload) => {
            delete state.knowledgeBasesByID.data![payload.params.kbID];
            state.deletesByID[payload.params.kbID] = {
                status: LoadStatus.SUCCESS,
            };
            return state;
        })
        .case(KnowledgeBaseActions.deleteKB_ACs.failed, (state, payload) => {
            state.deletesByID[payload.params.kbID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        });
}

type ReducerType = KnowledgeReducer<IKnowledgeBasesState>;
