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
import { organize } from "@library/components/icons";
import { t } from "@library/application";
import { OrganizeCategoriesRoute } from "@knowledge/routes/pageRoutes";

interface IProps extends INavigationStoreState {
    actions: NavigationActions;
    activeRecord: IActiveRecord;
    collapsible: boolean;
}

/**
 * Data connect navigation component for knowledge base.
 */
export class Navigation extends React.Component<IProps> {
    public render(): React.ReactNode {
        return (
            <div className="navigation">
                <SiteNav collapsible={this.props.collapsible!} activeRecord={this.props.activeRecord}>
                    {NavigationModel.selectChildren(
                        this.props.navigationItems,
                        "knowledgeCategory1" /** Temporarily hardcoded until knowledge bases are wired up. */,
                    )}
                </SiteNav>
                <hr className="navigation-divider" />
                <div className="navigation-cta">
                    {organize()}
                    <OrganizeCategoriesRoute.Link data={{ kbID: 1 }}>
                        {t("Organize Categories")}
                    </OrganizeCategoriesRoute.Link>
                </div>
            </div>
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
