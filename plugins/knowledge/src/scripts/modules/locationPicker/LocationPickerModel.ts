/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/state/ReduxReducer";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import produce from "immer";
import { IKbCategory, IKbCategoryFragment } from "@knowledge/@types/api";
import { IStoreState } from "@knowledge/state/model";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";
import { ICrumb } from "@library/components/Breadcrumbs";
import { createSelector } from "reselect";
import { INavigationTreeItem } from "@library/@types/api";

export interface ILocationPickerState {
    navigatedCategoryID: number; // What page the user is on in the picker.
    selectedCategoryID: number; // What category is selected (still not chosen).
    chosenCategoryID: number; // What category is chosen (input closes after a selection)
}

export interface ILPConnectedData extends ILocationPickerState {
    pageContents: INavigationTreeItem[];
    locationBreadcrumb: ICrumb[] | null;
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
            pageContents: LocationPickerModel.selectPageContents(state),
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

    private static selectState = (state: IStoreState) => state;
    private static stateSlice = (state: IStoreState) => state.knowledge.locationPicker;
    private static selectNavigateCategoryID = (state: IStoreState) =>
        LocationPickerModel.stateSlice(state).navigatedCategoryID;
    private static selectPageContents = createSelector(
        LocationPickerModel.selectState,
        LocationPickerModel.selectNavigateCategoryID,
        (state, navID) => {
            return CategoryModel.selectMixedRecordTree(state, navID).children!;
        },
    );

    public initialState: ILocationPickerState = {
        selectedCategoryID: -1,
        navigatedCategoryID: -1,
        chosenCategoryID: -1,
    };

    /**
     * @inheritDoc
     */
    public reducer = (
        state = this.initialState,
        action: typeof LocationPickerActions.ACTION_TYPES | typeof CategoryActions.ACTION_TYPES,
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
                case LocationPickerActions.INIT: {
                    const { categoryID, parentID } = action.payload;
                    draft.navigatedCategoryID = categoryID === -1 ? categoryID : parentID;
                    draft.selectedCategoryID = categoryID;
                    draft.chosenCategoryID = categoryID;
                    break;
                }
            }
        });
    };
}
