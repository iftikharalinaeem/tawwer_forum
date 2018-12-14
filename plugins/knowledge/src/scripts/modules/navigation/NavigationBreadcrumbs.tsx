/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import SiteNav from "@library/components/siteNav/SiteNav";
import { IStoreState } from "@knowledge/state/model";
import NavigationSelector from "@knowledge/modules/navigation/NavigationSelector";
import NavigationActions from "@knowledge/modules/navigation/NavigationActions";
import apiv2 from "@library/apiv2";
import { connect } from "react-redux";
import { IActiveRecord } from "@library/components/siteNav/SiteNavNode";
import Breadcrumbs from "@library/components/Breadcrumbs";
import { INavigationStoreState } from "@knowledge/modules/navigation/NavigationModel";

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
        return <Breadcrumbs>{NavigationSelector.selectBreadcrumb(navigationItems, recordKey)}</Breadcrumbs>;
    }

    /**
     * Fetch navigation data when the component is mounted.
     */
    public componentDidMount() {
        void this.props.actions.getNavigationFlat({});
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
