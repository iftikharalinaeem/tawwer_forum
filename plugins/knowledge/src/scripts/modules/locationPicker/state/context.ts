/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { LoadStatus } from "@library/@types/api";
import { IKbCategoryFragment, IKbNavigationItem } from "@knowledge/@types/api";
import { model, thunks, actions } from "@knowledge/modules/locationPicker/state";
import { IStoreState } from "@knowledge/state/model";
import { bindActionCreators } from "redux";
import { connect } from "react-redux";

interface IStateProps {
    locationBreadcrumb: IKbCategoryFragment[];
    currentFolderItems: IKbNavigationItem[];
    status: LoadStatus;
}

interface IDispatchProps {
    getKbNavigation: typeof thunks.getKbNavigation;
    resetNavigation: typeof actions.resetNavigation;
    navigateToCategory: typeof thunks.navigateToCategory;
    setCategory: typeof actions.setCategory;
}

export interface ILocationPickerProps extends IStateProps, IDispatchProps {}

/**
 * Map in the state from the redux store.
 */
function mapStateToProps(state: IStoreState): IStateProps {
    return {
        locationBreadcrumb: model.getCurrentLocationBreadcrumb(state),
        currentFolderItems: state.knowledge.locationPicker.currentFolderItems,
        status: state.knowledge.locationPicker.status,
    };
}

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch): IDispatchProps {
    const { getKbNavigation, navigateToCategory } = thunks;
    const { resetNavigation, setCategory } = actions;
    return bindActionCreators({ getKbNavigation, resetNavigation, setCategory, navigateToCategory }, dispatch);
}

export const withLocationPicker = connect(
    mapStateToProps,
    mapDispatchToProps,
);
