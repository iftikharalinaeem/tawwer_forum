/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ArticleActions from "@knowledge/modules/article/ArticleActions";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";
import PageTitle from "@knowledge/modules/common/PageTitle";
import DraftsList from "@knowledge/modules/editor/components/DraftsList";
import DraftsListItem from "@knowledge/modules/editor/components/DraftsListItem";
import RevisionsLayout from "@knowledge/modules/editor/components/RevisionsLayout";
import RevisionsList from "@knowledge/modules/editor/components/RevisionsList";
import RevisionsListItem from "@knowledge/modules/editor/components/RevisionsListItem";
import RevisionsPageActions from "@knowledge/modules/editor/RevisionsPageActions";
import RevisionsPageModel from "@knowledge/modules/editor/RevisionsPageModel";
import { EditorRoute, RevisionsRoute, HomeRoute } from "@knowledge/routes/pageRoutes";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import apiv2 from "@library/apiv2";
import UserContent from "@library/content/UserContent";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import DocumentTitle from "@library/routing/DocumentTitle";
import PageLoader from "@library/routing/PageLoader";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React from "react";
import { connect } from "react-redux";
import { RouteComponentProps } from "react-router";
import { FallbackBackUrlSetter } from "@library/routing/links/BackRoutingProvider";
import { ILayoutProps, useLayout, withLayout } from "@library/layout/LayoutContext";
import { IDeviceProps, withDevice } from "@library/layout/DeviceContext";

interface IState {
    showRestoreDialogue: boolean;
}

/**
 * Page component for an article.
 */
export class RevisionsPage extends React.Component<IProps, IState> {
    /**
     * Render not found or the article.
     */
    public render() {
        const { article, history, revisions, selectedRevision } = this.props;
        const loadStatus = revisions.data ? LoadStatus.SUCCESS : revisions.status;
        let analyticsDataID;

        // Determine if this warrants a new analytics page view event.
        if (selectedRevision.data) {
            analyticsDataID = `revision_${selectedRevision.data.articleRevisionID}`;
        } else if (article.data) {
            analyticsDataID = `article_${article.data.articleID}`;
        } else {
            analyticsDataID = -1;
        }

        return (
            <Modal
                isVisible={true}
                scrollable={true}
                size={ModalSizes.FULL_SCREEN}
                exitHandler={history.goBack}
                label={t("Article Revisions")}
                elementToFocusOnExit={document.activeElement as HTMLElement}
            >
                <AnalyticsData uniqueKey={analyticsDataID} />
                <FallbackBackUrlSetter url={article?.data?.url ?? HomeRoute.url(undefined)} />
                <PageLoader status={loadStatus}>
                    <DocumentTitle title={t("Article Revisions")}>
                        <form className={classNames(inheritHeightClass())} onSubmit={this.onSubmit}>
                            <RevisionsLayout
                                bodyHeading={this.renderTitle()}
                                bodyContent={
                                    selectedRevision.data && (
                                        <UserContent content={selectedRevision.data.bodyRendered} />
                                    )
                                }
                                crumbs={article.data && article.data.breadcrumbs ? article.data.breadcrumbs : []}
                                draftList={this.renderDrafts()}
                                revisionList={this.renderRevisions()}
                                canSubmit={this.canSubmit}
                            />
                        </form>
                    </DocumentTitle>
                </PageLoader>
            </Modal>
        );
    }

    /**
     * Render the active revisions title and metadata.
     */
    private renderTitle(): React.ReactNode {
        const { selectedRevision } = this.props;
        return selectedRevision.status === LoadStatus.SUCCESS && selectedRevision.data ? (
            <PageTitle
                title={selectedRevision.data.name}
                includeBackLink={false}
                meta={
                    <ArticleMeta
                        updateUser={selectedRevision.data.insertUser!}
                        dateUpdated={selectedRevision.data.dateInserted}
                        permaLink={RevisionsRoute.url(selectedRevision.data)}
                    />
                }
            />
        ) : null;
    }

    /**
     * Render the list of revisions.
     */
    private renderDrafts(): React.ReactNode {
        const { drafts } = this.props;

        const { isCompact } = useLayout();

        if (drafts.status !== LoadStatus.SUCCESS || (Array.isArray(drafts.data) && drafts.data.length === 0)) {
            return null;
        }

        return (
            drafts.data && (
                <DraftsList hideTitle={isCompact}>
                    {drafts.data.slice().map(item => {
                        return (
                            <DraftsListItem
                                {...item}
                                url={EditorRoute.url({ draftID: item.draftID, articleID: this.articleID })}
                                key={item.draftID}
                            />
                        );
                    })}
                </DraftsList>
            )
        );
    }

