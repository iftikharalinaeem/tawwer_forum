/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import KnowledgeBaseModel, { IKnowledgeBase, KbViewType } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import NavigationModel, {
    KbRecordType,
    INormalizedNavigationItems,
    IKbNavigationItem,
} from "@knowledge/navigation/state/NavigationModel";
import { createSelector } from "reselect";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { INavigationTreeItem, ILinkListData, ILinkGroup } from "@library/@types/api/core";

export interface ISortedNavItem {
    ownID: string;
    prevID: string | null;
    nextID: string | null;
}

export default class NavigationSelector {
    public static selectNavigationItems = createSelector(
        [
            (state: IKnowledgeAppStoreState) => state.knowledge.navigation.navigationItems,
            KnowledgeBaseModel.selectKnowledgeBasesAsNavItems,
        ],
        (navItems, kbNavItems): INormalizedNavigationItems => {
            const items = {
                ...navItems,
            };

            for (const kbNavItem of kbNavItems) {
                items[kbNavItem.recordType + kbNavItem.recordID] = {
                    ...kbNavItem,
                    children: [],
                };
            }
            return items;
        },
    );

    /**
     * Select a flat list of sorted article positons.
     *
     * This is useful for guides where we want to navigate left and right in the tree while walking up and down categories.
     */
    public static selectSortedArticleData = createSelector(
        [
            (state: IKnowledgeAppStoreState) => state.knowledge.navigation.navigationItems,
            (state: IKnowledgeAppStoreState) => state.knowledge.knowledgeBases.knowledgeBasesByID,
        ],
        (navItems, knowledgeBasesByID) => {
            const root = NavigationModel.SYNTHETIC_ROOT;
            const sortedTree = NavigationSelector.selectNavTree(navItems, root.recordType + root.recordID);
            const articles = NavigationSelector.selectAllRecursiveArticles(sortedTree);
            const result: { [articleID: number]: ISortedNavItem } = {};
            for (const [index, article] of articles.entries()) {
                const knowledgeBase = knowledgeBasesByID.data
                    ? knowledgeBasesByID.data[article.knowledgeBaseID]
                    : undefined;
                if (!knowledgeBase) {
                    continue;
                }

                if (knowledgeBase.viewType !== KbViewType.GUIDE) {
                    // Only guide type knowledge bases have this type of navigaiton.
                    continue;
                }

                const prev = articles[index - 1];
                const next = articles[index + 1];

                result[article.recordID] = {
                    ownID: article.recordType + article.recordID,
                    nextID: next ? next.recordType + next.recordID : null,
                    prevID: prev ? prev.recordType + prev.recordID : null,
                };
            }
            return result;
        },
    );

    /**
     * Select all articles recursively out of a navigation into a flat ordered array.
     */
    private static selectAllRecursiveArticles(navItems: INavigationTreeItem) {
        const results: IKbNavigationItem[] = [];
        for (const item of navItems.children) {
            switch (item.recordType) {
                case KbRecordType.ARTICLE:
                    // Typescript still doesn't feel this is narrowed enough but at this point it's definitely the article.
                    results.push((item as unknown) as IKbNavigationItem<KbRecordType.ARTICLE>);
                    break;
                case KbRecordType.CATEGORY:
                    results.push(...NavigationSelector.selectAllRecursiveArticles(item));
                    break;
            }
        }

        return results;
    }

    /**
     * Select the array of breadcrumbs from a set of normalized navigation data.
     *
     * @param navItems The data to select from.
     * @param rootKey The unique key of the navigation item to start the crumb from.
     */
    public static selectBreadcrumb(navItems: INormalizedNavigationItems, rootKey: string): ICrumb[] {
        const item = navItems[rootKey];
        if (!item || item === NavigationModel.SYNTHETIC_ROOT) {
            return [];
        }

        const crumb = {
            name: item.name,
            url: item.url,
        };

        const parents = NavigationSelector.selectBreadcrumb(navItems, KbRecordType.CATEGORY + item.parentID);

        if (item.recordType === KbRecordType.CATEGORY) {
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
     * @param recordTypes The record types to filter to.
     */
    public static selectDirectChildren(
        navItems: INormalizedNavigationItems,
        parentKey: string,
        recordTypes: KbRecordType[] = [KbRecordType.ARTICLE, KbRecordType.CATEGORY],
    ): IKbNavigationItem[] {
        const rootItem = navItems[parentKey];
        if (!rootItem) {
            return [];
        }

        return (
            rootItem.children
                .map(itemID => navItems[itemID])
                // Cast needed because typescript doesn't infer anything from undefined check + filter.
                .filter(item => item !== undefined && recordTypes.includes(item.recordType)) as IKbNavigationItem[]
        );
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

    /**
     * Select a single category, by its ID, from an INormalizedNavigationItems collection.
     * @param knowledgeCategoryID - Unique ID of the knowledge category to select.
     * @param navItems - An collection of INormalizedNavigationItems elements, indexed by their type and ID.
     */
    public static selectCategory(knowledgeCategoryID: number, navItems: INormalizedNavigationItems) {
        const key = `knowledgeCategory${knowledgeCategoryID}`;
        return navItems[key] as IKbNavigationItem<KbRecordType.CATEGORY> | undefined;
    }

    public static selectHelpCenterHome(navItems: INormalizedNavigationItems, knowledgeBase: IKnowledgeBase) {
        const rootNavItemID = KbRecordType.CATEGORY + knowledgeBase.rootCategoryID;
        const treeData = NavigationSelector.selectNavTree(navItems, rootNavItemID);
        const data: ILinkListData = {
            groups: [],
            ungroupedItems: [],
        };

        // Help center data only iterates through 2 levels of nav data.
        for (const record of treeData.children) {
            switch (record.recordType) {
                case KbRecordType.ARTICLE: {
                    const { children, ...items } = record;
                    data.ungroupedItems.push(items as NavArticle);
                    break;
                }
                case KbRecordType.CATEGORY: {
                    const { children, ...category } = record;
                    const group: ILinkGroup = {
                        category: category as NavCategory,
                        items: [],
                    };
                    for (const child of children) {
                        if (child.recordType === KbRecordType.ARTICLE) {
                            const { children: unused, ...article } = child;
                            group.items.push(article as NavArticle);
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

export type NavArticle = IKbNavigationItem<KbRecordType.ARTICLE>;
export type NavCategory = IKbNavigationItem<KbRecordType.CATEGORY>;
