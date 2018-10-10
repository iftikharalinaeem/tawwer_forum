/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { connect } from "react-redux";
import apiv2 from "@library/apiv2";
import { IKbCategoryFragment } from "@knowledge/@types/api";
import { model as categoryModel } from "@knowledge/modules/categories/state";
import { IStoreState } from "@knowledge/state/model";
import { ILocationPickerState } from "@knowledge/modules/locationPicker/LocationPickerReducer";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";

interface IStateProps extends ILocationPickerState {
    locationBreadcrumb: IKbCategoryFragment[];
    navigatedCategory: IKbCategoryFragment;
    selectedCategory: IKbCategoryFragment;
    choosenCategory: IKbCategoryFragment;
}

interface IDispatchProps {
    actions: LocationPickerActions;
}

export interface ILocationPickerProps extends IStateProps, IDispatchProps {}

/**
 * Map in the state from the redux store.
 */
function mapStateToProps(state: IStoreState): IStateProps {
    const { navigatedCategoryID, selectedCategoryID, chosenCategoryID } = state.knowledge.locationPicker;
    return {
        locationBreadcrumb: categoryModel.selectKbCategoryBreadcrumb(state, chosenCategoryID),
        navigatedCategory: categoryModel.selectKbCategoryFragment(state, navigatedCategoryID),
        selectedCategory: categoryModel.selectKbCategoryFragment(state, selectedCategoryID),
        choosenCategory: categoryModel.selectKbCategoryFragment(state, chosenCategoryID),
        ...state.knowledge.locationPicker,
    };
}

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch): IDispatchProps {
    return {
        actions: new LocationPickerActions(dispatch, apiv2),
    };
}

export const withLocationPicker = connect(
    mapStateToProps,
    mapDispatchToProps,
);
