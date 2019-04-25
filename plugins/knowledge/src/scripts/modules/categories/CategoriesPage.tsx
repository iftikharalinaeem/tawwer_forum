/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticleFragment } from "@knowledge/@types/api/article";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import CategoriesPageActions from "@knowledge/modules/categories/CategoriesPageActions";
import CategoriesLayout from "@knowledge/modules/categories/components/CategoriesLayout";
import { DefaultError } from "@knowledge/modules/common/ErrorMessage";
import NavigationLoadingLayout from "@knowledge/navigation/NavigationLoadingLayout";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import ErrorPage from "@knowledge/pages/ErrorPage";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { ResultMeta } from "@library/result/ResultMeta";
import DocumentTitle from "@library/routing/DocumentTitle";
import React, { useEffect, useMemo } from "react";
import { hot } from "react-hot-loader";
import { connect } from "react-redux";
import { match } from "react-router";
import { NavHistoryUpdater } from "@knowledge/navigation/NavHistoryContext";

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

        void requestArticles(id, page);
        void requestCategory(id);
    }, [id, page]);

    // Handle errors
    if (id === null) {
        return <ErrorPage defaultError={DefaultError.NOT_FOUND} />;
    }

    const articlesError = articles.status === LoadStatus.ERROR && articles.error;
    if (articlesError) {
        return <ErrorPage apiError={articles.error} />;
    }

    const categoryError = category.status === LoadStatus.ERROR && category.error;
    if (categoryError) {
        return <ErrorPage apiError={category.error} />;
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
            excerpt: article.excerpt || "",
            attachments: [],
            location: [],
        };
    });

    // Render either a loading layout or a full layout.
    return (
        <DocumentTitle title={category.data.name}>
            <NavHistoryUpdater lastKbID={category.data.knowledgeBaseID} />
            <CategoriesLayout results={articleResults} category={category.data} pages={pages} />
        </DocumentTitle>
    );
}

interface IOwnProps {
    match: match<{
        id: string;
        page?: number;
    }>;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

/**
 * Map in the state from the redux store.
 */
function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    const { categoriesPage, categories } = state.knowledge;
    const categoryID = parseInt(ownProps.match.params.id, 10);

    const category = {
        ...categoriesPage.categoryLoadStatus,
        data: categories.categoriesByID[categoryID],
    };

    return {
        category,
        articles: categoriesPage.articles,
        pages: categoriesPage.pages,
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

export default hot(module)(
    connect(
        mapStateToProps,
        mapDispatchToProps,
    )(CategoriesPage),
);
