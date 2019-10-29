/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ArticleActions from "@knowledge/modules/article/ArticleActions";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import ArticlePageSelector from "@knowledge/modules/article/ArticlePageSelector";
import ArticleDeletedMessage from "@knowledge/modules/article/components/ArticleDeletedMessage";
import ArticleLayout from "@knowledge/modules/article/components/ArticleLayout";
import NavigationLoadingLayout from "@knowledge/navigation/NavigationLoadingLayout";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import ErrorPage from "@knowledge/pages/ErrorPage";
import { CategoryRoute } from "@knowledge/routes/pageRoutes";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { LoadStatus, PublishStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import DocumentTitle from "@library/routing/DocumentTitle";
import { withDevice, IDeviceProps } from "@library/layout/DeviceContext";
import Permission from "@library/features/users/Permission";
import React from "react";
import { connect } from "react-redux";
import { match } from "react-router";
import { hot } from "react-hot-loader";
import { DefaultError } from "@knowledge/modules/common/PageErrorMessage";
import { NavHistoryUpdater } from "@knowledge/navigation/NavHistoryContext";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import { articleEventFields } from "../analytics/KnowledgeAnalytics";
import { ArticleUntranslatedMessage } from "@knowledge/modules/article/components/ArticleUntranslatedMessage";
import ArticleModel from "@knowledge/modules/article/ArticleModel";

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
        const { article, articlelocales } = this.props;
        const articleID = this.articleID;
        if (!articleID) {
            return <ErrorPage defaultError={DefaultError.NOT_FOUND} />;
        }

        if (article.status === LoadStatus.ERROR) {
            return <ErrorPage error={article.error} />;
        }

        const activeRecord = {
            recordID: articleID,
            recordType: KbRecordType.ARTICLE,
        };

        if (
            [LoadStatus.PENDING, LoadStatus.LOADING].includes(article.status) ||
            !article.data ||
            !articlelocales ||
            !articlelocales.data
        ) {
            return <NavigationLoadingLayout activeRecord={activeRecord} />;
        }

        return (
            <DocumentTitle title={article.data.seoName || article.data.name}>
                <AnalyticsData data={articleEventFields(article.data)} uniqueKey={article.data.articleID} />
                <NavHistoryUpdater lastKbID={this.props.article.data!.knowledgeBaseID} />
                <ArticleLayout
                    article={article.data}
                    prevNavArticle={this.props.prevNavArticle}
                    nextNavArticle={this.props.nextNavArticle}
                    currentNavCategory={this.props.currentNavCategory}
                    messages={this.renderMessages()}
                    articlelocales={articlelocales.data}
                />
            </DocumentTitle>
        );
    }

    /**
     * If the component mounts without data we need to intialize it.
     */
    public componentDidMount() {
        const { article } = this.props;
        if (article.status === LoadStatus.PENDING) {
            this.initializeFromUrl();
        }

        // Preload the categories page. We may be navigating to it shortly.
        CategoryRoute.preload();
    }

    /**
     * If the pages url changes we need to fetch the article data again.
     */
    public componentDidUpdate(prevProps: IProps) {
        if (this.props.match.url !== prevProps.match.url || this.props.article.status === LoadStatus.PENDING) {
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
        const { article, notifyTranslationFallback } = this.props;
        let messages = (
            <Permission permission="articles.add">
                {notifyTranslationFallback && article.data && (
                    <ArticleUntranslatedMessage articleID={article.data.articleID} />
                )}
                {article.data && article.data.status === PublishStatus.DELETED && (
                    <ArticleDeletedMessage
                        onRestoreClick={this.handleRestoreClick}
                        isLoading={this.props.restoreStatus === LoadStatus.LOADING}
                    />
                )}
            </Permission>
        );

        return messages;
    }

    private handleRestoreClick = async () => {
        const { articleActions, article } = this.props;
        await articleActions.patchStatus({
            articleID: article.data!.articleID,
            status: PublishStatus.PUBLISHED,
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

interface IOwnProps extends IDeviceProps {
    match: match<{
        id: string;
    }>;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IKnowledgeAppStoreState, ownProps: IOwnProps) {
    const { restoreStatus } = state.knowledge.articlePage;
    const article = ArticlePageSelector.selectArticle(state);
    const notifyTranslationFallback =
        article &&
        article.data &&
        state.knowledge.articles.articlesIDsWithTranslationFallback.includes(article.data.articleID);
    const categoryID = article.data ? article.data.knowledgeCategoryID : null;

    return {
        article,
        restoreStatus,
        currentNavCategory:
            categoryID !== null
                ? NavigationSelector.selectCategory(categoryID, state.knowledge.navigation.navigationItems) || null
                : null,
        nextNavArticle: ArticlePageSelector.selectNextNavArticle(state),
        prevNavArticle: ArticlePageSelector.selectPrevNavArticle(state),
        articlelocales: article.data ? ArticleModel.selectArticleLocale(state, article.data.articleID) : null,
        notifyTranslationFallback,
    };
}

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch, ownProps: IOwnProps) {
    return {
        articlePageActions: new ArticlePageActions(dispatch, apiv2),
        ...ArticleActions.mapDispatchToProps(dispatch),
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default hot(module)(withDevice(withRedux(ArticlePage)));
