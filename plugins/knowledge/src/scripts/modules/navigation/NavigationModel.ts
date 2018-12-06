/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxReducer from "@library/state/ReduxReducer";
import { ILoadable, LoadStatus, INavigationTreeItem, INavigationItem } from "@library/@types/api";
import { produce } from "immer";
import NavigationActions from "@knowledge/modules/navigation/NavigationActions";
import { ICrumb } from "@library/components/Breadcrumbs";
import { NavigationRecordType } from "@knowledge/@types/api";

interface INormalizedNavigationItem extends INavigationItem {
    children: string[];
}
interface INavItems {
    [key: string]: INormalizedNavigationItem;
}

export interface INavigationStoreState {
    navigationItems: INavItems;
    currentKnowledgeBase: ILoadable<{}>; // Needs to be replaced with an actual KB.
    submitLoadable: ILoadable<never>;
    fetchLoadable: ILoadable<never>;
}

export default class NavigationModel implements ReduxReducer<INavigationStoreState> {
    public static readonly ROOT_ID = -1;

    public static selectBreadcrumb(navItems: INavItems, key: string): ICrumb[] {
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

    public static selectChildren(navItems: INavItems, key: string): INavigationTreeItem[] {
        const item = navItems[key];
        if (!item) {
            return [];
        }
        return item.children.map(itemID => NavigationModel.selectNavTree(navItems, itemID));
    }

    public static selectNavTree(navItems: INavItems, key: string): INavigationTreeItem {
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
        action: typeof NavigationActions.ACTION_TYPES,
    ): INavigationStoreState => {
        return produce(state, nextState => {
            switch (action.type) {
                case NavigationActions.GET_NAVIGATION_FLAT_REQUEST:
                    nextState.fetchLoadable.status = LoadStatus.ERROR;
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
                    nextState.submitLoadable.status = LoadStatus.SUCCESS;
                    break;
                case NavigationActions.PATCH_NAVIGATION_FLAT_ERROR:
                    nextState.submitLoadable.error = action.payload;
                    break;
            }
        });
    };

    public static normalizeData(data: INavigationItem[]) {
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
}
