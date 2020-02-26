/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import HelpCenterNavigation from "@knowledge/helpCenter/components/HelpCenterNavigation";
import { IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";
import { EditorRoute, HomeRoute } from "@knowledge/routes/pageRoutes";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { ILinkListData, ILoadable, LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import Permission from "@library/features/users/Permission";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Container from "@library/layout/components/Container";
import WidgetContainer from "@library/layout/components/WidgetContainer";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import Loader from "@library/loaders/Loader";
import DocumentTitle from "@library/routing/DocumentTitle";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t, getSiteSection, formatUrl } from "@library/utility/appUtils";
import classNames from "classnames";
import React, { useEffect } from "react";
import { connect } from "react-redux";
import TitleBar from "@library/headers/TitleBar";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import { ComposeIcon } from "@library/icons/common";
import { FallbackBackUrlSetter } from "@library/routing/links/BackRoutingProvider";
import { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";
import Banner from "@library/banner/Banner";
import NavigationAdminLinks from "@knowledge/navigation/subcomponents/NavigationAdminLinks";
import { ArticlesWidget } from "@knowledge/widgets/ArticlesWidget";
import { layoutVariables } from "@vanilla/library/src/scripts/layout/panelLayoutStyles";
import { ISearchRequestBody } from "@knowledge/@types/api/search";
import { useArticleList } from "@knowledge/modules/article/ArticleModel";

/**
 * Component representing the the full home page of a help center.
 */
export function HelpCenterHome(props: IProps) {
    const { knowledgeBase, status, data, error, rootCategoryUrl, requestData } = props;
    const { bannerImage, bannerContentImage, knowledgeBaseID, rootCategoryID, description } = knowledgeBase;

    useEffect(() => {
        if (status === LoadStatus.PENDING) {
            void requestData();
        }
    }, [status, requestData, knowledgeBaseID]);

    const widgetParams: ISearchRequestBody = {
        featured: true,
        siteSectionGroup: getSiteSection().sectionGroup === "vanilla" ? undefined : getSiteSection().sectionGroup,
        locale: getSiteSection().contentLocale,
        knowledgeBaseID: knowledgeBase.knowledgeBaseID,
    };
    const articleList = useArticleList(widgetParams);

    if (
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(status) ||
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(articleList.status)
    ) {
        return <Loader />;
    }

    if (status === LoadStatus.ERROR) {
        return <KbErrorPage error={props.error} />;
    }

    if (knowledgeBase.countArticles === 0) {
        return (
            <KbErrorPage
                defaultError={DefaultKbError.NO_ARTICLES}
                knowledgeBaseID={knowledgeBaseID}
                knowledgeCategoryID={knowledgeBase.rootCategoryID}
            />
        );
    }

    const bannerAction = (
        <Permission permission="articles.add">
            <LinkAsButton
                to={EditorRoute.url({ knowledgeBaseID, knowledgeCategoryID: rootCategoryID })}
                onMouseOver={EditorRoute.preload}
                className={classNames("searchBar-actionButton")}
                baseClass={ButtonTypes.ICON}
                title={t("Compose")}
            >
                <ComposeIcon />
            </LinkAsButton>
        </Permission>
    );

    return (
        <>
            <AnalyticsData data={knowledgeBase} uniqueKey={knowledgeBaseID} />
            <FallbackBackUrlSetter url={HomeRoute.url(undefined)} />
            <Banner
                action={bannerAction}
                backgroundImage={bannerImage}
                contentImage={bannerContentImage}
                title={knowledgeBase.name}
                description={description}
            />
            <DocumentTitle title={knowledgeBase.name}>
                <TitleBar
                    key={knowledgeBaseID}
                    useMobileBackButton={!props.isOnlyKb}
                    extraBurgerNavigation={
                        <NavigationAdminLinks showDivider knowledgeBase={knowledgeBase} inHamburger />
                    }
                />
            </DocumentTitle>

            {/*For Screen Readers / SEO*/}
            <ScreenReaderContent>
                <h1>{knowledgeBase.name}</h1>
            </ScreenReaderContent>
            <HelpCenterNavigation data={data!} rootCategoryUrl={rootCategoryUrl} />
            <ArticlesWidget
                title={t("Recommended Articles")}
                maxItemCount={4}
                containerOptions={{
                    maxColumnCount: 1,
                    borderType: "navLinks",
                    viewAll: {
                        to: `/kb/articles?recommended=true&knowledgeBaseID=${knowledgeBase.knowledgeBaseID}`,
                    },
                }}
                params={widgetParams}
            />
        </>
    );
}

interface IOwnProps {
    knowledgeBase: IKnowledgeBase;
    isOnlyKb?: boolean;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IKnowledgeAppStoreState, ownProps: IOwnProps) {
    const { knowledgeBaseID } = ownProps.knowledgeBase;
    const knowledgeState = state.knowledge.navigation;
    const loadStatus = knowledgeState.fetchStatusesByKbID[knowledgeBaseID] || LoadStatus.PENDING;

    let data: ILinkListData | undefined;
    let rootCategoryUrl: string | undefined;
    if (loadStatus === LoadStatus.SUCCESS) {
        data = NavigationSelector.selectHelpCenterHome(knowledgeState.navigationItems, ownProps.knowledgeBase);
        const rootCategory = NavigationSelector.selectCategory(
            ownProps.knowledgeBase.rootCategoryID,
            knowledgeState.navigationItems,
        );
        if (rootCategory) {
            rootCategoryUrl = rootCategory.url;
        }
    }

    const loadable: ILoadable<ILinkListData> = {
        status: loadStatus,
        data,
    };

    return { ...loadable, rootCategoryUrl };
}

function mapDispatchToProps(dispatch: any, ownProps: IOwnProps) {
    const navActions = new NavigationActions(dispatch, apiv2);
    return {
        requestData: () => navActions.getNavigationFlat(ownProps.knowledgeBase.knowledgeBaseID),
    };
}

export default connect(mapStateToProps, mapDispatchToProps)(HelpCenterHome);
