/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticleFragment, IKbCategoryFragment, NavigationRecordType } from "@knowledge/@types/api";
import CategoriesPageActions from "@knowledge/modules/categories/CategoriesPageActions";
import { ICategoriesPageState } from "@knowledge/modules/categories/CategoriesPageReducer";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";
import CategoriesLayout from "@knowledge/modules/categories/components/CategoriesLayout";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { SearchResultMeta } from "@knowledge/modules/common/SearchResultMeta";
import NavigationLoadingLayout from "@knowledge/modules/navigation/NavigationLoadingLayout";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import { IDeviceProps } from "@library/components/DeviceChecker";
import DocumentTitle from "@library/components/DocumentTitle";
import NotFoundPage from "@library/components/navigation/NotFoundPage";
import PageLoader from "@library/components/PageLoader";
import { withDevice } from "@library/contexts/DeviceContext";
import React from "react";
import { connect } from "react-redux";
import { match } from "react-router";

interface IProps extends IDeviceProps {
    category: IKbCategoryFragment;
    categoriesPageState: ICategoriesPageState;
    categoriesPageActions: CategoriesPageActions;
    match: match<{
        id: string;
    }>;
}

/**
 * Page component for a flat category list.
 */
export class CategoriesPage extends React.Component<IProps> {
    public render() {
        const { categoriesPageState, category } = this.props;
        const id = this.categoryID;

        const activeRecord = { recordID: id!, recordType: NavigationRecordType.KNOWLEDGE_CATEGORY };
        const noCategoryID = id === null;
        const categoryNotFound =
            categoriesPageState.articles.status === LoadStatus.ERROR &&
            !!categoriesPageState.articles.error &&
            categoriesPageState.articles.error.status === 404;
        if (noCategoryID || categoryNotFound) {
            return <NotFoundPage type="Page" />;
        }

        const hasData = categoriesPageState.articles.status === LoadStatus.SUCCESS && categoriesPageState.articles.data;

        // Render either a loading layout or a full layout.

        return (
            <PageLoader {...categoriesPageState.articles} status={LoadStatus.SUCCESS}>
                {hasData ? (
                    <DocumentTitle title={category.name}>
                        <CategoriesLayout
                            results={categoriesPageState.articles.data!.map(this.mapArticleToResult)}
                            category={category!}
                        />
                    </DocumentTitle>
                ) : (
                    <NavigationLoadingLayout activeRecord={activeRecord} />
                )}
            </PageLoader>
        );
    }

    /**
     * If the component mounts without preloaded data we need to request it.
     */
    public componentDidMount() {
        const { categoriesPageState } = this.props;
        if (categoriesPageState.articles.status !== LoadStatus.PENDING) {
            return this.fetchCategoryData();
        }
    }

    /**
     * If we the id of the page changes we need to re-fetch the data.
     */
    public componentDidUpdate(prevProps: IProps) {
        if (this.props.match.params.id !== prevProps.match.params.id) {
            return this.fetchCategoryData();
        }
    }

    /**
     * Use our passed in action to fetch category.
     */
    private fetchCategoryData() {
        const { categoriesPageActions } = this.props;
        const id = this.categoryID;

        if (id === null) {
            return;
        }

        return categoriesPageActions.getArticles(id);
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
        this.props.categoriesPageActions.reset();
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

/**
 * Map in the state from the redux store.
 */
function mapStateToProps(state: IStoreState, ownProps: IProps) {
    const { id } = ownProps.match.params;
    return {
        categoriesPageState: state.knowledge.categoriesPage,
        category: CategoryModel.selectKbCategoryFragment(state, parseInt(id, 10)),
    };
}

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch) {
    return {
        categoriesPageActions: new CategoriesPageActions(dispatch, apiv2),
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(withDevice(CategoriesPage));
