/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/state/ReduxReducer";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import produce from "immer";
import { IKbCategoryFragment } from "@knowledge/@types/api";
import { IStoreState, KnowledgeReducer } from "@knowledge/state/model";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";
import { ICrumb } from "@library/components/Breadcrumbs";
import { createSelector } from "reselect";
import { INavigationTreeItem } from "@library/@types/api";
import { reducerWithoutInitialState } from "typescript-fsa-reducers";

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
    private static selectState = (state: IStoreState) => state;
    private static stateSlice = (state: IStoreState) => state.knowledge.locationPicker;
    public static selectNavigateCategoryID = (state: IStoreState) =>
        LocationPickerModel.stateSlice(state).navigatedCategoryID;
    public static selectPageContents = createSelector(
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
    public reducer: ReducerType = (state = this.initialState, action) => {
        return produce(state, nextState => this.reduceSelf(nextState, action));
    };

    public reduceSelf: ReducerType = reducerWithoutInitialState<ILocationPickerState>()
        .case(LocationPickerActions.initAC, (state, payload) => {
            const { categoryID, parentID } = payload;
            state.navigatedCategoryID = categoryID === -1 ? categoryID : parentID;
            state.selectedCategoryID = categoryID;
            state.chosenCategoryID = categoryID;
            return state;
        })
        .case(LocationPickerActions.chooseAC, (state, payload) => {
            state.chosenCategoryID = payload.categoryID;
            return state;
        })
        .case(LocationPickerActions.selectAC, (state, payload) => {
            state.selectedCategoryID = payload.categoryID;
            return state;
        })
        .case(LocationPickerActions.navigateAC, (state, payload) => {
            state.navigatedCategoryID = payload.categoryID;
            return state;
        });
}

export interface ILocationPickerState {
    navigatedCategoryID: number; // What page the user is on in the picker.
    selectedCategoryID: number; // What category is selected (still not chosen).
    chosenCategoryID: number; // What category is chosen (input closes after a selection)
}

export type ReducerType = KnowledgeReducer<ILocationPickerState>;
