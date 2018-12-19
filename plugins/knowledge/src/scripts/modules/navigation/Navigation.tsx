/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import NavigationActions from "@knowledge/modules/navigation/NavigationActions";
import NavigationAdminLinks from "@knowledge/modules/navigation/NavigationAdminLinks";
import { INavigationStoreState } from "@knowledge/modules/navigation/NavigationModel";
import NavigationSelector from "@knowledge/modules/navigation/NavigationSelector";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import SiteNav from "@library/components/siteNav/SiteNav";
import { IActiveRecord } from "@library/components/siteNav/SiteNavNode";
import React from "react";
import { connect } from "react-redux";

interface IProps extends INavigationStoreState {
    actions: NavigationActions;
    activeRecord: IActiveRecord;
    collapsible: boolean;
    kbID: number;
    title?: string; // Title on top of navigation
}

/**
 * Data connect navigation component for knowledge base.
 */
export class Navigation extends React.Component<IProps> {
    /**
     * @inheritdoc
     */
    public render(): React.ReactNode {
        return (
            <SiteNav
                title={this.props.title}
                collapsible={this.props.collapsible!}
                activeRecord={this.props.activeRecord}
                bottomCTA={
                    this.props.fetchLoadable.status === LoadStatus.SUCCESS && (
                        <NavigationAdminLinks kbID={this.props.kbID} />
                    )
                }
            >
                {NavigationSelector.selectChildren(
                    this.props.navigationItems,
                    "knowledgeCategory1" /** Temporarily hardcoded until knowledge bases are wired up. */,
                )}
            </SiteNav>
        );
    }

    /**
     * Fetch navigation data when the component is mounted.
     */
    public componentDidMount() {
        return this.props.actions.getNavigationFlat({});
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
