/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbNavigationItem } from "@knowledge/@types/api";
import { LoadStatus, ILoadable } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import produce from "immer";

export interface ILocationPickerState {
    selectedCategoryID: number;
    navigatedCategoryID: number;
    chosenCategoryID: number;
    items: ILoadable<IKbNavigationItem[]>;
}

/**
 * Reducer for the article page.
 */
export default class LocationPickerReducer extends ReduxReducer<ILocationPickerState> {
    public initialState: ILocationPickerState = {
        selectedCategoryID: 1,
        navigatedCategoryID: 1,
        chosenCategoryID: 1,
        items: {
            status: LoadStatus.PENDING,
        },
    };

    public reducer = (
        state = this.initialState,
        action: typeof LocationPickerActions.ACTION_TYPES,
    ): ILocationPickerState => {
        return produce(state, draft => {
            switch (action.type) {
                case LocationPickerActions.GET_KB_NAVIGATION_REQUEST:
                    draft.items.status = LoadStatus.LOADING;
                    break;
                case LocationPickerActions.GET_KB_NAVIGATION_RESPONSE:
                    draft.items.status = LoadStatus.SUCCESS;
                    draft.items.data = action.payload.data;
                    break;
                case LocationPickerActions.GET_KB_NAVIGATION_ERROR:
                    break;
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
                    draft.navigatedCategoryID = action.payload.category.parentID;
                    draft.selectedCategoryID = action.payload.category.knowledgeCategoryID;
                    draft.chosenCategoryID = action.payload.category.knowledgeCategoryID;
                case LocationPickerActions.RESET:
                    draft.navigatedCategoryID = this.initialState.chosenCategoryID;
                    draft.selectedCategoryID = draft.chosenCategoryID;
                    break;
            }
        });
    };
}
