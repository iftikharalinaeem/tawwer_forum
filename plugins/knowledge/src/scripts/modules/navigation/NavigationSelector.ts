/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { INormalizedNavigationItems } from "@knowledge/modules/navigation/NavigationModel";
import { ICrumb } from "@library/components/Breadcrumbs";
import { NavigationRecordType } from "@knowledge/@types/api";
import { INavigationTreeItem } from "@library/@types/api";

export default class NavigationSelector {
    /**
     * Select the array of breadcrumbs from a set of normalized navigation data.
     *
     * @param navItems The data to select from.
     * @param rootKey The unique key of the navigation item to start the crumb from.
     */
    public static selectBreadcrumb(navItems: INormalizedNavigationItems, rootKey: string): ICrumb[] {
        const item = navItems[rootKey];
        if (!item) {
            return [];
        }

        const crumb = {
            name: item.name,
            url: item.url,
        };

        const parents = NavigationSelector.selectBreadcrumb(
            navItems,
            NavigationRecordType.KNOWLEDGE_CATEGORY + item.parentID,
        );

        if (item.recordType === NavigationRecordType.KNOWLEDGE_CATEGORY) {
            parents.push(crumb);
        }

        return parents;
    }

    /**
     * Select an array of navigation trees with a parent whose unique id is parent key.
     *
     * @param navItems The navigation data.
     * @param key The parent's unique id.
     */
    public static selectChildren(navItems: INormalizedNavigationItems, parentKey: string): INavigationTreeItem[] {
        const item = navItems[parentKey];
        if (!item) {
            return [];
        }
        return item.children.map(itemID => NavigationSelector.selectNavTree(navItems, itemID));
    }

    /**
     * Select a single navigation tree a root element whose unique id is rootKey.
     *
     * @param navItems The navigation data.
     * @param rootKey The root element's unique id.
     */
    public static selectNavTree(navItems: INormalizedNavigationItems, rootKey: string): INavigationTreeItem {
        const item = navItems[rootKey];
        if (!item) {
            throw new Error("Root element not found in navigation items.");
        }
        return {
            ...item,
            children: NavigationSelector.selectChildren(navItems, rootKey),
        };
    }
}
