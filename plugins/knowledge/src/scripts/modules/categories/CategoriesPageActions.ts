/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions from "@library/state/ReduxActions";
import actionCreatorFactory from "typescript-fsa";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";

const createAction = actionCreatorFactory("@@categoryPage");

/**
 * Actions for the categories page.
 */
export default class CategoriesPageActions extends ReduxActions {
    public static resetAction = createAction("reset");

    public static setCategoryIDAction = createAction<number>("SET_CATEGORY_ID");

    /**
     * Reset the page state.
     */
    public reset = this.bindDispatch(CategoriesPageActions.resetAction);
    public setCategoryID = this.bindDispatch(CategoriesPageActions.setCategoryIDAction);

    private categoriesPageActions = new CategoryActions(this.dispatch, this.api, this.getState);

    /**
     * Set the active category and request the data for it to be fetched from the server.
     */
    public initForCategoryID = async (categoryID: number) => {
        this.setCategoryID(categoryID);
        await this.categoriesPageActions.getCategory({ id: categoryID });
    };
}
