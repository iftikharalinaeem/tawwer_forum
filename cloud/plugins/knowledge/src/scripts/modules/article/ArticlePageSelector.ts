/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createSelector } from "reselect";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import NavigationSelector, { ISortedNavItem } from "@knowledge/navigation/state/NavigationSelector";
import { KbRecordType, IKbNavigationItem } from "@knowledge/navigation/state/NavigationModel";

export default class ArticlePageSelector {
    /**
     * Select the article of the current page.
     */
    public static selectArticle = createSelector(
        [
            (state: IKnowledgeAppStoreState) => state.knowledge.articlePage,
            (state: IKnowledgeAppStoreState) => state.knowledge.articles.articlesByID,
        ],
        (pageState, articlesByID) => {
            const { articleID, articleLoadable } = pageState;
            return {
                ...articleLoadable,
                data: articleID !== null ? articlesByID[articleID] : null,
            };
        },
    );

    /**
     * Select the sort data related to the current article.
     */
    private static selectCurrentArticleSortData = createSelector(
        [ArticlePageSelector.selectArticle, NavigationSelector.selectSortedArticleData],
        (article, articleSortData): ISortedNavItem | null => {
            if (!article.data) {
                return null;
            }
            return articleSortData[article.data.articleID] || null;
        },
    );

    /**
     * Select the next article from our navigation data if possible.
     */
    public static selectNextNavArticle = createSelector(
        [
            (state: IKnowledgeAppStoreState) => state.knowledge.navigation.navigationItems,
            ArticlePageSelector.selectCurrentArticleSortData,
        ],
        (navItemsByFullID, sortData) => {
            if (sortData === null || sortData.nextID === null) {
                return null;
            }

            return (navItemsByFullID[sortData.nextID] as IKbNavigationItem<KbRecordType.ARTICLE>) || null;
        },
    );

    /**
     * Select the previous article from our navigation data if possible.
     */
    public static selectPrevNavArticle = createSelector(
        [
            (state: IKnowledgeAppStoreState) => state.knowledge.navigation.navigationItems,
            ArticlePageSelector.selectCurrentArticleSortData,
        ],
        (navItemsByFullID, sortData) => {
            if (sortData === null || sortData.prevID === null) {
                return null;
            }

            return (navItemsByFullID[sortData.prevID] as IKbNavigationItem<KbRecordType.ARTICLE>) || null;
        },
    );
}
