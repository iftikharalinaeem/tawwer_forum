/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ArticleActions from "@knowledge/modules/article/ArticleActions";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import { KnowledgeReducer } from "@knowledge/state/model";
import { formatUrl } from "@library/utility/appUtils";
import ReduxReducer from "@library/redux/ReduxReducer";
import { produce } from "immer";
import reduceReducers from "reduce-reducers";
import { reducerWithoutInitialState } from "typescript-fsa-reducers";
import { INavigationItem, LoadStatus, IApiError, ILoadable } from "@library/@types/api/core";

export type ReducerType = KnowledgeReducer<INavigationStoreState>;

export interface IKbNavigationItem<R extends KbRecordType = KbRecordType> extends INavigationItem {
    recordType: R;
    knowledgeBaseID: number;
    children?: string[];
}

export interface IPatchFlatItem {
    parentID: number;
    recordID: number;
    sort: number | null;
    recordType: KbRecordType;
}

export interface INormalizedNavigationItem extends IKbNavigationItem {
    children: string[];
    tempName?: string;
    tempDeleted?: boolean;
    error?: {
        message: string;
    };
}
export interface INormalizedNavigationItems {
    [key: string]: INormalizedNavigationItem | undefined;
}

export interface INavigationStoreState {
    translationSourceNavItems: ILoadable<IKbNavigationItem[]>;
    navigationItems: INormalizedNavigationItems;
    navigationItemsByKbID: {
        [kbID: number]: string[];
    };
    sourceNavigationItemsByKbID: {
        [kbID: number]: string[];
    };
    submitStatus: LoadStatus;
    fetchStatusesByKbID: {
        [kbID: number]: LoadStatus;
    };
    patchTransactionID: string | null;
    patchItems: IPatchFlatItem[];
    currentError: {
        type: NavigationActionType;
        error: IApiError;
        isLoading: boolean;
    } | null;
}

export enum KbRecordType {
    CATEGORY = "knowledgeCategory",
    ARTICLE = "article",
    KB = "knowledgeBase",
}

export enum NavigationActionType {
    MOVE = "move",
    GET = "get",
    OTHER = "other",
}

/**
 * Model for managing and selection navigation data.
 */
export default class NavigationModel implements ReduxReducer<INavigationStoreState> {
    public static readonly SYNTHETIC_ROOT: INormalizedNavigationItem = {
        recordType: KbRecordType.CATEGORY,
        knowledgeBaseID: -1,
        recordID: -1,
        name: "Synthetic Root",
        url: formatUrl("/kb"),
        parentID: -2,
        sort: null,
        children: [],
    };

    public static DEFAULT_STATE: INavigationStoreState = {
        navigationItems: {
            [NavigationModel.SYNTHETIC_ROOT.recordType +
            NavigationModel.SYNTHETIC_ROOT.recordID]: NavigationModel.SYNTHETIC_ROOT,
        },
        patchItems: [],
        navigationItemsByKbID: {},
        submitStatus: LoadStatus.PENDING,
        fetchStatusesByKbID: [],
        patchTransactionID: null,
        currentError: null,
        sourceNavigationItemsByKbID: {},
        translationSourceNavItems: {
            status: LoadStatus.PENDING,
        },
    };

    public initialState = NavigationModel.DEFAULT_STATE;

    /**
     * Main reducer function for the navigation model.
     *
     * Made up of multiple sub-reduceres.
     */
    public reducer: ReducerType = (state = this.initialState, action) => {
        return produce(state, nextState => {
            return reduceReducers(
                this.reduceErrors,
                this.reduceGetNav,
                this.reducePatchNav,
                this.reduceDelete,
                this.reduceRename,
            )(nextState, action);
        });
    };

