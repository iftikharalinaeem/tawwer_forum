/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { produce, Draft } from "immer";
import { LoadStatus } from "@library/@types/api";
import * as actions from "./actions";
import * as constants from "./constants";
import { ILocationPickerState } from "./types";

export const initialState: ILocationPickerState = {
    selectedCategoryID: 1,
    navigatedCategoryID: 1,
    items: {
        status: LoadStatus.PENDING,
    },
};

/**
 * Reducer for the locaton picker page.
 *
 * @param state - The currrent state.
 * @param action - The action being taken.
 */
export default function editorPageReducer(
    state: ILocationPickerState = initialState,
    action: actions.ActionTypes,
): ILocationPickerState {
    return produce(state, (draft: Draft<ILocationPickerState>) => {
        switch (action.type) {
            case constants.GET_KB_NAVIGATION_REQUEST:
                draft.items.status = LoadStatus.LOADING;
                break;
            case constants.GET_KB_NAVIGATION_RESPONSE:
                draft.items.status = LoadStatus.SUCCESS;
                draft.items.data = action.payload.data;
                break;
            case constants.GET_KB_NAVIGATION_ERROR:
                break;
            case constants.SET_NAVIGATED_CATEGORY:
                draft.navigatedCategoryID = action.payload.categoryID;
                break;
            case constants.SELECT_CATEGORY:
                draft.selectedCategoryID = action.payload.categoryID;
                break;
        }
    });
}
