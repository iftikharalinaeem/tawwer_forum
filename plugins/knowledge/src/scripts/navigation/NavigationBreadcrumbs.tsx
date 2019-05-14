/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import { INavigationStoreState } from "@knowledge/navigation/state/NavigationModel";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import { IStoreState } from "@knowledge/state/model";
import apiv2 from "@library/apiv2";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import { IActiveRecord } from "@library/navigation/SiteNavNode";
import React from "react";
import { connect } from "react-redux";
import { t, formatUrl } from "@library/utility/appUtils";
import { IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";

interface IProps extends INavigationStoreState {
    actions: NavigationActions;
    activeRecord: IActiveRecord;
    knowledgeBases: {
        [id: number]: IKnowledgeBase;
    };
}

/**
 * Data connected breadcrumbs component for the navigation menu.
 */
export class NavigationBreadcrumbs extends React.Component<IProps> {
    public render(): React.ReactNode {
        const { activeRecord, knowledgeBases, navigationItems } = this.props;
        const recordKey = activeRecord.recordType + activeRecord.recordID;
        const recordBreadcrumbs = NavigationSelector.selectBreadcrumb(navigationItems, recordKey);

        if (Object.keys(knowledgeBases).length > 1) {
            recordBreadcrumbs.unshift({
                name: t("Help"),
                url: formatUrl("/kb"),
            });
        }

        recordBreadcrumbs.unshift({
            name: t("Home"),
            url: formatUrl("/"),
        });

        return <Breadcrumbs forceDisplay={false}>{recordBreadcrumbs}</Breadcrumbs>;
    }
}

function mapStateToProps(store: IStoreState) {
    return {
        ...store.knowledge.navigation,
        knowledgeBases: store.knowledge.knowledgeBases.knowledgeBasesByID.data || {},
    };
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
