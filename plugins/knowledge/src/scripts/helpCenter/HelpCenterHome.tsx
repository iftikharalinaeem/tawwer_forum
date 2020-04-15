/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISearchRequestBody } from "@knowledge/@types/api/search";
import HelpCenterNavigation from "@knowledge/helpCenter/components/HelpCenterNavigation";
import { useHelpCenterNavigation } from "@knowledge/knowledge-bases/knowledgeBaseHooks";
import { IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { useArticleList } from "@knowledge/modules/article/ArticleModel";
import { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";
import NavigationAdminLinks from "@knowledge/navigation/subcomponents/NavigationAdminLinks";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";
import { EditorRoute, HomeRoute } from "@knowledge/routes/pageRoutes";
import { ArticlesWidget } from "@knowledge/widgets/ArticlesWidget";
import { LoadStatus } from "@library/@types/api/core";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import Banner from "@library/banner/Banner";
import Permission from "@library/features/users/Permission";
import { ButtonTypes } from "@library/forms/buttonTypes";
import TitleBar from "@library/headers/TitleBar";
import { ComposeIcon } from "@library/icons/common";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import DocumentTitle from "@library/routing/DocumentTitle";
import LinkAsButton from "@library/routing/LinkAsButton";
import { FallbackBackUrlSetter } from "@library/routing/links/BackRoutingProvider";
import { getSiteSection, t } from "@library/utility/appUtils";
import { NavLinksPlaceholder } from "@vanilla/library/src/scripts/navigation/NavLinksPlaceholder";
import classNames from "classnames";
import React from "react";
import SearchContext from "@vanilla/library/src/scripts/contexts/SearchContext";
import KnowledgeSearchProvider from "@knowledge/modules/search/KnowledgeSearchProvider";
import { UniversalKnowledgeWidget } from "@knowledge/knowledge-bases/UniversalKnowledgeWidget";
import { KbPermission } from "@knowledge/knowledge-bases/KbPermission";

interface IProps {
    knowledgeBase: IKnowledgeBase;
    isOnlyKb?: boolean;
}

/**
 * Component representing the the full home page of a help center.
 */
export default function HelpCenterHome(props: IProps) {
    const { knowledgeBase } = props;
    const navData = useHelpCenterNavigation(props.knowledgeBase.knowledgeBaseID);
    const navStatus = navData.status;
    const { bannerImage, bannerContentImage, knowledgeBaseID, rootCategoryID, description } = knowledgeBase;

    const widgetParams: ISearchRequestBody = {
        featured: true,
        siteSectionGroup: getSiteSection().sectionGroup === "vanilla" ? undefined : getSiteSection().sectionGroup,
        locale: getSiteSection().contentLocale,
        knowledgeBaseID: knowledgeBase.knowledgeBaseID,
    };
    const articleList = useArticleList(widgetParams);

    const titleBarAndBanner = (
        <>
            <SearchContext.Provider
                value={{ searchOptionProvider: new KnowledgeSearchProvider(knowledgeBase.knowledgeBaseID) }}
            >
                <DocumentTitle title={knowledgeBase.name}>
                    <TitleBar
                        key={knowledgeBaseID}
                        useMobileBackButton={!props.isOnlyKb}
                        extraBurgerNavigation={
                            <NavigationAdminLinks showDivider knowledgeBase={knowledgeBase} inHamburger />
                        }
                    />
                </DocumentTitle>
                <Banner
                    action={
                        <KbPermission permission="articles.add" kbID={knowledgeBaseID}>
                            <LinkAsButton
                                to={EditorRoute.url({ knowledgeBaseID, knowledgeCategoryID: rootCategoryID })}
                                onMouseOver={EditorRoute.preload}
                                className={classNames("searchBar-actionButton")}
                                baseClass={ButtonTypes.ICON}
                                title={t("Compose")}
                            >
                                <ComposeIcon />
                            </LinkAsButton>
                        </KbPermission>
                    }
                    backgroundImage={bannerImage}
                    contentImage={bannerContentImage}
                    title={knowledgeBase.name}
                    description={description}
                />
            </SearchContext.Provider>
            {/*For Screen Readers / SEO*/}
            <ScreenReaderContent>
                <h1>{knowledgeBase.name}</h1>
            </ScreenReaderContent>
            <FallbackBackUrlSetter url={HomeRoute.url(undefined)} />
        </>
    );

    if (
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(navStatus) ||
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(articleList.status)
    ) {
        return (
            <>
                {titleBarAndBanner}
                <NavLinksPlaceholder title={knowledgeBase.name} />
            </>
        );
    }

    if (status === LoadStatus.ERROR || !navData.data) {
        return <KbErrorPage error={navData.error} />;
    }

    if (knowledgeBase.countArticles === 0 && knowledgeBase.universalSources.length === 0) {
        return (
            <KbErrorPage
                defaultError={DefaultKbError.NO_ARTICLES}
                knowledgeBaseID={knowledgeBaseID}
                knowledgeCategoryID={knowledgeBase.rootCategoryID}
            />
        );
    }

    const hasRecommended = articleList.data && articleList.data.body.length > 0;
    const hasNavigation =
        navData.data.navigation.groups.length > 0 || navData.data.navigation.ungroupedItems.length > 0;

    return (
        <>
            {titleBarAndBanner}
            <AnalyticsData data={knowledgeBase} uniqueKey={knowledgeBaseID} />
            <HelpCenterNavigation
                data={navData.data.navigation}
                rootCategory={navData.data.rootCategory}
                kbID={knowledgeBaseID}
            />
            <ArticlesWidget
                title={t("Featured Articles", "Recommended Articles")}
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
            <UniversalKnowledgeWidget kb={knowledgeBase} hasTopSeparator={hasRecommended || hasNavigation} />
        </>
    );
}
