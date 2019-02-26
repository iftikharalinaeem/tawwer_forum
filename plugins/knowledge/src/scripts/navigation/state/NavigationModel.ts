/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ArticleActions from "@knowledge/modules/article/ArticleActions";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import { KnowledgeReducer } from "@knowledge/state/model";
import { ILoadable, INavigationItem, LoadStatus, IApiError } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import { compare } from "@library/utility";
import { produce } from "immer";
import reduceReducers from "reduce-reducers";
import { reducerWithoutInitialState } from "typescript-fsa-reducers";
import { formatUrl } from "@library/application";
import { IArticle } from "@knowledge/@types/api";
import { Navigation } from "@knowledge/navigation/Navigation";

export enum KbRecordType {
    CATEGORY = "knowledgeCategory",
    ARTICLE = "article",
    KB = "knowledgeBase",
}

export enum NavigationActionType {
    MOVE = "move",
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
        navigationItemsByKbID: {},
        submitStatus: LoadStatus.PENDING,
        fetchStatusesByKbID: [],
        patchTransactionID: null,
        currentError: null,
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
                this.reduceGetNav,
                this.reducePatchNav,
                this.reduceDelete,
                this.reduceRename,
                this.reduceAdd,
                this.reduceEdit,
            )(nextState, action);
        });
    };

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
            return state;
        })
        .case(NavigationActions.getNavigationFlatACs.failed, (state, payload) => {
            state.currentError = {
                type: NavigationActionType.OTHER,
                error: payload.error,
            };
            state.fetchStatusesByKbID[payload.params.knowledgeBaseID] = LoadStatus.ERROR;
            return state;
        });

    private reducePatchNav: ReducerType = reducerWithoutInitialState<INavigationStoreState>()
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
                state.navigationItemsByKbID[knowledgeBaseID] = Object.keys(normalizedItems);
                state.submitStatus = LoadStatus.SUCCESS;
            }
            return state;
        })
        .case(NavigationActions.patchNavigationFlatACs.failed, (state, payload) => {
            state.submitStatus = LoadStatus.SUCCESS;
            state.currentError = {
                type: NavigationActionType.MOVE,
                error: payload.error,
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
        const handleRenameError = (key: string, errorMessage: string) => {
            const item = nextState.navigationItems[key];
            if (item) {
                item.error = { message: errorMessage };
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
                handleRenameError(KbRecordType.CATEGORY + action.meta.knowledgeCategoryID, action.payload.message);
                break;
            case ArticleActions.PATCH_ARTICLE_ERROR:
                handleRenameError(KbRecordType.ARTICLE + action.meta.articleID, action.payload.message);
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
     * Reduce actions related to adding new items.
     */
    private reduceAdd: ReducerType = (nextState = this.initialState, action) => {
        switch (action.type) {
            case ArticleActions.PATCH_ARTICLE_RESPONSE:
                const patchedArticle = action.payload.data;
                const patchedArticleID = KbRecordType.ARTICLE + patchedArticle.articleID;
                const patchedArticleParentStringID = KbRecordType.CATEGORY + patchedArticle.knowledgeCategoryID;
                const originalNavigationItem = nextState.navigationItems[patchedArticleID];

                // If moved out of the original category, remove it from that navigation item's children.
                if (originalNavigationItem) {
                    const { parentID } = originalNavigationItem;
                    if (parentID && parentID !== patchedArticle.knowledgeCategoryID) {
                        const originalArticleParentStringID = KbRecordType.CATEGORY + parentID;
                        if (nextState.navigationItems[originalArticleParentStringID]) {
                            nextState.navigationItems[
                                originalArticleParentStringID
                            ]!.children = nextState.navigationItems[originalArticleParentStringID]!.children.filter(
                                item => item !== patchedArticleID,
                            );
                        }
                    }
                }

                nextState.navigationItems[patchedArticleID] = {
                    name: patchedArticle.name,
                    url: patchedArticle.url,
                    parentID: patchedArticle.knowledgeCategoryID!,
                    recordID: patchedArticle.articleID,
                    sort: patchedArticle.sort,
                    knowledgeBaseID: patchedArticle.knowledgeBaseID,
                    recordType: KbRecordType.ARTICLE,
                    children: [],
                };

                // Add to the children of its parent, if it isn't already there.
                if (!originalNavigationItem || originalNavigationItem.parentID !== patchedArticle.knowledgeCategoryID) {
                    const patchedParentItem = nextState.navigationItems[patchedArticleParentStringID];
                    if (patchedParentItem) {
                        patchedParentItem.children.push(patchedArticleID);
                        NavigationModel.sortItemChildren(nextState.navigationItems, patchedArticleParentStringID);
                    }
                }
                break;
            case ArticleActions.POST_ARTICLE_RESPONSE:
                const article = action.payload.data;
                const stringID = KbRecordType.ARTICLE + article.articleID;
                const parentStringID = KbRecordType.CATEGORY + article.knowledgeCategoryID;
                nextState.navigationItems[stringID] = NavigationModel.normalizeArticle(article);
                nextState.navigationItems = NavigationModel.pushArticle(
                    stringID,
                    parentStringID,
                    nextState.navigationItems,
                );
                break;
        }
        switch (action.type) {
            case CategoryActions.POST_CATEGORY_RESPONSE:
                const category = action.payload.data;
                const stringID = KbRecordType.CATEGORY + category.knowledgeCategoryID;
                const parentStringID = KbRecordType.CATEGORY + category.parentID;
                nextState.navigationItems[stringID] = {
                    name: category.name,
                    url: category.url,
                    parentID: category.parentID,
                    recordID: category.knowledgeCategoryID,
                    sort: category.sort,
                    knowledgeBaseID: category.knowledgeBaseID,
                    recordType: KbRecordType.CATEGORY,
                    children: [],
                };

                const parentItem = nextState.navigationItems[parentStringID];
                if (parentItem) {
                    parentItem.children.push(stringID);
                    NavigationModel.sortItemChildren(nextState.navigationItems, parentStringID);
                }
                break;
        }
        return nextState;
    };

    /**
     * Reduce actions related to editing existing items.
     */
    private reduceEdit: ReducerType = (nextState = this.initialState, action) => {
        switch (action.type) {
            case ArticleActions.PATCH_ARTICLE_RESPONSE:
                const patchedArticle = action.payload.data;
                const patchedArticleID = KbRecordType.ARTICLE + patchedArticle.articleID;
                const patchedArticleParentStringID = KbRecordType.CATEGORY + patchedArticle.knowledgeCategoryID;
                const originalNavigationItem = nextState.navigationItems[patchedArticleID];

                // If moved out of the original category, remove it from that navigation item's children.
                if (originalNavigationItem) {
                    const { parentID } = originalNavigationItem;
                    if (parentID && parentID !== patchedArticle.knowledgeCategoryID) {
                        const originalArticleParentStringID = KbRecordType.CATEGORY + parentID;
                        if (nextState.navigationItems[originalArticleParentStringID]) {
                            nextState.navigationItems[
                                originalArticleParentStringID
                            ]!.children = nextState.navigationItems[originalArticleParentStringID]!.children.filter(
                                item => item !== patchedArticleID,
                            );
                        }
                    }
                }

                nextState.navigationItems[patchedArticleID] = NavigationModel.normalizeArticle(patchedArticle);

                // Add to the children of its parent, if it isn't already there.
                if (!originalNavigationItem || originalNavigationItem.parentID !== patchedArticle.knowledgeCategoryID) {
                    nextState.navigationItems = NavigationModel.pushArticle(
                        patchedArticleID,
                        patchedArticleParentStringID,
                        nextState.navigationItems,
                        false,
                    );
                }
                NavigationModel.sortItemChildren(nextState.navigationItems, patchedArticleParentStringID);
                break;
        }
        return nextState;
    };

    private static pushArticle(
        articleID: string,
        parentID: string,
        navigationItems: INormalizedNavigationItems,
        doSort: boolean = true,
    ): INormalizedNavigationItems {
        const parentItem = navigationItems[parentID];
        if (parentItem) {
            parentItem.children.push(articleID);
            if (doSort) {
                NavigationModel.sortItemChildren(navigationItems, parentID);
            }
        }
        return navigationItems;
    }

    private static normalizeArticle = (article: IArticle): INormalizedNavigationItem => {
        return {
            name: article.name,
            url: article.url,
            parentID: article.knowledgeCategoryID!,
            recordID: article.articleID,
            sort: article.sort,
            knowledgeBaseID: article.knowledgeBaseID,
            recordType: KbRecordType.ARTICLE,
            children: [],
        };
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
        const handleDeleteError = (key: string, errorMessage: string) => {
            const item = nextState.navigationItems[key];
            if (item) {
                item.error = { message: errorMessage };
                delete item.tempDeleted;

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
                handleDeleteError(KbRecordType.ARTICLE + action.meta.articleID, action.payload.message);
                break;
            case CategoryActions.DELETE_CATEGORY_REQUEST:
                handleDeleteRequest(KbRecordType.CATEGORY + action.meta.knowledgeCategoryID);
                break;
            case CategoryActions.DELETE_CATEGORY_RESPONSE:
                handleDeleteSuccess(KbRecordType.CATEGORY + action.meta.knowledgeCategoryID);
                break;
            case CategoryActions.DELETE_CATEGORY_ERROR:
                handleDeleteError(KbRecordType.CATEGORY + action.meta.knowledgeCategoryID, action.payload.message);
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
        data = data.sort(this.sortNavigationItems);

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

    /**
     * Sort a navigation item's children.
     *
     * @param navItems The keyed items to pull from.
     * @param idToSort The item whos children you want to sort.
     */
    private static sortItemChildren(navItems: INormalizedNavigationItems, idToSort: string) {
        const item = navItems[idToSort];
        if (!item) {
            return;
        }

        const newChildren = item.children
            .map(childID => navItems[childID]) // Map to actual items.
            .sort(this.sortNavigationItems) // Sort
            .map(child => child!.recordType + child!.recordID); // Back to IDs
        item.children = newChildren;
    }

    /**
     * Given two navigation items, compare them and determine their sort order.
     */
    private static sortNavigationItems(a: INormalizedNavigationItem, b: INormalizedNavigationItem) {
        const sortA = a.sort;
        const sortB = b.sort;
        if (sortA === sortB) {
            // Same sort weight? We must go deeper.
            const typeA = a.recordType;
            const typeB = b.recordType;
            if (typeA === typeB) {
                // Same record type? Sort by name.
                const nameA = a.name;
                const nameB = b.name;
                return compare(nameA, nameB)!;
            }
            // Articles rank lower than categories.
            return typeA === KbRecordType.ARTICLE ? 1 : -1;
        } else if (sortA === null) {
            // If they're not the same, and A is null, then B must not be null. B should rank higher.
            return 1;
        } else if (sortB === null) {
            // If they're not the same, and B is null, then A must not be null. A should rank higher.
            return -1;
        } else {
            // We have two non-null, non-equal sort weights. Compare them using the combined-comparison operator.
            return compare(sortA, sortB)!;
        }
    }
}

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
    navigationItems: INormalizedNavigationItems;
    navigationItemsByKbID: {
        [kbID: number]: string[];
    };
    submitStatus: LoadStatus;
    fetchStatusesByKbID: {
        [kbID: number]: LoadStatus;
    };
    patchTransactionID: string | null;
    currentError: {
        type: NavigationActionType;
        error: IApiError;
    } | null;
}
