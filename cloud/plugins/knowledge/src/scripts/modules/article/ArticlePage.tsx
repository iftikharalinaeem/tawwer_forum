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
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";
import { CategoryRoute, HomeRoute } from "@knowledge/routes/pageRoutes";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { LoadStatus, PublishStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import DocumentTitle from "@library/routing/DocumentTitle";
import React from "react";
import { connect } from "react-redux";
import { match } from "react-router";
import { NavHistoryUpdater } from "@knowledge/navigation/NavHistoryContext";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import { articleEventFields } from "../analytics/KnowledgeAnalytics";
import { ArticleUntranslatedMessage } from "@knowledge/modules/article/components/ArticleUntranslatedMessage";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import { FallbackBackUrlSetter } from "@library/routing/links/BackRoutingProvider";
import { hasPermission } from "@library/features/users/Permission";
import { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";
import { ILayoutProps, withLayout } from "@vanilla/library/src/scripts/layout/LayoutContext";

interface IState {
    showRestoreDialogue: boolean;
}

/**
 * Page component for an article.
 */
export class ArticlePage extends React.Component<IProps, IState> {
    public static defaultProps = {
        forceLoading: false,
    };

    /**
     * Render not found or the article.
     */
    public render(): React.ReactNode {
        const { article, articlelocales, relatedArticles } = this.props;
        const articleID = this.articleID;

        if (!articleID) {
            return <KbErrorPage defaultError={DefaultKbError.NOT_FOUND} />;
        }

        if (article.status === LoadStatus.ERROR) {
            return <KbErrorPage error={article.error} />;
        }

        const activeRecord = {
            recordID: articleID,
            recordType: KbRecordType.ARTICLE,
        };

        if (
            [LoadStatus.PENDING, LoadStatus.LOADING].includes(article.status) ||
            !article.data ||
            this.props.forceLoading
        ) {
            return <NavigationLoadingLayout activeRecord={activeRecord} forceLoading={this.props.forceLoading} />;
        }

        const crumbs = article.data?.breadcrumbs;
        const lastCrumb = crumbs ? crumbs[crumbs.length - 1] ?? null : null;

        const fallbackKbUrl = lastCrumb?.url ?? HomeRoute.url(undefined);
        const fallbackUrl = this.props.isHomeArticle ? HomeRoute.url(undefined) : fallbackKbUrl;
        const isAbsoluteKbRoot = this.props.isHomeArticle && this.props.isOnlyKb;

        return (
            <DocumentTitle title={article.data.seoName || article.data.name}>
                <AnalyticsData data={articleEventFields(article.data)} uniqueKey={article.data.articleID} />
                <FallbackBackUrlSetter url={fallbackUrl} />
                <NavHistoryUpdater lastKbID={this.props.article.data!.knowledgeBaseID} />
                <ArticleLayout
                    key={articleID}
                    useBackButton={!isAbsoluteKbRoot}
                    article={article.data}
                    prevNavArticle={this.props.prevNavArticle}
                    nextNavArticle={this.props.nextNavArticle}
                    currentNavCategory={this.props.currentNavCategory}
                    messages={this.renderMessages()}
                    articlelocales={articlelocales?.data ?? null}
                    relatedArticles={relatedArticles?.error ? [] : relatedArticles?.data ?? null}
                    featured={article.data.featured}
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
        const hasUntranslatedMessage = notifyTranslationFallback && article.data;
        const hasDeletedMessage = article.data && article.data.status === PublishStatus.DELETED;
        if (!hasPermission("articles.add") || (!hasUntranslatedMessage && !hasDeletedMessage)) {
            return null;
        }

        return (
            <>
                {hasUntranslatedMessage && (
                    <ArticleUntranslatedMessage articleID={article.data!.articleID} date={article.data!.dateUpdated} />
                )}
                {hasDeletedMessage && (
                    <ArticleDeletedMessage
                        onRestoreClick={this.handleRestoreClick}
                        isLoading={this.props.restoreStatus === LoadStatus.LOADING}
                    />
                )}
            </>
        );
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

interface IOwnProps extends ILayoutProps {
    match: match<{
        id: string;
    }>;
    isOnlyKb?: boolean;
    isHomeArticle?: boolean;
    forceLoading?: boolean;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IKnowledgeAppStoreState, ownProps: IOwnProps) {
    const { restoreStatus } = state.knowledge.articlePage;
    const article = ArticlePageSelector.selectArticle(state);
    const notifyTranslationFallback =
        article &&
        article.data &&
        state.knowledge.articles.articleIDsWithTranslationFallback.includes(article.data.articleID);
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
        relatedArticles: article.data ? ArticleModel.selectRelatedArticles(state, article.data.articleID) : null,
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

const withRedux = connect(mapStateToProps, mapDispatchToProps);

export default withLayout(withRedux(ArticlePage));
