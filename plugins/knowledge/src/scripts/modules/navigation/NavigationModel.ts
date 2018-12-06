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

interface INormalizedNavigationItem extends IKbNavigationItem {
    children: string[];
}
export interface INormalizedNavigationItems {
    [key: string]: INormalizedNavigationItem;
}

export interface INavigationStoreState {
    navigationItems: INormalizedNavigationItems;
    currentKnowledgeBase: ILoadable<{}>; // Needs to be replaced with an actual KB.
    submitLoadable: ILoadable<never>;
    fetchLoadable: ILoadable<never>;
}

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
    };

    public reducer = (
        state: INavigationStoreState = this.initialState,
        action: typeof NavigationActions.ACTION_TYPES | typeof CategoryActions.ACTION_TYPES,
    ): INavigationStoreState => {
        return produce(state, nextState => {
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
                case NavigationActions.PATCH_NAVIGATION_FLAT_REQUEST:
                    nextState.submitLoadable.status = LoadStatus.LOADING;
                    break;
                case NavigationActions.PATCH_NAVIGATION_FLAT_RESPONSE:
                    nextState.navigationItems = NavigationModel.normalizeData(action.payload.data);
                    nextState.submitLoadable.status = LoadStatus.SUCCESS;
                    break;
                case NavigationActions.PATCH_NAVIGATION_FLAT_ERROR:
                    nextState.submitLoadable.status = LoadStatus.SUCCESS;
                    nextState.submitLoadable.error = action.payload;
                    break;
                case CategoryActions.PATCH_CATEGORY_REQUEST:
                    const id = NavigationRecordType.KNOWLEDGE_CATEGORY + action.meta.knowledgeCategoryID;
                    const item = nextState.navigationItems[id];
                    if (item && action.meta.name) {
                        item.name = action.meta.name;
                    }
                    break;
            }
        });
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

    private static sortNavigationItems(a: IKbNavigationItem, b: IKbNavigationItem) {
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
                return spaceship(nameA, nameB)!;
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
            return spaceship(sortA, sortB)!;
        }
    }
}

function spaceship(val1: any, val2: any) {
    if (val1 === null || val2 === null || typeof val1 != typeof val2) {
        return null;
    }
    if (typeof val1 === "string") {
        return val1.localeCompare(val2);
    } else {
        if (val1 > val2) {
            return 1;
        } else if (val1 < val2) {
            return -1;
        }
        return 0;
    }
}
