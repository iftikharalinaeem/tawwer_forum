/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { IKnowledgeBase, KbViewType } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import ArticleActions, {useArticleActions} from "@knowledge/modules/article/ArticleActions";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import NavigationAdminLinks from "@knowledge/navigation/subcomponents/NavigationAdminLinks";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import apiv2 from "@library/apiv2";
import {getSiteSection, t} from "@library/utility/appUtils";
import SiteNav from "@library/navigation/SiteNav";
import { IActiveRecord } from "@library/navigation/SiteNavNode";
import React, {useCallback, useEffect, useState} from "react";
import { connect } from "react-redux";
import { LoadStatus, INavigationTreeItem, ILoadable } from "@library/@types/api/core";
import { getCurrentLocale } from "@vanilla/i18n";
import { NavigationPlaceholder } from "@knowledge/navigation/NavigationPlaceholder";
import { DropDownPanelNav } from "@vanilla/library/src/scripts/flyouts/panelNav/DropDownPanelNav";
import {useArticleList} from "@knowledge/modules/article/ArticleModel";

/**
 * Data connect navigation component for knowledge base.
 */
export function Navigation(props: IProps) {
    const { navItems, knowledgeBase } = props;
    const [article, setArticle] = useState();
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

    // useEffect(() => {
    //     if (props.activeRecord.recordType === KbRecordType.ARTICLE) {
    //         props.preloadArticle(props.activeRecord.recordID).then(value => setArticle(value));
    //     }
    // }, [props]);

    if (props.activeRecord.recordType === KbRecordType.ARTICLE) {
        let knowledgeCategoryID = 66;
        const queryParams = {
            knowledgeCategoryID: 66, // need a way to find kbcategoryID.
            siteSectionGroup: getSiteSection().sectionGroup === "vanilla" ? undefined : getSiteSection().sectionGroup,
            locale: getSiteSection().contentLocale,
            limit: 10,
        };
        const articleList = useArticleList(queryParams, true);

        if (articleList.data?.body) {
            const articlesInThisCategory = articleList.data.body.map((article)=> {
                return{
                    name: article.name,
                    url: article.url,
                    recordID: article.recordID,
                    parentID: 66,
                    sort: null,
                    recordType: 'article',
                    children: [],
                }
            });
            

            const navTreeItems:INavigationTreeItem[] = [
                {
                    name: "Category Name",
                    url: "https://dev.vanilla.localhost/kb/categories/",
                    parentID: 66,
                    recordID: 1,
                    sort: null,
                    recordType: "category",
                    children: articlesInThisCategory
                }
                ];

         //   navItems.data.push(navTreeItems);

        }

    }
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
            navItems.status === LoadStatus.LOADING
        ) {
            return <NavigationPlaceholder />;
        }

        const hasTitle = knowledgeBase.data.viewType === KbViewType.HELP && navItems.data.length > 0;
        const clickableCategoryLabels = knowledgeBase.data.viewType === KbViewType.GUIDE;
        const title = hasTitle ? t("Subcategories") : undefined;


        if (props.inHamburger) {
            const adminLinks = (
                <NavigationAdminLinks
                    inHamburger={props.inHamburger}
                    knowledgeBase={knowledgeBase.data}
                    showDivider={true}
                />
            );

            if (navItems.data.length > 0) {
                return (
                    <DropDownPanelNav
                        activeRecord={props.activeRecord}
                        title={title ?? knowledgeBase.data.name}
                        navItems={navItems.data}
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
                        <NavigationAdminLinks knowledgeBase={knowledgeBase.data} showDivider={navItems.data.length > 0} />
                    }
                    onItemHover={preloadItem}
                    clickableCategoryLabels={clickableCategoryLabels}
                >
                    {navItems.data}
                </SiteNav>
            );
        }
    }

interface IOwnProps {
    activeRecord: IActiveRecord;
    collapsible: boolean;
    kbID: number;
    inHamburger?: boolean;
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
        preloadArticle: (articleID: number) => articleActions.fetchByID({ articleID, locale: getCurrentLocale() }),
    };
}

export default connect(mapStateToProps, mapDispatchToProps)(Navigation);
