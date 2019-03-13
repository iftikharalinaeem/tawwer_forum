/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { RouteComponentProps } from "react-router";
import { connect } from "react-redux";
import { IDeviceProps, Devices } from "@library/components/DeviceChecker";
import { withDevice } from "@library/contexts/DeviceContext";
import RevisionsLayout from "@knowledge/modules/editor/components/RevisionsLayout";
import PageLoader from "@library/components/PageLoader";
import DocumentTitle from "@library/components/DocumentTitle";
import { t } from "@library/application";
import RevisionsPageModel from "./RevisionsPageModel";
import RevisionsPageActions from "./RevisionsPageActions";
import { Modal, ModalSizes } from "@library/components/modal";
import { LoadStatus } from "@library/@types/api";
import UserContent from "@library/user-content/UserContent";
import PageTitle from "@knowledge/modules/common/PageTitle";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";
import RevisionsListItem from "@knowledge/modules/editor/components/RevisionsListItem";
import RevisionsList from "@knowledge/modules/editor/components/RevisionsList";
import { EditorRoute, RevisionsRoute } from "@knowledge/routes/pageRoutes";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import apiv2 from "@library/apiv2";
import { IStoreState } from "@knowledge/state/model";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import { richEditorFormClasses } from "@rich-editor/styles/richEditorStyles/richEditorFormClasses";
import classNames from "classnames";
import { inheritHeightClass } from "@library/styles/styleHelpers";

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
        const classesRichEditorForm = richEditorFormClasses();
        return (
            <Modal
                size={ModalSizes.FULL_SCREEN}
                exitHandler={history.goBack}
                label={t("Article Revisions")}
                elementToFocusOnExit={document.activeElement as HTMLElement}
            >
                <PageLoader status={revisions.status}>
                    <DocumentTitle title={t("Article Revisions")}>
                        <form
                            className={classNames("richEditorForm", inheritHeightClass(), classesRichEditorForm.root)}
                            onSubmit={this.onSubmit}
                        >
                            <RevisionsLayout
                                bodyHeading={this.renderTitle()}
                                bodyContent={
                                    selectedRevision.data && (
                                        <UserContent content={selectedRevision.data.bodyRendered} />
                                    )
                                }
                                crumbs={article.data && article.data.breadcrumbs ? article.data.breadcrumbs : []}
                                revisionList={this.renderList()}
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
        const { selectedRevision, device } = this.props;
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
    private renderList(): React.ReactNode {
        const { revisions, selectedRevisionID } = this.props;
        return (
            revisions.status === LoadStatus.SUCCESS &&
            revisions.data && (
                <RevisionsList hideTitle={this.props.device === Devices.MOBILE}>
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
     * Initialize the page's data from it's url.
     */
    private async initializeFromUrl() {
        const { id, revisionID } = this.props.match.params;

        const numID = parseInt(id, 10);
        const numRevID = revisionID !== undefined ? parseInt(revisionID, 10) : undefined;
        await this.props.setActiveArticle(numID);
        await this.props.setActiveRevision(numRevID);
    }
}

interface IOwnProps
    extends IDeviceProps,
        RouteComponentProps<{
            id: string;
            revisionID?: string;
        }> {}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    const { revisionsPage } = state.knowledge;
    const { selectedRevisionID, selectedRevisionStatus, revisionsStatus, articleID, articleStatus } = revisionsPage;
    const article = articleID ? ArticleModel.selectArticle(state, articleID) : null;
    return {
        // article:
        revisions: {
            ...revisionsStatus,
            data: RevisionsPageModel.selectRevisions(state),
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
    };
}

function mapDispatchToProps(dispatch: any) {
    const articleActions = new ArticleActions(dispatch, apiv2);
    const revisionsPageActions = new RevisionsPageActions(dispatch, apiv2);
    const { setActiveRevision, setActiveArticle, reset } = revisionsPageActions;

    return {
        preloadRevision: (revisionID: number) => articleActions.fetchRevisionByID({ revisionID }),
        setActiveRevision,
        setActiveArticle,
        reset,
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(withDevice(RevisionsPage));
