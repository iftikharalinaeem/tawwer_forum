/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticle } from "@knowledge/@types/api";
import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";

/**
 * Actions for the categories page.
 */
export default class CategoriesPageActions extends ReduxActions {
    public static readonly GET_ARTICLES_REQUEST = "@@kbCategoriesPage/GET_ARTICLES_REQUEST";
    public static readonly GET_ARTICLES_RESPONSE = "@@kbCategoriesPage/GET_ARTICLES_RESPONSE";
    public static readonly GET_ARTICLES_ERROR = "@@kbCategoriesPage/GET_ARTICLES_ERROR";

    public static readonly RESET = "@@kbCategoriesPage/RESET";

    public static ACTION_TYPES:
        | ActionsUnion<typeof CategoriesPageActions.getArticlesACs>
        | ReturnType<typeof CategoriesPageActions.createResetAction>;

    // Raw actions for getting a knowledge category
    private static getArticlesACs = ReduxActions.generateApiActionCreators(
        CategoriesPageActions.GET_ARTICLES_REQUEST,
        CategoriesPageActions.GET_ARTICLES_RESPONSE,
        CategoriesPageActions.GET_ARTICLES_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IArticle[],
        {},
    );

    /**
     * Create a reset action
     */
    private static createResetAction() {
        return CategoriesPageActions.createAction(CategoriesPageActions.RESET);
    }

    /**
     * Reset the page state.
     */
    public reset = this.bindDispatch(CategoriesPageActions.createResetAction);

    public getArticles(id: number) {
        return this.dispatchApi("get", `/articles?knowledgeCategoryID=${id}`, CategoriesPageActions.getArticlesACs, {});
    }
}
