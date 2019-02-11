/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticleFragment, IKbCategory } from "@knowledge/@types/api";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import CategoriesPageActions from "@knowledge/modules/categories/CategoriesPageActions";
import CategoriesLayout from "@knowledge/modules/categories/components/CategoriesLayout";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { SearchResultMeta } from "@knowledge/modules/common/SearchResultMeta";
import NavigationLoadingLayout from "@knowledge/navigation/NavigationLoadingLayout";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import ErrorPage, { DefaultError } from "@knowledge/routes/ErrorPage";
import { IStoreState } from "@knowledge/state/model";
import { ILoadable, LoadStatus } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import DocumentTitle from "@library/components/DocumentTitle";
import React from "react";
import { connect } from "react-redux";
import { match } from "react-router";

/**
 * Page component for a flat category list.
 */
export class CategoriesPage extends React.Component<IProps> {
    public render() {
        const { articles, category, pages } = this.props;
        const id = this.categoryID;

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

        if (!category.data || !articles.data) {
            return <NavigationLoadingLayout activeRecord={activeRecord} />;
        }

        // Render either a loading layout or a full layout.
        return (
            <DocumentTitle title={category.data!.name}>
                <CategoriesLayout
                    results={articles.data.map(this.mapArticleToResult)}
                    category={category.data}
                    pages={pages}
                />
            </DocumentTitle>
        );
    }

    /**
     * If the component mounts without preloaded data we need to request it.
     */
    public componentDidMount() {
        this.fetchCategoryData();
    }

    /**
     * If we the id of the page changes we need to re-fetch the data.
     */
    public componentDidUpdate(prevProps: IProps) {
        const { id, page } = this.props.match.params;

        if (id !== prevProps.match.params.id || page !== prevProps.match.params.page) {
            this.fetchCategoryData();
        }
    }

    /**
     * Use our passed in action to fetch category.
     */
    private fetchCategoryData() {
        const { requestArticles, articles, category, requestCategory } = this.props;
        const id = this.categoryID;
        const { page } = this.props.match.params;

        if (id === null) {
            return;
        }

        if (articles.status === LoadStatus.PENDING) {
            void requestArticles(id, page);
        }

        if (category.status === LoadStatus.PENDING) {
            void requestCategory(id);
        }
    }

    /**
     * Get a numeric category ID from the string id passed by the router.
     */
    private get categoryID(): number | null {
        const id = parseInt(this.props.match.params.id, 10);
        if (Number.isNaN(id)) {
            return null;
        } else {
            return id;
        }
    }

    /**
     * Cleanup the page contents.
     */
    public componentWillUnmount() {
        this.props.onReset();
    }

    /**
     * Map an article fragment into an `IResult`.
     */
    private mapArticleToResult(article: IArticleFragment): IResult {
        return {
            name: article.name || "",
            meta: <SearchResultMeta updateUser={article.updateUser} dateUpdated={article.dateUpdated} />,
            url: article.url,
            excerpt: article.excerpt || "",
            attachments: [],
            location: [],
        };
    }
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

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(CategoriesPage);
