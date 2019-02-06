/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { IKnowledgeBase, KbViewType } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import NavigationAdminLinks from "@knowledge/navigation/subcomponents/NavigationAdminLinks";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import { IStoreState } from "@knowledge/state/model";
import { ILoadable, INavigationTreeItem, LoadStatus } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import { t } from "@library/application";
import SiteNav from "@library/components/siteNav/SiteNav";
import { IActiveRecord } from "@library/components/siteNav/SiteNavNode";
import React from "react";
import { connect } from "react-redux";

/**
 * Data connect navigation component for knowledge base.
 */
export class Navigation extends React.Component<IProps> {
    /**
     * @inheritdoc
     */
    public render(): React.ReactNode {
        const { navItems, knowledgeBase } = this.props;

        if (
            knowledgeBase.status !== LoadStatus.SUCCESS ||
            navItems.status !== LoadStatus.SUCCESS ||
            !knowledgeBase.data ||
            !navItems.data
        ) {
            return null;
        }

        const title =
            knowledgeBase.data.viewType === KbViewType.HELP && navItems.data.length > 0
                ? t("Subcategories")
                : undefined;

        return (
            <SiteNav
                title={title}
                collapsible={this.props.collapsible!}
                activeRecord={this.props.activeRecord}
                bottomCTA={<NavigationAdminLinks kbID={this.props.kbID} showDivider={navItems.data!.length > 0} />}
                onItemHover={this.preloadItem}
            >
                {navItems.data}
            </SiteNav>
        );
    }

    /**
     * Preload a navigation item from the API.
     */
    private preloadItem = (item: INavigationTreeItem) => {
        if (item.recordType === KbRecordType.ARTICLE) {
            void this.props.preloadArticle(item.recordID);
        }
    };

    /**
     * Fetch navigation data when the component is mounted.
     */
    public componentDidMount() {
        if (this.props.navItems.status === LoadStatus.PENDING) {
            void this.props.requestNavigation();
        }

        if (this.props.knowledgeBase.status === LoadStatus.PENDING) {
            this.props.requestKnowledgeBase();
        }
    }
}

interface IOwnProps {
    activeRecord: IActiveRecord;
    collapsible: boolean;
    kbID: number;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(store: IStoreState, ownProps: IOwnProps) {
    const { navigation, knowledgeBases } = store.knowledge;
    const kbsByID = knowledgeBases.knowledgeBasesByID;
    const knowledgeBase: ILoadable<IKnowledgeBase> = {
        ...kbsByID,
        data: kbsByID.status === LoadStatus.SUCCESS && kbsByID.data ? kbsByID.data[ownProps.kbID] : undefined,
    };

    const fetchLoadable = navigation.fetchLoadablesByKbID[ownProps.kbID] || { status: LoadStatus.PENDING };

    const navItems: ILoadable<INavigationTreeItem[]> = {
        ...fetchLoadable,
        data: undefined,
    };

    if (knowledgeBase.data && navItems.status === LoadStatus.SUCCESS) {
        const items = navigation.navigationItems;
        switch (knowledgeBase.data.viewType) {
            case KbViewType.GUIDE:
                {
                    // Guides always display from the root of the knowledge base..
                    const rootID = KbRecordType.CATEGORY + knowledgeBase.data.rootCategoryID;
                    navItems.data = NavigationSelector.selectChildren(items, rootID);
                }
                break;
            case KbViewType.HELP: {
                const rootID = ownProps.activeRecord.recordType + ownProps.activeRecord.recordID;
                navItems.data = NavigationSelector.selectDirectChildren(items, rootID, [KbRecordType.CATEGORY]).map(
                    item => ({ ...item, children: [] }),
                );
            }
        }
    }

    return {
        navItems,
        knowledgeBase,
    };
}

function mapDispatchToProps(dispatch, ownProps: IOwnProps) {
    const navActions = new NavigationActions(dispatch, apiv2);
    const articleActions = new ArticleActions(dispatch, apiv2);
    const kbActions = new KnowledgeBaseActions(dispatch, apiv2);
    return {
        requestNavigation: () => navActions.getNavigationFlat(ownProps.kbID),
        requestKnowledgeBase: () => kbActions.getAll(),
        preloadArticle: (articleID: number) => articleActions.fetchByID({ articleID }),
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(Navigation);
