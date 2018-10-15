/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { match } from "react-router";
import { connect } from "react-redux";
import { IStoreState } from "@knowledge/state/model";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { LoadStatus, ILoadable } from "@library/@types/api";
import NotFoundPage from "@library/components/NotFoundPage";
import { ArticleLayout } from "@knowledge/modules/article/components";
import PageLoader from "@library/components/PageLoader";
import { IArticlePageState } from "@knowledge/modules/article/ArticlePageReducer";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import apiv2 from "@library/apiv2";
import DocumentTitle from "@library/components/DocumentTitle";
import { ICrumb } from "@library/components/Breadcrumbs";
import categoryModel from "@knowledge/modules/categories/CategoryModel";
import { IArticle } from "@knowledge/@types/api";

interface IProps extends IDeviceProps {
    match: match<{
        id: number;
    }>;
    article: ILoadable<IArticle>;
    articlePageActions: ArticlePageActions;
    breadcrumbData: ICrumb[] | null;
}

/**
 * Page component for an article.
 */
export class ArticlePage extends React.Component<IProps> {
    /**
     * Render not found or the article.
     */
    public render() {
        const { article, breadcrumbData } = this.props;
        const { id } = this.props.match.params;

        if (id === null || (article.status === LoadStatus.ERROR && article.error.status === 404)) {
            return <NotFoundPage type="Page" />;
        }

        return (
            <PageLoader {...article}>
                {article.status === LoadStatus.SUCCESS && (
                    <DocumentTitle title={article.data.seoName || article.data.articleRevision.name}>
                        <ArticleLayout article={article.data} breadcrumbData={breadcrumbData!} />
                    </DocumentTitle>
                )}
            </PageLoader>
        );
    }

    /**
     * If the component mounts without data we need to intialize it.
     */
    public componentDidMount() {
        const { article } = this.props;
        if (article.status !== LoadStatus.SUCCESS) {
            this.initializeFromUrl();
        }
    }

    /**
     * If the pages url changes we need to fetch the article data again.
     */
    public componentDidUpdate(prevProps: IProps) {
        if (this.props.match.url !== prevProps.match.url) {
            this.initializeFromUrl();
        }
    }

    /**
     * When the component unmounts we need to be sure to clear out the data we requested in componentDidMount.
     */
    public componentWillUnmount() {
        this.props.articlePageActions.reset();
    }

    /**
     * Initialize the page's data from it's url.
     */
    private initializeFromUrl() {
        const { articlePageActions } = this.props;
        const { id } = this.props.match.params;

        if (id === null) {
            return;
        }

        void articlePageActions.getArticleByID(id);
    }
}

/**
 * Map in the state from the redux store.
 */
function mapStateToProps(state: IStoreState) {
    let breadcrumbData: ICrumb[] | null = null;

    if (state.knowledge.articlePage.article.status === LoadStatus.SUCCESS) {
        const categories = categoryModel.selectKbCategoryBreadcrumb(
            state,
            state.knowledge.articlePage.article.data.knowledgeCategoryID,
        );
        breadcrumbData = categories.map(category => {
            return {
                name: category.name,
                url: category.url,
            };
        });
    }

    return {
        article: state.knowledge.articlePage.article,
        breadcrumbData,
    };
}

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch) {
    return {
        articlePageActions: new ArticlePageActions(dispatch, apiv2),
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(withDevice(ArticlePage));
