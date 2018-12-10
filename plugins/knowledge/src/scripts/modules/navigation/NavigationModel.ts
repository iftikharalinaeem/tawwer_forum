/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NavigationRecordType, IPatchFlatItem, IKbNavigationItem } from "@knowledge/@types/api";
import NavigationActions from "@knowledge/modules/navigation/NavigationActions";
import { ILoadable, INavigationItem, INavigationTreeItem, LoadStatus } from "@library/@types/api";
import { ICrumb } from "@library/components/Breadcrumbs";
import ReduxReducer from "@library/state/ReduxReducer";
import { produce } from "immer";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import { compare } from "@library/utility";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import reduceReducers from "reduce-reducers";

export interface INormalizedNavigationItem extends IKbNavigationItem {
    children: string[];
    tempName?: string;
    tempDeleted?: boolean;
    error?: {
        message: string;
    };
}
export interface INormalizedNavigationItems {
    [key: string]: INormalizedNavigationItem;
}

export interface INavigationStoreState {
    navigationItems: INormalizedNavigationItems;
    currentKnowledgeBase: ILoadable<{}>; // Needs to be replaced with an actual KB.
    submitLoadable: ILoadable<never>;
    fetchLoadable: ILoadable<never>;
    patchTransactionID: string | null;
}

/**
 * Model for managing and selection navigation data.
 */
export default class NavigationModel implements ReduxReducer<INavigationStoreState> {
    public static readonly ROOT_ID = -1;

    public static selectBreadcrumb(navItems: INormalizedNavigationItems, key: string): ICrumb[] {
        const item = navItems[key];
        if (!item) {
            return [];
        }

        const crumb = {
            name: item.name,
            url: item.url,
        };

        const parents = NavigationModel.selectBreadcrumb(
            navItems,
            NavigationRecordType.KNOWLEDGE_CATEGORY + item.parentID,
        );

        if (item.recordType === NavigationRecordType.KNOWLEDGE_CATEGORY) {
            parents.push(crumb);
        }

        return parents;
    }

    public static selectChildren(navItems: INormalizedNavigationItems, key: string): INavigationTreeItem[] {
        const item = navItems[key];
        if (!item) {
            return [];
        }
        return item.children.map(itemID => NavigationModel.selectNavTree(navItems, itemID));
    }

    public static selectNavTree(navItems: INormalizedNavigationItems, key: string): INavigationTreeItem {
        const item = navItems[key];
        return {
            ...item,
            children: item.children.map(itemID => NavigationModel.selectNavTree(navItems, itemID)),
        };
    }

    public initialState: INavigationStoreState = {
        navigationItems: {},
        currentKnowledgeBase: {
            status: LoadStatus.PENDING,
        },
        submitLoadable: {
            status: LoadStatus.PENDING,
        },
        fetchLoadable: {
            status: LoadStatus.PENDING,
        },
        patchTransactionID: null,
    };

    public reducer = (
        state: INavigationStoreState = this.initialState,
        action:
            | typeof NavigationActions.ACTION_TYPES
            | typeof CategoryActions.ACTION_TYPES
            | typeof ArticleActions.ACTION_TYPES,
    ): INavigationStoreState => {
        return produce(state, nextState => {
            return reduceReducers(this.reduceGetNav, this.reducePatchNav, this.reduceDelete, this.reduceRename)(
                nextState,
                action,
            );
        });
    };

    private reduceGetNav = (
        nextState: INavigationStoreState = this.initialState,
        action:
            | typeof NavigationActions.ACTION_TYPES
            | typeof CategoryActions.ACTION_TYPES
            | typeof ArticleActions.ACTION_TYPES,
    ): INavigationStoreState => {
        switch (action.type) {
            case NavigationActions.GET_NAVIGATION_FLAT_REQUEST:
                nextState.fetchLoadable.status = LoadStatus.LOADING;
                break;
            case NavigationActions.GET_NAVIGATION_FLAT_RESPONSE:
                nextState.navigationItems = NavigationModel.normalizeData(action.payload.data);
                nextState.fetchLoadable.status = LoadStatus.SUCCESS;
                break;
            case NavigationActions.GET_NAVIGATION_FLAT_ERROR:
                nextState.fetchLoadable.status = LoadStatus.ERROR;
                nextState.fetchLoadable.error = action.payload;
                break;
        }

        return nextState;
    };

    private reducePatchNav = (
        nextState: INavigationStoreState = this.initialState,
        action:
            | typeof NavigationActions.ACTION_TYPES
            | typeof CategoryActions.ACTION_TYPES
            | typeof ArticleActions.ACTION_TYPES,
    ): INavigationStoreState => {
        switch (action.type) {
            case NavigationActions.PATCH_NAVIGATION_FLAT_REQUEST:
                nextState.patchTransactionID = action.meta.transactionID;
                nextState.submitLoadable.status = LoadStatus.LOADING;
                break;
            case NavigationActions.PATCH_NAVIGATION_FLAT_RESPONSE:
                // Use a transaction ID so that only that last request/response in a series of patches
                // "finishes" the state updates.
                if (nextState.patchTransactionID === action.meta.transactionID) {
                    nextState.navigationItems = NavigationModel.normalizeData(action.payload.data);
                    nextState.submitLoadable.status = LoadStatus.SUCCESS;
                }
                break;
            case NavigationActions.PATCH_NAVIGATION_FLAT_ERROR:
                nextState.submitLoadable.status = LoadStatus.SUCCESS;
                nextState.submitLoadable.error = action.payload;
                break;
        }

        return nextState;
    };

