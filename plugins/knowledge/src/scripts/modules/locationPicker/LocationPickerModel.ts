/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/state/ReduxReducer";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import produce from "immer";

export interface ILocationPickerState {
    selectedCategoryID: number;
    navigatedCategoryID: number;
    chosenCategoryID: number;
    initialCategoryID: number | null;
}

/**
 * Reducer for the article page.
 */
export default class LocationPickerModel extends ReduxReducer<ILocationPickerState> {
    public initialState: ILocationPickerState = {
        selectedCategoryID: 1,
        navigatedCategoryID: 1,
        chosenCategoryID: 1,
        initialCategoryID: null,
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
                    draft.navigatedCategoryID = action.payload.initialCategory.parentID;
                    draft.selectedCategoryID = action.payload.initialCategory.knowledgeCategoryID;
                    draft.chosenCategoryID = action.payload.initialCategory.knowledgeCategoryID;
            }
        });
    };
}
