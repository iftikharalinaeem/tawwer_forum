/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions from "@library/state/ReduxActions";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";
import actionCreatorFactory from "typescript-fsa";

const createAction = actionCreatorFactory("@@loationPicker");

/**
 * Actions for the article page.
 */
export default class LocationPickerActions extends ReduxActions {
    /**
     * Initialize the state from a category.
     *
     * @param categoryID The categoryID to initialize with.
     * @param parentID The parent ID of the category.
     */
    public static initAC = createAction<{ categoryID: number; parentID: number }>("init");

    /**
     * Set the navigated category.
     *
     * @param categoryID
     */
    public static navigateAC = createAction<{ categoryID: number }>("navigate");

    /**
     * Set the selected category.
     *
     * @param categoryID
     */
    public static selectAC = createAction<{ categoryID: number }>("select");

    /**
     * Set the chosen category.
     *
     * @param categoryID
     */
    public static chooseAC = createAction<{ categoryID: number }>("choose");

    // Bind dispatch the simpler action creators instead of rewriting their function signatures.
    public selectCategory = this.bindDispatch(LocationPickerActions.selectAC);
    public chooseCategory = this.bindDispatch(LocationPickerActions.chooseAC);
    public navigateToCategory = this.bindDispatch(LocationPickerActions.navigateAC);
    public init = this.bindDispatch(LocationPickerActions.initAC);

    /**
     * Initialize location picker actions from an article.
     *
     * @param article The article to init from.
     */
    public initLocationPickerFromArticle = (article: { knowledgeCategoryID?: number | null }) => {
        if (article.knowledgeCategoryID != null) {
            const { knowledgeCategoryID } = article;
            this.initLocationPickerFromCategoryID(knowledgeCategoryID);
        }
    };

    public initLocationPickerFromCategoryID = (categoryID: number) => {
        const category = CategoryModel.selectKbCategoryFragment(this.getState(), categoryID);
        if (category) {
            this.init({ categoryID: category.knowledgeCategoryID, parentID: category.parentID });
        }
    };
}
