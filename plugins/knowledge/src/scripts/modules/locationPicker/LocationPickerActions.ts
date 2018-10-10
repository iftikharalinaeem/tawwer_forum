/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import { IKbCategoryFragment, IKbNavigationResponse, IKbNavigationRequest } from "@knowledge/@types/api";

/**
 * Actions for the article page.
 */
export default class LocationPickerActions extends ReduxActions {
    // Action constants.
    public static readonly GET_KB_NAVIGATION_REQUEST = "@@locationPicker/GET_KB_NAVIGATION_REQUEST";
    public static readonly GET_KB_NAVIGATION_RESPONSE = "@@locationPicker/GET_KB_NAVIGATION_RESPONSE";
    public static readonly GET_KB_NAVIGATION_ERROR = "@@locationPicker/GET_KB_NAVIGATION_ERROR";
    public static readonly NAVIGATE_TO_CATEGORY = "@@locationPicker/NAVIGATE_TO_CATEGORY";
    public static readonly SELECT_CATEGORY = "@@locationPicker/SELECT_CATEGORY";
    public static readonly CHOOSE_CATEGORY = "@@locationPicker/CHOOSE_CATEGORY";
    public static readonly RESET = "@@locationPicker/RESET";
    public static readonly INIT = "@@locationPicker/INIT";

    /**
     * Union of all possible action types in this class.
     */
    public static ACTION_TYPES:
        | ActionsUnion<typeof LocationPickerActions.getNavigationActionCreators>
        | ReturnType<typeof LocationPickerActions.createResetAction>
        | ReturnType<typeof LocationPickerActions.createInitAction>
        | ReturnType<typeof LocationPickerActions.createNavigateAction>
        | ReturnType<typeof LocationPickerActions.createChooseAction>
        | ReturnType<typeof LocationPickerActions.createSelectAction>;

    /**
     * Action creators for the /kb/navigation get request.
     */
    private static getNavigationActionCreators = ReduxActions.generateApiActionCreators(
        LocationPickerActions.GET_KB_NAVIGATION_REQUEST,
        LocationPickerActions.GET_KB_NAVIGATION_RESPONSE,
        LocationPickerActions.GET_KB_NAVIGATION_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IKbNavigationResponse,
        {} as IKbNavigationRequest,
    );

    private static createResetAction() {
        return LocationPickerActions.createAction(LocationPickerActions.RESET);
    }

    /**
     * Initialize the state from a category.
     *
     * @param category A category fragment.
     */
    private static createInitAction(category: IKbCategoryFragment) {
        return LocationPickerActions.createAction(LocationPickerActions.INIT, { category });
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
    public reset = this.bindDispatch(LocationPickerActions.createResetAction);
    public init = this.bindDispatch(LocationPickerActions.createInitAction);

    /**
     * Get location data from the server.
     *
     * @param options
     */
    public getKbNavigation = (options: IKbNavigationRequest) => {
        return this.dispatchApi(
            "get",
            `/knowledge-navigation?knowledgeCategoryID=${options.knowledgeCategoryID}`,
            LocationPickerActions.getNavigationActionCreators,
            options,
        );
    };

    /**
     * Navigate to a particular category.
     *
     * Immediately navigates in one level, then requests the data for the next level deeper.
     *
     * @param categoryID
     */
    public navigateToCategory = (categoryID: number) => {
        this.dispatch(LocationPickerActions.createNavigateAction(categoryID));
        this.dispatch(this.getKbNavigation({ knowledgeCategoryID: categoryID }));
    };
}
