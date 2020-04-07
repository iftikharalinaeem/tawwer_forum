/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticleFragment } from "@knowledge/@types/api/article";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import CategoriesPageActions from "@knowledge/modules/categories/CategoriesPageActions";
import CategoriesLayout from "@knowledge/modules/categories/components/CategoriesLayout";
import { NavHistoryUpdater } from "@knowledge/navigation/NavHistoryContext";
import NavigationLoadingLayout from "@knowledge/navigation/NavigationLoadingLayout";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import apiv2 from "@library/apiv2";
import { ResultMeta } from "@library/result/ResultMeta";
import DocumentTitle from "@library/routing/DocumentTitle";
import React, { useEffect, useMemo } from "react";
import { connect } from "react-redux";
import { match } from "react-router";
import { knowledgeCategoryEventFields } from "../analytics/KnowledgeAnalytics";
import { useFallbackBackUrl } from "@library/routing/links/BackRoutingProvider";
import { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";
import Banner from "@vanilla/library/src/scripts/banner/Banner";

/**
 * Page component for a flat category list.
 */
export function CategoriesPage(props: IProps) {
    const { articles, category, pages, requestArticles, requestCategory } = props;
    const { page } = props.match.params;

    const id = useMemo(() => {
        const parsedID = parseInt(props.match.params.id, 10);
        if (Number.isNaN(parsedID)) {
            return null;
        } else {
            return parsedID;
        }
    }, [props.match.params.id]);

    useEffect(() => {
        if (id === null) {
            return;
        }

        let pageNumber;
        if (page !== undefined) {
            pageNumber = Number.parseInt(page, 10);
        }

        void requestArticles(id, pageNumber);
        void requestCategory(id);
    }, [id, page]);

    useFallbackBackUrl(props.knowledgeBase?.url);

    // Handle errors
    if (id === null) {
        return <KbErrorPage defaultError={DefaultKbError.NOT_FOUND} />;
    }

    const articlesError = articles.status === LoadStatus.ERROR && articles.error;
    if (articlesError) {
        return <KbErrorPage apiError={articles.error} />;
    }

    const categoryError = category.status === LoadStatus.ERROR && category.error;
    if (categoryError) {
        return <KbErrorPage apiError={category.error} />;
    }

    // Handle loading statuses
    const activeRecord = { recordID: id!, recordType: KbRecordType.CATEGORY };


    if (
        category.status === LoadStatus.LOADING ||
        articles.status === LoadStatus.LOADING ||
        !category.data ||
        !articles.data
    ) {
        return <NavigationLoadingLayout activeRecord={activeRecord} />;
    }
    const articleResults = articles.data.map((article: IArticleFragment) => {
        return {
            name: article.name || "",
            meta: <ResultMeta updateUser={article.updateUser} dateUpdated={article.dateUpdated} />,
            url: article.url,
            attachments: [],
            location: [],
        };
    });
    const kb = props.knowledgeBase;

    // Render either a loading layout or a full layout.
    return (
        <DocumentTitle title={category.data.name}>
            <Banner isContentBanner backgroundImage={kb?.bannerImage} contentImage={kb?.bannerContentImage} />
            <AnalyticsData
                data={knowledgeCategoryEventFields(category.data)}
                uniqueKey={category.data.knowledgeCategoryID}
            />
            <NavHistoryUpdater lastKbID={category.data.knowledgeBaseID} />
            <CategoriesLayout results={articleResults} category={category.data} pages={pages} />
        </DocumentTitle>
    );
}

interface IOwnProps {
    match: match<{
        id: string;
        page?: string;
    }>;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

/**
 * Map in the state from the redux store.
 */
function mapStateToProps(state: IKnowledgeAppStoreState, ownProps: IOwnProps) {
    const { categoriesPage, categories, knowledgeBases } = state.knowledge;
    const categoryID = parseInt(ownProps.match.params.id, 10);

    const category = {
        ...categoriesPage.categoryLoadStatus,
        data: categories.categoriesByID[categoryID],
    };

    const knowledgeBase = category.data
        ? knowledgeBases.knowledgeBasesByID.data?.[category.data.knowledgeBaseID] ?? null
        : null;

    return {
        category,
        articles: categoriesPage.articles,
        pages: categoriesPage.pages,
        knowledgeBase,
    };
}

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch) {
    const categoriesPageActions = new CategoriesPageActions(dispatch, apiv2);
    const articleActions = new ArticleActions(dispatch, apiv2);

    return {
        requestCategory: categoriesPageActions.initForCategoryID,
        requestArticles: articleActions.getArticles,
        onReset: categoriesPageActions.reset,
    };
}

export default connect(mapStateToProps, mapDispatchToProps)(CategoriesPage);
