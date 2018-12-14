/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { match } from "react-router";
import { connect } from "react-redux";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { LoadStatus } from "@library/@types/api";
import { withDevice } from "@library/contexts/DeviceContext";
import ArticleLayout from "@knowledge/modules/article/components/ArticleLayout";
import PageLoader from "@library/components/PageLoader";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import apiv2 from "@library/apiv2";
import DocumentTitle from "@library/components/DocumentTitle";
import { ArticleStatus, NavigationRecordType } from "@knowledge/@types/api";
import ArticleDeletedMessage from "@knowledge/modules/article/components/ArticleDeletedMessage";
import ArticleActions, { IArticleActionsProps } from "@knowledge/modules/article/ArticleActions";
import ArticlePageModel, { IInjectableArticlePageState } from "./ArticlePageModel";
import Permission from "@library/users/Permission";
import ErrorPage from "@knowledge/routes/ErrorPage";
import NavigationLoadingLayout from "@knowledge/modules/navigation/NavigationLoadingLayout";

interface IProps extends IDeviceProps, IArticleActionsProps, IInjectableArticlePageState {
    match: match<{
        id: string;
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
    public render(): React.ReactNode {
        const { loadable } = this.props;

        const id = this.articleID;
        const hasData = loadable.status === LoadStatus.SUCCESS && loadable.data && id;
        const activeRecord = { recordID: id!, recordType: NavigationRecordType.ARTICLE };
        return (
            <>
                <ErrorPage loadable={loadable} />
                <PageLoader status={LoadStatus.SUCCESS}>
                    {hasData ? (
                        <DocumentTitle title={loadable.data!.article.seoName || loadable.data!.article.name}>
                            <ArticleLayout
                                article={loadable.data!.article}
                                breadcrumbData={loadable.data!.breadcrumbs}
                                messages={this.renderMessages()}
                                kbID={1}
                            />
                        </DocumentTitle>
                    ) : (
                        <NavigationLoadingLayout activeRecord={activeRecord} />
                    )}
                </PageLoader>
            </>
        );
    }

    /**
     * If the component mounts without data we need to intialize it.
     */
    public componentDidMount() {
        const { loadable } = this.props;
        if (loadable.status === LoadStatus.PENDING) {
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
                    <Permission permission="articles.add">
                        <ArticleDeletedMessage
                            onRestoreClick={this.handleRestoreClick}
                            isLoading={this.props.restoreStatus === LoadStatus.LOADING}
                        />
                    </Permission>
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

    private get articleID(): number | null {
        const id = parseInt(this.props.match.params.id, 10);
        if (Number.isNaN(id)) {
            return null;
        } else {
            return id;
        }
    }

    /**
     * Initialize the page's data from it's url.
     */
    private initializeFromUrl() {
        const { articlePageActions } = this.props;
        const id = this.articleID;

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

export default withRedux(withDevice<IProps>(ArticlePage));
