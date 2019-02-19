/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createSelector } from "reselect";
import { IStoreState } from "@knowledge/state/model";
import NavigationSelector, { ISortedNavItem } from "@knowledge/navigation/state/NavigationSelector";
import { KbRecordType, IKbNavigationItem } from "@knowledge/navigation/state/NavigationModel";

export default class ArticlePageSelector {
    public static selectArticle = createSelector(
        [
            (state: IStoreState) => state.knowledge.articlePage,
            (state: IStoreState) => state.knowledge.articles.articlesByID,
        ],
        (pageState, articlesByID) => {
            const { articleID, articleLoadable } = pageState;
            return {
                ...articleLoadable,
                data: articleID !== null ? articlesByID[articleID] : null,
            };
        },
    );

    private static selectCurrentArticleSortData = createSelector(
        [ArticlePageSelector.selectArticle, NavigationSelector.selectSortedArticleData],
        (article, articleSortData): ISortedNavItem | null => {
            if (!article.data) {
                return null;
            }
            return articleSortData[article.data.articleID] || null;
        },
    );

    public static selectNextArticle = createSelector(
        [
            (state: IStoreState) => state.knowledge.navigation.navigationItems,
            ArticlePageSelector.selectCurrentArticleSortData,
        ],
        (navItemsByFullID, sortData) => {
            if (sortData === null || sortData.nextID === null) {
                return null;
            }

            return (navItemsByFullID[sortData.nextID] as IKbNavigationItem<KbRecordType.ARTICLE>) || null;
        },
    );

    public static selectPrevArticle = createSelector(
        [
            (state: IStoreState) => state.knowledge.navigation.navigationItems,
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
