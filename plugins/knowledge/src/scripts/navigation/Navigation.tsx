/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { IKnowledgeBase, KbViewType } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import ArticleActions, { useArticleActions } from "@knowledge/modules/article/ArticleActions";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import NavigationAdminLinks from "@knowledge/navigation/subcomponents/NavigationAdminLinks";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import apiv2 from "@library/apiv2";
import { formatUrl, getSiteSection, t } from "@library/utility/appUtils";
import SiteNav from "@library/navigation/SiteNav";
import { IActiveRecord } from "@library/navigation/SiteNavNode";
import React, { useCallback, useEffect, useMemo, useState } from "react";
import { connect } from "react-redux";
import { ILoadable, INavigationTreeItem, LoadStatus } from "@library/@types/api/core";
import { getCurrentLocale } from "@vanilla/i18n";
import { NavigationPlaceholder } from "@knowledge/navigation/NavigationPlaceholder";
import { DropDownPanelNav } from "@vanilla/library/src/scripts/flyouts/panelNav/DropDownPanelNav";
import { useArticleList } from "@knowledge/modules/article/ArticleModel";

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
                props.preloadArticle(item.recordID);
            }
        },
        [props],
    );

    const isHelpCenter = props.knowledgeBase.data?.viewType === KbViewType.HELP;
    const isArticleInHelpCenter = props.activeRecord.recordType === "article" && isHelpCenter;

    const queryParams = {
        knowledgeCategoryID: props.knowledgeCategoryID,
        siteSectionGroup: getSiteSection().sectionGroup === "vanilla" ? undefined : getSiteSection().sectionGroup,
        locale: getSiteSection().contentLocale,
    };

    const currentCategoryNav = isArticleInHelpCenter
        ? useCurrentCategoryNav(queryParams, props.activeRecord.recordID)
        : navItems.data;

    /**
     * Fetch navigation data when the component is mounted.
     */
    useEffect(() => {
        if (props.navItems.status === LoadStatus.PENDING) {
            void props.requestNavigation();
        }
    }, [props, props.navItems.status]);

    useEffect(() => {
        if (props.knowledgeBase.status === LoadStatus.PENDING) {
            props.requestKnowledgeBase();
        }
    }, [props, props.knowledgeBase.status]);

    if (
        !knowledgeBase.data ||
        !navItems.data ||
        knowledgeBase.status === LoadStatus.LOADING ||
        navItems.status === LoadStatus.LOADING ||
        !currentCategoryNav
    ) {
        return <NavigationPlaceholder />;
    }

    const hasTitle = isHelpCenter && currentCategoryNav.length > 0;
    const clickableCategoryLabels = knowledgeBase.data.viewType === KbViewType.GUIDE;
    const title = hasTitle ? props.knowledgeCategoryName : undefined;

    if (props.inHamburger) {
        const adminLinks = (
            <NavigationAdminLinks
                inHamburger={props.inHamburger}
                knowledgeBase={knowledgeBase.data}
                showDivider={true}
            />
        );

        if (currentCategoryNav.length > 0) {
            return (
                <DropDownPanelNav
                    activeRecord={props.activeRecord}
                    title={title ?? knowledgeBase.data.name}
                    navItems={currentCategoryNav}
                    isNestable={props.collapsible}
                    afterNavSections={adminLinks}
                />
            );
        } else {
            return adminLinks;
        }
    } else {
        return (
            <SiteNav
                title={title}
                hiddenTitle={hasTitle}
                collapsible={props.collapsible}
                activeRecord={props.activeRecord}
                bottomCTA={
                    <NavigationAdminLinks
                        knowledgeBase={knowledgeBase.data}
                        showDivider={currentCategoryNav.length > 0}
                    />
                }
                onItemHover={preloadItem}
                clickableCategoryLabels={clickableCategoryLabels}
            >
                {currentCategoryNav}
            </SiteNav>
        );
    }
}

interface IOwnProps {
    activeRecord: IActiveRecord;
    knowledgeCategoryID?: number | null;
    collapsible: boolean;
    kbID: number;
    inHamburger?: boolean;
    knowledgeCategoryName?: string | undefined;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(store: IKnowledgeAppStoreState, ownProps: IOwnProps) {
    const { navigation, knowledgeBases, articles } = store.knowledge;

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
        preloadArticle: (articleID: number) => articleActions.fetchByID({ articleID, locale: getCurrentLocale() }),
    };
}

function useCurrentCategoryNav(queryParams, articleID) {
    const articles = useArticleList(queryParams, true);
    const { data, status } = articles;
    const articleList = articles.data?.body;
    return useMemo(() => {
        if (articleList) {
            let currentArticle = articleList.find(article => article.recordID === articleID);
            let currentArticleIndex = currentArticle ? articleList.indexOf(currentArticle) : 1;
            const maxArticles = articleList.length > 10;
            let filteredArticles = maxArticles
                ? articleList.filter((article, index) => {
                      let startingPoint = currentArticleIndex <= 5 ? 0 : currentArticleIndex - 5;
                      let endingPoint = currentArticleIndex <= 5 ? 9 : currentArticleIndex + 4;

                      if (index >= startingPoint && index <= endingPoint) {
                          return article;
                      }
                  })
                : articleList;

            let items = filteredArticles.map(article => {
                return {
                    name: article.name,
                    url: article.url,
                    recordID: article.recordID,
                    parentID: queryParams.knowledgeCategoryID,
                    sort: null,
                    recordType: "article",
                    children: [],
                };
            });

            items.push({
                name: "View All",
                url: formatUrl(`/kb/categories/${queryParams.knowledgeCategoryID}`),
                recordID: 1,
                parentID: 1,
                sort: null,
                recordType: "link",
                children: [],
            });

            const navTreeItems: INavigationTreeItem[] = items.map(article => {
                return article;
            });

            return navTreeItems;
        }
    }, [data, status]);
}

export default connect(mapStateToProps, mapDispatchToProps)(Navigation);
