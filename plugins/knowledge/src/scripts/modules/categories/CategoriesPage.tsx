/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import CategoriesLayout from "@knowledge/modules/categories/components/CategoriesLayout";
import { LoadStatus } from "@library/@types/api";
import PageLoader from "@library/components/PageLoader";
import { ICrumb } from "@library/components/Breadcrumbs";
import { IStoreState } from "@knowledge/state/model";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";
import { match } from "react-router";
import { ICategoriesPageState } from "@knowledge/modules/categories/CategoriesPageReducer";
import apiv2 from "@library/apiv2";
import CategoriesPageActions from "@knowledge/modules/categories/CategoriesPageActions";
import { connect } from "react-redux";
import NotFoundPage from "@library/components/navigation/NotFoundPage";
import DocumentTitle from "@library/components/DocumentTitle";
import { IKbCategoryFragment } from "@knowledge/@types/api";

interface IProps extends IDeviceProps {
    breadcrumbData: ICrumb[];
    category: IKbCategoryFragment;
    categoriesPageState: ICategoriesPageState;
    categoriesPageActions: CategoriesPageActions;
    match: match<{
        id: number;
    }>;
}

/**
 * Page component for a flat category list.
 */
export class CategoriesPage extends React.Component<IProps> {
    public render() {
        const { breadcrumbData, category, categoriesPageState } = this.props;
        const { id } = this.props.match.params;

        const noCategoryID = id === null;
        const categoryNotFound =
            categoriesPageState.articles.status === LoadStatus.ERROR &&
            !!categoriesPageState.articles.error &&
            categoriesPageState.articles.error.status === 404;
        if (noCategoryID || categoryNotFound) {
            return <NotFoundPage type="Page" />;
        }

        return (
            <PageLoader {...categoriesPageState.articles}>
                {categoriesPageState.articles.status === LoadStatus.SUCCESS &&
                    categoriesPageState.articles.data && (
                        <DocumentTitle title={category.name}>
                            <CategoriesLayout
                                articles={categoriesPageState.articles.data}
                                breadcrumbData={breadcrumbData}
                                category={category}
                            />
                        </DocumentTitle>
                    )}
            </PageLoader>
        );
    }

    /**
     * If the component mounts without any data we need to fetch request it.
     */
    public componentDidMount() {
        const { categoriesPageState, categoriesPageActions } = this.props;
        const { id } = this.props.match.params;
        if (categoriesPageState.articles.status !== LoadStatus.PENDING) {
            return;
        }

        if (id === null) {
            return;
        }

        void categoriesPageActions.getArticles(id);
    }

    /**
     * Cleanup the page contents.
     */
    public componentWillUnmount() {
        this.props.categoriesPageActions.reset();
    }
}

/**
 * Map in the state from the redux store.
 */
function mapStateToProps(state: IStoreState, ownProps: IProps) {
    let breadcrumbData: ICrumb[] = [];
    const { id } = ownProps.match.params;

    if (state.knowledge.categoriesPage.articles.status === LoadStatus.SUCCESS) {
        const categories = CategoryModel.selectKbCategoryBreadcrumb(state, id);
        breadcrumbData = categories.map(category => {
            return {
                name: category.name,
                url: category.url,
            };
        });
    }

    return {
        categoriesPageState: state.knowledge.categoriesPage,
        breadcrumbData,
        category: CategoryModel.selectKbCategoryFragment(state, id),
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
