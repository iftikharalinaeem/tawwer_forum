/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import { INavigationStoreState } from "@knowledge/navigation/state/NavigationModel";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import { IStoreState } from "@knowledge/state/model";
import apiv2 from "@library/apiv2";
import Breadcrumbs from "@library/components/Breadcrumbs";
import { IActiveRecord } from "@library/components/siteNav/SiteNavNode";
import React from "react";
import { connect } from "react-redux";

interface IProps extends INavigationStoreState {
    actions: NavigationActions;
    activeRecord: IActiveRecord;
}

/**
 * Data connected breadcrumbs component for the navigation menu.
 */
export class NavigationBreadcrumbs extends React.Component<IProps> {
    public render(): React.ReactNode {
        const { activeRecord, navigationItems } = this.props;
        const recordKey = activeRecord.recordType + activeRecord.recordID;
        return (
            <Breadcrumbs forceDisplay={false}>
                {NavigationSelector.selectBreadcrumb(navigationItems, recordKey)}
            </Breadcrumbs>
        );
    }
}

function mapStateToProps(store: IStoreState) {
    return store.knowledge.navigation;
}

function mapDispatchToProps(dispatch) {
    return {
        actions: new NavigationActions(dispatch, apiv2),
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(NavigationBreadcrumbs);
