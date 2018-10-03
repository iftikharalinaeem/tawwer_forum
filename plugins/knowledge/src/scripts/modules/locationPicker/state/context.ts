/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus, ILoadable } from "@library/@types/api";
import { IKbCategoryFragment, IKbNavigationItem } from "@knowledge/@types/api";
import { model, thunks, actions, ILocationPickerState } from "@knowledge/modules/locationPicker/state";
import { model as categoryModel } from "@knowledge/modules/categories/state";
import { IStoreState } from "@knowledge/state/model";
import { bindActionCreators } from "redux";
import { connect } from "react-redux";

interface IStateProps extends ILocationPickerState {
    locationBreadcrumb: IKbCategoryFragment[];
    navigatedCategory: IKbCategoryFragment;
    selectedCategory: IKbCategoryFragment;
}

interface IDispatchProps {
    getKbNavigation: typeof thunks.getKbNavigation;
    resetNavigation: typeof actions.resetNavigation;
    navigateToCategory: typeof thunks.navigateToCategory;
    selectCategory: typeof actions.selectCategory;
}

export interface ILocationPickerProps extends IStateProps, IDispatchProps {}

/**
 * Map in the state from the redux store.
 */
function mapStateToProps(state: IStoreState): IStateProps {
    const { navigatedCategoryID, selectedCategoryID } = state.knowledge.locationPicker;
    return {
        locationBreadcrumb: model.selectCurrentLocationBreadcrumb(state),
        navigatedCategory: categoryModel.selectKbCategoryFragment(state, navigatedCategoryID),
        selectedCategory: categoryModel.selectKbCategoryFragment(state, selectedCategoryID),
        ...state.knowledge.locationPicker,
    };
}

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch): IDispatchProps {
    const { getKbNavigation, navigateToCategory } = thunks;
    const { resetNavigation, selectCategory } = actions;
    return bindActionCreators({ getKbNavigation, resetNavigation, selectCategory, navigateToCategory }, dispatch);
}

export const withLocationPicker = connect(
    mapStateToProps,
    mapDispatchToProps,
);