    private reduceErrors: ReducerType = reducerWithoutInitialState<INavigationStoreState>()
        .case(NavigationActions.clearErrors, state => {
            state.currentError = NavigationModel.DEFAULT_STATE.currentError;
            return state;
        })
        .case(NavigationActions.markRetryAsLoading, state => {
            if (state.currentError) {
                state.currentError.isLoading = true;
            }
            return state;
        })
        .default((nextState, action: any) => {
            if (action.type === CategoryActions.POST_CATEGORY_ERROR) {
                nextState.currentError = {
                    type: NavigationActionType.OTHER,
                    error: action.payload,
                    isLoading: false,
                };
            }

            return nextState;
        });

    /**
     * Reduce actions related to fetching navigation.
     */
    private reduceGetNav: ReducerType = reducerWithoutInitialState<INavigationStoreState>()
        .case(NavigationActions.getNavigationFlatACs.started, (state, payload) => {
            state.fetchStatusesByKbID[payload.knowledgeBaseID] = LoadStatus.LOADING;
            return state;
        })
        .case(NavigationActions.getNavigationFlatACs.done, (state, payload) => {
            const { knowledgeBaseID } = payload.params;
            const normalizedItems = NavigationModel.normalizeData(payload.result);
            state.navigationItems = {
                ...state.navigationItems,
                ...normalizedItems,
            };
            state.navigationItemsByKbID[knowledgeBaseID] = Object.keys(normalizedItems);
            state.fetchStatusesByKbID[knowledgeBaseID] = LoadStatus.SUCCESS;

            // Clean up errors
            if (state.currentError && state.currentError.type === NavigationActionType.GET) {
                state.currentError = NavigationModel.DEFAULT_STATE.currentError;
            }
            return state;
        })

