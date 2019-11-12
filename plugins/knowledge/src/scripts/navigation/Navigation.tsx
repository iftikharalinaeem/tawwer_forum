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
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import apiv2 from "@library/apiv2";
import { t } from "@library/utility/appUtils";
import SiteNav from "@library/navigation/SiteNav";
import { IActiveRecord } from "@library/navigation/SiteNavNode";
import React, { useCallback, useEffect } from "react";
import { connect } from "react-redux";
import { LoadStatus, INavigationTreeItem, ILoadable } from "@library/@types/api/core";

/**
 * Data connect navigation component for knowledge base.
 */
export function Navigation(props: IProps) {
    const { navItems, knowledgeBase } = props;

    /**
     * Preload a navigation item from the API.
     */
    const preloadItem = useCallback(
        (item: INavigationTreeItem) => {
            if (item.recordType === KbRecordType.ARTICLE) {
                void props.preloadArticle(item.recordID);
            }
        },
        [props.preloadArticle],
    );

    /**
     * Fetch navigation data when the component is mounted.
     */
    useEffect(() => {
        if (props.navItems.status === LoadStatus.PENDING) {
            void props.requestNavigation();
        }
    }, [props.navItems.status]);

    useEffect(() => {
        if (props.knowledgeBase.status === LoadStatus.PENDING) {
            props.requestKnowledgeBase();
        }
    }, [props.knowledgeBase.status]);

    if (!knowledgeBase.data || !navItems.data) {
        return null;
    }

    const hasTitle = knowledgeBase.data.viewType === KbViewType.HELP && navItems.data.length > 0;
    const clickableCategoryLabels = knowledgeBase.data.viewType === KbViewType.GUIDE;
    const title = hasTitle ? t("Subcategories") : undefined;

    return (
        <SiteNav
            title={title}
            hiddenTitle={hasTitle}
            collapsible={props.collapsible!}
            activeRecord={props.activeRecord}
            bottomCTA={
                <NavigationAdminLinks
                    knowledgeBase={props.knowledgeBase.data!}
                    showDivider={navItems.data!.length > 0}
                />
            }
            onItemHover={preloadItem}
            clickableCategoryLabels={clickableCategoryLabels}
        >
            {navItems.data}
        </SiteNav>
    );
}

interface IOwnProps {
    activeRecord: IActiveRecord;
    collapsible: boolean;
    kbID: number;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(store: IKnowledgeAppStoreState, ownProps: IOwnProps) {
    const { navigation, knowledgeBases } = store.knowledge;
    const kbsByID = knowledgeBases.knowledgeBasesByID;
    const knowledgeBase: ILoadable<IKnowledgeBase> = {
        ...kbsByID,
        data: kbsByID.status === LoadStatus.SUCCESS && kbsByID.data ? kbsByID.data[ownProps.kbID] : undefined,
    };

    const fetchStatus = navigation.fetchStatusesByKbID[ownProps.kbID] || LoadStatus.PENDING;

    const navItems: ILoadable<INavigationTreeItem[]> = {
        status: fetchStatus,
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
                const isHelpCenterRoot =
                    ownProps.activeRecord.recordType === KbRecordType.CATEGORY &&
                    knowledgeBase.data.rootCategoryID === ownProps.activeRecord.recordID;
                if (isHelpCenterRoot) {
                    // Avoid seeing any navigation for a root-level help center category (i.e. hide top-level categories).
                    navItems.data = [];
                } else {
                    const rootID = ownProps.activeRecord.recordType + ownProps.activeRecord.recordID;
                    navItems.data = NavigationSelector.selectDirectChildren(items, rootID, [
                        KbRecordType.CATEGORY,
                    ]).map(item => ({ ...item, children: [] }));
                }
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

export default connect(mapStateToProps, mapDispatchToProps)(Navigation);
