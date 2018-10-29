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
import ArticleLayout from "@knowledge/modules/article/components/ArticleLayout";
import PageLoader from "@library/components/PageLoader";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import apiv2 from "@library/apiv2";
import DocumentTitle from "@library/components/DocumentTitle";
import { ICrumb } from "@library/components/Breadcrumbs";
import categoryModel from "@knowledge/modules/categories/CategoryModel";
import { IArticle, ArticleStatus } from "@knowledge/@types/api";
import ArticleDeletedMessage from "@knowledge/modules/article/components/ArticleDeletedMessage";
import ArticleActions, { IArticleActionsProps } from "@knowledge/modules/article/ArticleActions";
import ArticlePageModel, { IInjectableArticlePageState } from "./ArticlePageModel";

interface IProps extends IDeviceProps, IArticleActionsProps, IInjectableArticlePageState {
    match: match<{
        id: number;
    }>;
    articlePageActions: ArticlePageActions;
}

interface IState {
    showRestoreDialogue: boolean;
}

/**
 * Page component for an article.
 */
export class ArticlePage extends React.Component<IProps, IState> {
    /**
     * Render not found or the article.
     */
    public render() {
        const { loadable } = this.props;
        const { id } = this.props.match.params;

        if (id === null || (loadable.status === LoadStatus.ERROR && loadable.error!.status === 404)) {
            return <NotFoundPage type="Page" />;
        }

        return (
            <PageLoader {...loadable}>
                {loadable.data && (
                    <DocumentTitle title={loadable.data.article.seoName || loadable.data.article.name}>
                        <ArticleLayout
                            article={loadable.data.article}
                            breadcrumbData={loadable.data.breadcrumbs}
                            messages={this.renderMessages()}
                        />
                    </DocumentTitle>
                )}
            </PageLoader>
        );
    }

    /**
     * If the component mounts without data we need to intialize it.
     */
    public componentDidMount() {
        const { loadable } = this.props;
        if (loadable.status !== LoadStatus.SUCCESS) {
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

    private renderMessages(): React.ReactNode {
        const { loadable } = this.props;
        let messages: React.ReactNode;

        if (loadable.data) {
            if (loadable.data.article.status === ArticleStatus.DELETED) {
                messages = (
                    <ArticleDeletedMessage
                        onRestoreClick={this.handleRestoreClick}
                        isLoading={this.props.restoreStatus === LoadStatus.LOADING}
                    />
                );
            }
        }

        return messages;
    }

    private handleRestoreClick = async () => {
        const { articleActions, loadable } = this.props;
        await articleActions.patchStatus({
            articleID: loadable.data!.article.articleID,
            status: ArticleStatus.PUBLISHED,
        });
    };

    /**
     * Initialize the page's data from it's url.
     */
    private initializeFromUrl() {
        const { articlePageActions } = this.props;
        const { id } = this.props.match.params;

        if (id === null) {
            return;
        }

        void articlePageActions.init(id);
    }
}

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch) {
    return {
        articlePageActions: new ArticlePageActions(dispatch, apiv2),
        ...ArticleActions.mapDispatchToProps(dispatch),
    };
}

const withRedux = connect(
    ArticlePageModel.getInjectableState,
    mapDispatchToProps,
);

export default withRedux(withDevice(ArticlePage));
