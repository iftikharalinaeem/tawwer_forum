/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import {
    IKbCategoryFragment,
    IKbNavigationResponse,
    IKbNavigationRequest,
    IArticle,
    IArticleDraftContents,
} from "@knowledge/@types/api";
import apiv2 from "@library/apiv2";
import { IStoreState } from "@knowledge/state/model";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";

export interface ILPActionsProps {
    actions: LocationPickerActions;
}

/**
 * Actions for the article page.
 */
export default class LocationPickerActions extends ReduxActions {
    // Action constants.
    public static readonly NAVIGATE_TO_CATEGORY = "@@locationPicker/NAVIGATE_TO_CATEGORY";
    public static readonly SELECT_CATEGORY = "@@locationPicker/SELECT_CATEGORY";
    public static readonly CHOOSE_CATEGORY = "@@locationPicker/CHOOSE_CATEGORY";
    public static readonly INIT = "@@locationPicker/INIT";

    /**
     * Union of all possible action types in this class.
     */
    public static ACTION_TYPES:
        | ReturnType<typeof LocationPickerActions.createInitAction>
        | ReturnType<typeof LocationPickerActions.createNavigateAction>
        | ReturnType<typeof LocationPickerActions.createChooseAction>
        | ReturnType<typeof LocationPickerActions.createSelectAction>;

    public static mapDispatchToProps(dispatch: any): ILPActionsProps {
        return {
            actions: new LocationPickerActions(dispatch, apiv2),
        };
    }

    /**
     * Initialize the state from a category.
     *
     * @param categoryID The categoryID to initialize with.
     * @param parentID The parent ID of the category.
     */
    private static createInitAction(categoryID: number, parentID: number) {
        return LocationPickerActions.createAction(LocationPickerActions.INIT, { categoryID, parentID });
    }

    /**
     * Set the navigated category.
     *
     * @param categoryID
     */
    private static createNavigateAction(categoryID: number) {
        return LocationPickerActions.createAction(LocationPickerActions.NAVIGATE_TO_CATEGORY, { categoryID });
    }

    /**
     * Set the selected category.
     *
     * @param categoryID
     */
    private static createSelectAction(categoryID: number) {
        return LocationPickerActions.createAction(LocationPickerActions.SELECT_CATEGORY, { categoryID });
    }

    /**
     * Set the choosen category.
     *
     * @param categoryID
     */
    private static createChooseAction(categoryID: number) {
        return LocationPickerActions.createAction(LocationPickerActions.CHOOSE_CATEGORY, { categoryID });
    }

    // Bind dispatch the simpler action creators instead of rewriting their function signatures.
    public selectCategory = this.bindDispatch(LocationPickerActions.createSelectAction);
    public chooseCategory = this.bindDispatch(LocationPickerActions.createChooseAction);
    public init = this.bindDispatch(LocationPickerActions.createInitAction);

    /**
     * Initialize location picker actions from an article.
     *
     * @param article The article to init from.
     */
    public initLocationPickerFromArticle(article: { knowledgeCategoryID?: number | null }) {
        if (article.knowledgeCategoryID != null) {
            const { knowledgeCategoryID } = article;
            this.initLocationPickerFromCategoryID(knowledgeCategoryID);
        }
    }

    public initLocationPickerFromCategoryID(categoryID: number) {
        const category = CategoryModel.selectKbCategoryFragment(this.getState(), categoryID);
        if (category) {
            this.init(category.knowledgeCategoryID, category.parentID);
        }
    }

    /**
     * Navigate to a particular category.
     *
     * Immediately navigates in one level, then requests the data for the next level deeper.
     *
     * @param categoryID
     */
    public navigateToCategory = (categoryID: number) => {
        this.dispatch(LocationPickerActions.createNavigateAction(categoryID));
    };
}
