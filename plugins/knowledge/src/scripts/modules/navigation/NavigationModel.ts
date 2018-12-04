/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxReducer from "@library/state/ReduxReducer";
import { ILoadable, LoadStatus, INavigationTreeItem, INavigationItem } from "@library/@types/api";
import { produce } from "immer";
import NavigationActions from "@knowledge/modules/navigation/NavigationActions";

interface INormalizedNavigationItem extends INavigationItem {
    children: string[];
}
interface INavItems {
    [key: string]: INormalizedNavigationItem;
}

export interface INavigationStoreState {
    navigationItems: INavItems;
    currentKnowledgeBase: ILoadable<{}>;
    submitLoadable: ILoadable<never>;
    fetchLoadable: ILoadable<never>;
}

export default class NavigationModel implements ReduxReducer<INavigationStoreState> {
    public static getChildren(navItems: INavItems, id: string): INavigationTreeItem[] {
        const item = navItems[id];
        if (!item) {
            return [];
        }
        return item.children.map(itemID => NavigationModel.getNavTree(navItems, itemID));
    }

    public static getNavTree(navItems: INavItems, id: string): INavigationTreeItem {
        const item = navItems[id];
        return {
            ...item,
            children: item.children.map(itemID => NavigationModel.getNavTree(navItems, itemID)),
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
                    nextState.navigationItems = this.normalizeData(action.payload.data);
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

    private normalizeData(data: INavigationItem[]) {
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