        .case(NavigationActions.getNavigationFlatACs.failed, (state, payload) => {
            state.currentError = {
                type: NavigationActionType.GET,
                error: payload.error,
                isLoading: false,
            };
            state.fetchStatusesByKbID[payload.params.knowledgeBaseID] = LoadStatus.ERROR;
            return state;
        })
        .case(NavigationActions.getTranslationSourceNavigationItemsACs.started, (state, payload) => {
            state.translationSourceNavItems = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(NavigationActions.getTranslationSourceNavigationItemsACs.done, (state, payload) => {
            const { knowledgeBaseID } = payload.params;
            const normalizedItems = NavigationModel.normalizeData(payload.result);
            state.translationSourceNavItems = {
                status: LoadStatus.SUCCESS,
                ...normalizedItems,
                ...state.translationSourceNavItems,
            };

            // Clean up errors
            if (state.currentError && state.currentError.type === NavigationActionType.GET) {
                state.currentError = null;
            }
            return state;
        })
        .case(NavigationActions.getTranslationSourceNavigationItemsACs.failed, (state, payload) => {
            state.translationSourceNavItems = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        });

    private reducePatchNav: ReducerType = reducerWithoutInitialState<INavigationStoreState>()
        .case(NavigationActions.setPatchItems, (state, payload) => {
            state.patchItems = payload;
            return state;
        })
        .case(NavigationActions.patchNavigationFlatACs.started, (state, payload) => {
            state.patchTransactionID = payload.transactionID;
            state.submitStatus = LoadStatus.LOADING;
            return state;
        })
        .case(NavigationActions.patchNavigationFlatACs.done, (state, payload) => {
            const { knowledgeBaseID } = payload.params;
            if (state.patchTransactionID === payload.params.transactionID) {
                const normalizedItems = NavigationModel.normalizeData(payload.result);

                state.navigationItems = {
                    ...state.navigationItems,
                    ...normalizedItems,
                };
                state.sourceNavigationItemsByKbID[knowledgeBaseID] = Object.keys(normalizedItems);
                state.submitStatus = LoadStatus.SUCCESS;
            }

            // Clean up errors
            if (state.currentError && state.currentError.type === NavigationActionType.MOVE) {
                state.currentError = NavigationModel.DEFAULT_STATE.currentError;
            }

            return state;
        })
        .case(NavigationActions.patchNavigationFlatACs.failed, (state, payload) => {
            state.submitStatus = LoadStatus.ERROR;
            state.patchTransactionID = null;
            state.currentError = {
                type: NavigationActionType.MOVE,
                error: payload.error,
                isLoading: false,
            };
            return state;
        });

    /**
     * Reduce actions related to renaming a single item.
     */
    private reduceRename: ReducerType = (nextState = this.initialState, action) => {
        /**
         * Utility for handling successfull rename action.
         */
        const handleRenameSuccess = (key: string, newName?: string) => {
            const item = nextState.navigationItems[key];

            if (item && newName) {
                item.name = newName!;
                delete item.error;
                delete item.tempName;
            }
        };

        /**
         * Utility for handling an error while renaming an item.
         */
        const handleRenameError = (key: string, error: IApiError) => {
            const item = nextState.navigationItems[key];
            if (item) {
                delete item.tempName;
                nextState.currentError = {
                    type: NavigationActionType.OTHER,
                    error,
                    isLoading: false,
                };
            }
        };

        /**
         * Utility for handling the initialization of a rename request.
         */
        const handleRenameRequest = (key: string, tempName?: string) => {
            const item = nextState.navigationItems[key];
            if (item && tempName) {
                item.tempName = tempName;
                delete item.error;
            }
        };

        switch (action.type) {
            case CategoryActions.PATCH_CATEGORY_REQUEST:
                handleRenameRequest(KbRecordType.CATEGORY + action.meta.knowledgeCategoryID, action.meta.name);
                break;
            case ArticleActions.PATCH_ARTICLE_REQUEST:
                handleRenameRequest(KbRecordType.ARTICLE + action.meta.articleID, action.meta.name);
                break;
            case CategoryActions.PATCH_CATEGORY_ERROR:
                handleRenameError(KbRecordType.CATEGORY + action.meta.knowledgeCategoryID, action.payload);
                break;
            case ArticleActions.PATCH_ARTICLE_ERROR:
                handleRenameError(KbRecordType.ARTICLE + action.meta.articleID, action.payload);
                break;
            case CategoryActions.PATCH_CATEGORY_RESPONSE:
                handleRenameSuccess(KbRecordType.CATEGORY + action.meta.knowledgeCategoryID);
                break;
            case ArticleActions.PATCH_ARTICLE_RESPONSE:
                handleRenameSuccess(KbRecordType.ARTICLE + action.meta.articleID, action.payload.data.name);
                break;
        }
        return nextState;
    };

    /**
     * Reduce actions related to the deletion of a navigation item.
     */
    private reduceDelete: ReducerType = (nextState = this.initialState, action) => {
        /**
         * Utility for handling the initialization of a delete item request.
         *
         * - Cleans up previous error statements.
         * - Mark item as temporarily deleted.
         * - Preemptively remove the item's reference ont it's parent.
         */
        const handleDeleteRequest = (key: string) => {
            const item = nextState.navigationItems[key];
            if (item) {
                item.tempDeleted = true;
                delete item.error;

                // Remove the item from it's parent
                const parentItem = nextState.navigationItems[KbRecordType.CATEGORY + item.parentID];
                if (parentItem) {
                    parentItem.children = parentItem.children.filter(childKey => childKey !== key);
                }
            }
        };

        /**
         * Utility for handling successful server responses after a deletion.
         *
         * Fully deletes itself and removes it's parent reference.
         */
        const handleDeleteSuccess = (key: string) => {
            const item = nextState.navigationItems[key];
            if (item) {
                delete nextState.navigationItems[key];

                // Remove the item from it's parent
                const parentItem = nextState.navigationItems[KbRecordType.CATEGORY + item.parentID];
                if (parentItem) {
                    parentItem.children = parentItem.children.filter(childKey => childKey !== key);
                }
            }
        };

        /**
         * Utility for handling a failed delete action.
         *
         * - Clears the tempDeleted status.
         * - Sets the error message on the item.
         * - Puts a reference to the item back on it's parent.
         */
        const handleDeleteError = (key: string, error: IApiError) => {
            const item = nextState.navigationItems[key];
            if (item) {
                delete item.tempDeleted;
                nextState.currentError = {
                    type: NavigationActionType.OTHER,
                    error,
                    isLoading: false,
                };

                // Put the item back on its parent.
                const parentItem = nextState.navigationItems[KbRecordType.CATEGORY + item.parentID];
                if (parentItem) {
                    parentItem.children.push(key);
                }
            }
        };

        switch (action.type) {
            case ArticleActions.PATCH_ARTICLE_STATUS_REQUEST:
                handleDeleteRequest(KbRecordType.ARTICLE + action.meta.articleID);
                break;
            case ArticleActions.PATCH_ARTICLE_STATUS_RESPONSE:
                handleDeleteSuccess(KbRecordType.ARTICLE + action.meta.articleID);
                break;
            case ArticleActions.PATCH_ARTICLE_STATUS_ERROR:
                handleDeleteError(KbRecordType.ARTICLE + action.meta.articleID, action.payload);
                break;
            case CategoryActions.DELETE_CATEGORY_REQUEST:
                handleDeleteRequest(KbRecordType.CATEGORY + action.meta.knowledgeCategoryID);
                break;
            case CategoryActions.DELETE_CATEGORY_RESPONSE:
                handleDeleteSuccess(KbRecordType.CATEGORY + action.meta.knowledgeCategoryID);
                break;
            case CategoryActions.DELETE_CATEGORY_ERROR:
                handleDeleteError(KbRecordType.CATEGORY + action.meta.knowledgeCategoryID, action.payload);
                break;
        }
        return nextState;
    };

    /**
     * Function to transform the normalized nav data into an array of navigation items.
     *
     * Calculated items will have a numeric sort values and references to their parent ID.
     *
     * @param data The data to run calculations on.
     * @param lookupID The unique id of the item to calculate. It's children will also be calculated recursively.
     * @param sort The sort value of the current item. Defaults to 0.
     * @param parentID The id of the current item's parent.
     */
    public static denormalizeData(
        data: INormalizedNavigationItems,
        lookupID: string,
        sort: number = 0,
        parentID: number = -1,
    ): IPatchFlatItem[] {
        const flatPatches: IPatchFlatItem[] = [];
        const item = data[lookupID];

        if (item) {
            const { recordID, recordType } = item;
            const patchItem: IPatchFlatItem = {
                sort,
                parentID,
                recordID,
                recordType,
            };
            flatPatches.push(patchItem);

            item.children.forEach((childID, index) => {
                flatPatches.push(...this.denormalizeData(data, childID, index, item.recordID));
            });
        }

        return flatPatches;
    }

    /**
     * Normalize the data returned from the server.
     *
     * - Sort the items.
     * - Generate a unique ID for each item.
     * - Store the the item in an indexed Map.
     */
    public static normalizeData(data: IKbNavigationItem[]) {
        const normalizedByID: { [id: string]: INormalizedNavigationItem } = {
            [NavigationModel.SYNTHETIC_ROOT.recordType +
            NavigationModel.SYNTHETIC_ROOT.recordID]: NavigationModel.SYNTHETIC_ROOT,
        };
        // Loop through once to generate normalizedIDs
        for (const item of data) {
            const id = item.recordType + item.recordID;

            normalizedByID[id] = {
                ...item,
                children: [],
            };
        }

        // Loop through again to gather the children.
        for (const [itemID, itemValue] of Object.entries(normalizedByID)) {
            const lookupID = "knowledgeCategory" + itemValue.parentID;
            const parent = normalizedByID[lookupID];
            if (parent) {
                parent.children.push(itemID);
            }
        }

        return normalizedByID;
    }
}
