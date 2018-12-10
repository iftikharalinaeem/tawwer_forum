/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { INormalizedNavigationItems } from "@knowledge/modules/navigation/NavigationModel";
import { ICrumb } from "@library/components/Breadcrumbs";
import { NavigationRecordType } from "@knowledge/@types/api";
import { INavigationTreeItem } from "@library/@types/api";

export default class NavigationSelector {
    public static selectBreadcrumb(navItems: INormalizedNavigationItems, key: string): ICrumb[] {
        const item = navItems[key];
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

    public static selectChildren(navItems: INormalizedNavigationItems, key: string): INavigationTreeItem[] {
        const item = navItems[key];
        if (!item) {
            return [];
        }
        return item.children.map(itemID => NavigationSelector.selectNavTree(navItems, itemID));
    }

    public static selectNavTree(navItems: INormalizedNavigationItems, key: string): INavigationTreeItem {
        const item = navItems[key];
        return {
            ...item,
            children: item.children.map(itemID => NavigationSelector.selectNavTree(navItems, itemID)),
        };
    }
}
