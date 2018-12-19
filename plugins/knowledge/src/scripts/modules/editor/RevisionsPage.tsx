/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
import RevisionsPageModel, { IInjectableRevisionsState } from "./RevisionsPageModel";
import RevisionsPageActions, { IInjectableRevisionsPageActions } from "./RevisionsPageActions";
import { Modal, ModalSizes } from "@library/components/modal";
import { LoadStatus } from "@library/@types/api";
import UserContent from "@library/components/UserContent";
import PageTitle from "@knowledge/modules/common/PageTitle";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";
import RevisionsListItem from "@knowledge/modules/editor/components/RevisionsListItem";
import RevisionsList from "@knowledge/modules/editor/components/RevisionsList";
import { EditorRoute, RevisionsRoute } from "@knowledge/routes/pageRoutes";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import apiv2 from "@library/apiv2";

interface IProps
    extends IDeviceProps,
        IInjectableRevisionsState,
        RouteComponentProps<{
            id: string;
            revisionID?: string;
        }> {
    revisionsPageActions: RevisionsPageActions;
    articleActions: ArticleActions;
}

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
        return (
            <Modal
                size={ModalSizes.FULL_SCREEN}
                exitHandler={this.props.history.goBack}
                label={t("Article Revisions")}
                elementToFocusOnExit={document.activeElement as HTMLElement}
            >
                <PageLoader status={this.props.revisions.status}>
                    <DocumentTitle title={t("Article Revisions")}>
                        <form className="richEditorForm inheritHeight" onSubmit={this.onSubmit}>
                            <RevisionsLayout
                                bodyHeading={this.renderTitle()}
                                bodyContent={
                                    this.props.selectedRevision.data && (
                                        <UserContent content={this.props.selectedRevision.data.bodyRendered} />
                                    )
                                }
                                crumbs={this.props.article.data ? this.props.article.data.crumbs : []}
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
                includeBackLink={device === Devices.DESKTOP}
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
                <RevisionsList>
                    {revisions.data.slice().map(item => {
                        const preload = () =>
                            this.props.articleActions.fetchRevisionByID({ revisionID: item.articleRevisionID });
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
            this.props.revisionsPageActions.setActiveRevision(revisionID);
        }
    }

    /**
     * When the component unmounts we need to be sure to clear out the data we requested in componentDidMount.
     */
    public componentWillUnmount() {
        this.props.revisionsPageActions.reset();
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
        const { revisionsPageActions } = this.props;
        const { id, revisionID } = this.props.match.params;

        const numID = parseInt(id, 10);
        const numRevID = revisionID !== undefined ? parseInt(revisionID, 10) : undefined;
        await revisionsPageActions.setActiveArticle(numID);
        await revisionsPageActions.setActiveRevision(numRevID);
    }
}

const withRedux = connect(
    RevisionsPageModel.getInjectableProps,
    dispatch => {
        return {
            articleActions: new ArticleActions(dispatch, apiv2),
            revisionsPageActions: new RevisionsPageActions(dispatch, apiv2),
        };
    },
);

export default withRedux(withDevice(RevisionsPage));