    /**
     * Render the list of revisions.
     */
    private renderRevisions(): React.ReactNode {
        const { revisions, selectedRevisionID, pagination } = this.props;
        const { isCompact } = useLayout();
        return (
            revisions.data && (
                <>
                    <RevisionsList hideTitle={isCompact}>
                        {revisions.data.slice().map(item => {
                            const preload = () => this.props.preloadRevision(item.articleRevisionID);
                            return (
                                <RevisionsListItem
                                    {...item}
                                    isSelected={item.articleRevisionID === selectedRevisionID}
                                    url={RevisionsRoute.url(item)}
                                    onHover={preload}
                                    key={item.articleRevisionID}
                                />
                            );
                        })}
                    </RevisionsList>
                    <LoadMoreButton nextPage={pagination.revisions.next} onClick={this.loadArticleRevisions} />
                </>
            )
        );
    }

    /**
     * If the component mounts without data we need to intialize it.
     */
    public componentDidMount() {
        // Initialize the page data, then preload the editor page because we are likely to navigate there.
        void this.initializeFromUrl().then(EditorRoute.preload);
    }

    /**
     * If the pages url changes we need to fetch the article data again.
     */
    public componentDidUpdate(prevProps: IProps) {
        const { params } = this.props.match;
        const prevParams = prevProps.match.params;
        if (params.id !== prevParams.id) {
            void this.initializeFromUrl();
        } else if (params.revisionID !== prevParams.revisionID) {
            const revisionID = params.revisionID !== undefined ? parseInt(params.revisionID, 10) : undefined;
            this.props.setActiveRevision(revisionID);
        }
    }

    /**
     * When the component unmounts we need to be sure to clear out the data we requested in componentDidMount.
     */
    public componentWillUnmount() {
        this.props.reset();
    }

    /**
     * Form submit handler. Fetch the values out of the form and pass them to the callback prop.
     */
    private onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        if (this.canSubmit) {
            this.props.history.push(EditorRoute.url(this.props.selectedRevision.data!));
        }
    };

    /**
     * Determine whether or not we can submit the form or not.
     *
     * Loading or already published revisions cannot be submitted.
     */
    private get canSubmit(): boolean {
        const { selectedRevision } = this.props;
        return (
            selectedRevision.status === LoadStatus.SUCCESS &&
            !!selectedRevision.data &&
            selectedRevision.data.status !== "published"
        );
    }

    /**
     * Get revisions of an article.
     *
     * @param articleID
     * @param page
     */
    private loadArticleRevisions = (): void => {
        const { articleID, fetchRevisionsForArticle, pagination } = this.props;
        if (!articleID || !pagination.revisions.next) {
            return;
        }
        void fetchRevisionsForArticle({ articleID, page: pagination.revisions.next });
    };

    /**
     * Initialize the page's data from it's url.
     */
    private async initializeFromUrl() {
        await this.props.setActiveArticle(this.articleID);
        await this.props.setActiveRevision(this.revisionID);
    }

    private get articleID(): number {
        return parseInt(this.props.match.params.id, 10);
    }

    private get revisionID(): number | null {
        const { revisionID } = this.props.match.params;
        return revisionID !== undefined ? parseInt(this.props.match.params.id, 10) : null;
    }
}

interface IOwnProps
    extends ILayoutProps,
        IDeviceProps,
        RouteComponentProps<{
            id: string;
            revisionID?: string;
        }> {}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IKnowledgeAppStoreState, ownProps: IOwnProps) {
    const { revisionsPage } = state.knowledge;
    const {
        selectedRevisionID,
        selectedRevisionStatus,
        revisionsStatus,
        articleID,
        articleStatus,
        draftsStatus,
        pagination,
    } = revisionsPage;
    const article = articleID ? ArticleModel.selectArticle(state, articleID) : null;
    return {
        articleID,
        revisions: {
            ...revisionsStatus,
            data: RevisionsPageModel.selectRevisions(state),
        },
        drafts: {
            ...draftsStatus,
            data: RevisionsPageModel.selectDrafts(state),
        },
        article: {
            ...articleStatus,
            data: article || undefined,
        },
        selectedRevision: {
            ...selectedRevisionStatus,
            data: RevisionsPageModel.selectActiveRevision(state),
        },
        selectedRevisionID,
        pagination,
    };
}

function mapDispatchToProps(dispatch: any) {
    const articleActions = new ArticleActions(dispatch, apiv2);
    const revisionsPageActions = new RevisionsPageActions(dispatch, apiv2);
    const { setActiveRevision, setActiveArticle, reset } = revisionsPageActions;
    const { fetchRevisionsForArticle } = articleActions;

    return {
        preloadRevision: (revisionID: number) => articleActions.fetchRevisionByID({ revisionID }),
        setActiveRevision,
        setActiveArticle,
        reset,
        fetchRevisionsForArticle,
    };
}

const withRedux = connect(mapStateToProps, mapDispatchToProps);

function LoadMoreButton(props: { nextPage?: number; onClick: () => void }) {
    const { nextPage, onClick } = props;

    if (!nextPage || nextPage < 2) {
        return null;
    }

    return (
        <Button baseClass={ButtonTypes.STANDARD} onClick={onClick}>
            {t("Load More")}
        </Button>
    );
}

export default withRedux(withDevice(RevisionsPage));
