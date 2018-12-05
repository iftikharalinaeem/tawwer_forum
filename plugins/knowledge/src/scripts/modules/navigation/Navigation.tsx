/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import SiteNav from "@library/components/siteNav/SiteNav";
import { IStoreState } from "@knowledge/state/model";
import NavigationModel, { INavigationStoreState } from "@knowledge/modules/navigation/NavigationModel";
import NavigationActions from "@knowledge/modules/navigation/NavigationActions";
import apiv2 from "@library/apiv2";
import { connect } from "react-redux";
import { IActiveRecord } from "@library/components/siteNav/SiteNavNode";

interface IProps extends INavigationStoreState {
    actions: NavigationActions;
    activeRecord: IActiveRecord;
}

export class Navigation extends React.Component<IProps> {
    public render(): React.ReactNode {
        return (
            <SiteNav collapsible={true} activeRecord={this.props.activeRecord}>
                {NavigationModel.getChildren(this.props.navigationItems, "knowledgeCategory1")}
            </SiteNav>
        );
    }

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
)(Navigation);