    private reduceRename = (
        nextState: INavigationStoreState = this.initialState,
        action:
            | typeof NavigationActions.ACTION_TYPES
            | typeof CategoryActions.ACTION_TYPES
            | typeof ArticleActions.ACTION_TYPES,
    ): INavigationStoreState => {
        const handleRenameSuccess = (key: string, newName?: string) => {
            const item = nextState.navigationItems[key];
            if (item && newName) {
                item.name = newName!;
                delete item.error;
                delete item.tempName;
            }
        };

        const handleRenameError = (key: string, errorMessage: string) => {
            const item = nextState.navigationItems[key];
            if (item) {
                item.error = { message: errorMessage };
            }
        };

        const handleRenameRequest = (key: string, tempName?: string) => {
            const item = nextState.navigationItems[key];
            if (item && tempName) {
                item.tempName = tempName;
                delete item.error;
            }
        };

        switch (action.type) {
            case CategoryActions.PATCH_CATEGORY_REQUEST:
                handleRenameRequest(
                    NavigationRecordType.KNOWLEDGE_CATEGORY + action.meta.knowledgeCategoryID,
                    action.meta.name,
                );
                break;
            case ArticleActions.PATCH_ARTICLE_REQUEST:
                handleRenameRequest(NavigationRecordType.ARTICLE + action.meta.articleID, action.meta.name);
                break;
            case CategoryActions.PATCH_CATEGORY_ERROR:
                handleRenameError(
                    NavigationRecordType.KNOWLEDGE_CATEGORY + action.meta.knowledgeCategoryID,
                    action.payload.message,
                );
                break;
            case ArticleActions.PATCH_ARTICLE_ERROR:
                handleRenameError(NavigationRecordType.ARTICLE + action.meta.articleID, action.payload.message);
                break;
            case CategoryActions.PATCH_CATEGORY_RESPONSE:
                handleRenameSuccess(NavigationRecordType.KNOWLEDGE_CATEGORY + action.meta.knowledgeCategoryID);
                break;
            case ArticleActions.PATCH_ARTICLE_RESPONSE:
                handleRenameSuccess(NavigationRecordType.ARTICLE + action.meta.articleID);
                break;
        }
        return nextState;
    };

    private reduceDelete = (
        nextState: INavigationStoreState = this.initialState,
        action:
            | typeof NavigationActions.ACTION_TYPES
            | typeof CategoryActions.ACTION_TYPES
            | typeof ArticleActions.ACTION_TYPES,
    ): INavigationStoreState => {
        const handleDeleteRequest = (key: string) => {
            const item = nextState.navigationItems[key];
            if (item) {
                item.tempDeleted = true;
                delete item.error;

                // Remove the item from it's parent
                const parentItem = nextState.navigationItems[NavigationRecordType.KNOWLEDGE_CATEGORY + item.parentID];
                parentItem.children = parentItem.children.filter(childKey => childKey !== key);
            }
        };

        const handleDeleteSuccess = (key: string) => {
            if (nextState.navigationItems[key]) {
                delete nextState.navigationItems[key];
            }
        };

        const handleDeleteError = (key: string, errorMessage: string) => {
            const item = nextState.navigationItems[key];
            if (item) {
                item.error = { message: errorMessage };
                delete item.tempDeleted;

                // Put the item back on its parent.
                const parentItem = nextState.navigationItems[NavigationRecordType.KNOWLEDGE_CATEGORY + item.parentID];
                parentItem.children.push(key);
            }
        };

        switch (action.type) {
            case ArticleActions.PATCH_ARTICLE_STATUS_REQUEST:
                handleDeleteRequest(NavigationRecordType.ARTICLE + action.meta.articleID);
                break;
            case ArticleActions.PATCH_ARTICLE_STATUS_RESPONSE:
                handleDeleteSuccess(NavigationRecordType.ARTICLE + action.meta.articleID);
                break;
            case ArticleActions.PATCH_ARTICLE_STATUS_ERROR:
                handleDeleteError(NavigationRecordType.ARTICLE + action.meta.articleID, action.payload.message);
                break;
            case CategoryActions.DELETE_CATEGORY_REQUEST:
                handleDeleteRequest(NavigationRecordType.KNOWLEDGE_CATEGORY + action.meta.knowledgeCategoryID);
                break;
            case CategoryActions.DELETE_CATEGORY_RESPONSE:
                handleDeleteSuccess(NavigationRecordType.KNOWLEDGE_CATEGORY + action.meta.knowledgeCategoryID);
                break;
            case CategoryActions.DELETE_CATEGORY_ERROR:
                handleDeleteError(
                    NavigationRecordType.KNOWLEDGE_CATEGORY + action.meta.knowledgeCategoryID,
                    action.payload.message,
                );
                break;
        }
        return nextState;
    };

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

    public static normalizeData(data: IKbNavigationItem[]) {
        data = data.sort(this.sortNavigationItems);

        const normalizedByID: { [id: string]: INormalizedNavigationItem } = {};
        // Loop through once to generate normalizedIDs
        for (const item of data) {
            const id = item.recordType + item.recordID;

            normalizedByID[id] = {
                ...item,
                // Temporary kludge https://github.com/vanilla/knowledge/issues/425
                parentID: item.parentID || (item as any).knowledgeCategoryID,
                children: [],
            };
        }

        // Loop through again to gather the children.
        for (const [itemID, itemValue] of Object.entries(normalizedByID)) {
            if (itemValue.parentID > 0) {
                const lookupID = "knowledgeCategory" + itemValue.parentID;
                normalizedByID[lookupID].children.push(itemID);
            }
        }

        return normalizedByID;
    }

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
            return typeA === NavigationRecordType.ARTICLE ? 1 : -1;
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
