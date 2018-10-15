/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/state/ReduxReducer";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import produce from "immer";
import { IKbCategoryFragment, IKbNavigationItem } from "@knowledge/@types/api";
import { IStoreState } from "@knowledge/state/model";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";

export interface ILocationPickerState {
    navigatedCategoryID: number; // What page the user is on in the picker.
    selectedCategoryID: number; // What category is selected (still not chosen).
    chosenCategoryID: number; // What category is chosen (input closes after a selection)
}

export interface ILPConnectedData extends ILocationPickerState {
    pageContents: IKbNavigationItem[];
    locationBreadcrumb: IKbCategoryFragment[] | null;
    navigatedCategory: IKbCategoryFragment | null; // What page the user is on in the picker.
    selectedCategory: IKbCategoryFragment | null; // What category is selected (still not chosen).
    choosenCategory: IKbCategoryFragment | null; // What category is chosen (input closes after a selection)
}

/**
 * Reducer for the article page.
 */
export default class LocationPickerModel extends ReduxReducer<ILocationPickerState> {
    /**
     * Static utility for mapping store state to the location picker props.
     */
    public static mapStateToProps(state: IStoreState): ILPConnectedData {
        const { navigatedCategoryID, selectedCategoryID, chosenCategoryID } = state.knowledge.locationPicker;

        // Category ID's less than 0 (eg. -1) represents the true root of the forum.
        return {
            pageContents: CategoryModel.selectMixedRecordTree(state, navigatedCategoryID).children!,
            locationBreadcrumb:
                chosenCategoryID > 0 ? CategoryModel.selectKbCategoryBreadcrumb(state, chosenCategoryID) : null,
            navigatedCategory:
                navigatedCategoryID > 0 ? CategoryModel.selectKbCategoryFragment(state, navigatedCategoryID) : null,
            selectedCategory:
                selectedCategoryID > 0 ? CategoryModel.selectKbCategoryFragment(state, selectedCategoryID) : null,
            choosenCategory:
                chosenCategoryID > 0 ? CategoryModel.selectKbCategoryFragment(state, chosenCategoryID) : null,
            ...state.knowledge.locationPicker,
        };
    }

    public initialState: ILocationPickerState = {
        selectedCategoryID: -1,
        navigatedCategoryID: -1,
        chosenCategoryID: -1,
    };

    public reducer = (
        state = this.initialState,
        action: typeof LocationPickerActions.ACTION_TYPES,
    ): ILocationPickerState => {
        return produce(state, draft => {
            switch (action.type) {
                case LocationPickerActions.NAVIGATE_TO_CATEGORY:
                    draft.navigatedCategoryID = action.payload.categoryID;
                    break;
                case LocationPickerActions.SELECT_CATEGORY:
                    draft.selectedCategoryID = action.payload.categoryID;
                    break;
                case LocationPickerActions.CHOOSE_CATEGORY:
                    draft.chosenCategoryID = action.payload.categoryID;
                    break;
                case LocationPickerActions.INIT:
                    const { initialCategory } = action.payload;

                    // If we are in a top level category.
                    if (initialCategory.parentID === -1) {
                        draft.navigatedCategoryID = initialCategory.knowledgeCategoryID;
                        draft.selectedCategoryID = initialCategory.knowledgeCategoryID;
                        draft.chosenCategoryID = initialCategory.knowledgeCategoryID;
                    } else {
                        draft.navigatedCategoryID = initialCategory.parentID;
                        draft.selectedCategoryID = initialCategory.knowledgeCategoryID;
                        draft.chosenCategoryID = initialCategory.knowledgeCategoryID;
                    }
            }
        });
    };
}
