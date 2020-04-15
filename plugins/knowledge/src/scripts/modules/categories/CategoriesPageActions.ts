/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions from "@library/redux/ReduxActions";
import actionCreatorFactory from "typescript-fsa";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";

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

    private categoriesActions = new CategoryActions(this.dispatch, this.api, this.getState);
    private navigationActions = new NavigationActions(this.dispatch, this.api, this.getState);

    /**
     * Set the active category and request the data for it to be fetched from the server.
     */
    public initForCategoryID = async (categoryID: number) => {
        this.setCategoryID(categoryID);
        const category = await this.categoriesActions.getCategory({ id: categoryID, includeSubcategories: true });
        if (category) {
            await this.navigationActions.getNavigationFlat(category.knowledgeBaseID);
        }
        return category;
    };
}
