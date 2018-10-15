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

interface ILocationPickerState {
    selectedCategoryID: number;
    navigatedCategoryID: number;
    chosenCategoryID: number;
}

export interface ILPConnectedData extends ILocationPickerState {
    locationBreadcrumb: IKbCategoryFragment[];
    navigatedCategory: IKbCategoryFragment;
    navigatedCategoryContents: IKbNavigationItem[];
    selectedCategory: IKbCategoryFragment;
    choosenCategory: IKbCategoryFragment;
}

export interface ILPConnectedData extends ILocationPickerState {
    locationBreadcrumb: IKbCategoryFragment[];
    navigatedCategory: IKbCategoryFragment;
    navigatedCategoryContents: IKbNavigationItem[];
    selectedCategory: IKbCategoryFragment;
    choosenCategory: IKbCategoryFragment;
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
        return {
            locationBreadcrumb: CategoryModel.selectKbCategoryBreadcrumb(state, chosenCategoryID),
            navigatedCategory: CategoryModel.selectKbCategoryFragment(state, navigatedCategoryID),
            navigatedCategoryContents: CategoryModel.selectMixedRecordTree(state, navigatedCategoryID).children!,
            selectedCategory: CategoryModel.selectKbCategoryFragment(state, selectedCategoryID),
            choosenCategory: CategoryModel.selectKbCategoryFragment(state, chosenCategoryID),
            ...state.knowledge.locationPicker,
        };
    }

    public initialState: ILocationPickerState = {
        selectedCategoryID: 1,
        navigatedCategoryID: 1,
        chosenCategoryID: 1,
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
