/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import {
    IKbNavigationItem,
    INormalizedNavigationItems,
    NavigationRecordType,
} from "@knowledge/navigation/state/NavigationModel";
import { INavigationTreeItem } from "@library/@types/api";
import { ICrumb } from "@library/components/Breadcrumbs";

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
     * Select an array of direct child categories.
     *
     * @param navItems The navigation data.
     * @param key The parent's unique id.
     */
    public static selectSubcategories(navItems: INormalizedNavigationItems, parentKey: string): INavigationTreeItem[] {
        const rootItem = navItems[parentKey];
        if (!rootItem) {
            return [];
        }

        return rootItem.children
            .map(itemID => navItems[itemID])
            .filter(item => item !== undefined && item.recordType === NavigationRecordType.KNOWLEDGE_CATEGORY)
            .map(item => {
                return {
                    ...item!,
                    children: [],
                } as INavigationTreeItem;
            });
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

    public static selectHelpCenterNome(navItems: INormalizedNavigationItems, knowledgeBase: IKnowledgeBase) {
        const rootNavItemID = NavigationRecordType.KNOWLEDGE_CATEGORY + knowledgeBase.rootCategoryID;
        const treeData = NavigationSelector.selectNavTree(navItems, rootNavItemID);
        const data: IHelpData = {
            groups: [],
            ungroupedArticles: [],
        };

        // Help center data only iterates through 2 levels of nav data.
        for (const record of treeData.children) {
            switch (record.recordType) {
                case NavigationRecordType.ARTICLE: {
                    const { children, ...article } = record;
                    data.ungroupedArticles.push(article as NavArticle);
                    break;
                }
                case NavigationRecordType.KNOWLEDGE_CATEGORY: {
                    const { children, ...category } = record;
                    const group: IHelpGroup = {
                        category: category as NavCategory,
                        articles: [],
                    };
                    for (const child of children) {
                        if (child.recordType === NavigationRecordType.ARTICLE) {
                            const { children: unused, ...article } = child;
                            group.articles.push(article as NavArticle);
                        }
                    }
                    data.groups.push(group);
                    break;
                }
            }
        }
        return data;
    }
}

export type NavArticle = IKbNavigationItem<NavigationRecordType.ARTICLE>;
export type NavCategory = IKbNavigationItem<NavigationRecordType.KNOWLEDGE_CATEGORY>;

export interface IHelpGroup {
    category: NavCategory;
    articles: NavArticle[];
}

export interface IHelpData {
    groups: IHelpGroup[];
    ungroupedArticles: NavArticle[];
}
