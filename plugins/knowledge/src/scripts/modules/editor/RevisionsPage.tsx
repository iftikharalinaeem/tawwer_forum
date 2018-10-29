/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { RouteComponentProps, withRouter } from "react-router";
import { connect } from "react-redux";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import RevisionsLayout from "@knowledge/modules/editor/components/RevisionsLayout";
import PageLoader from "@library/components/PageLoader";
import DocumentTitle from "@library/components/DocumentTitle";
import { t } from "@library/application";
import RevisionsPageModel, { IInjectableRevisionsState } from "./RevisionsPageModel";
import RevisionsPageActions, { IInjectableRevisionsPageActions } from "./RevisionsPageActions";
import { Modal, ModalSizes } from "@library/components/modal";
import { LoadStatus } from "@library/@types/api";
import PageTitle from "../common/PageTitle";
import { ArticleMeta } from "../article/components/ArticleMeta";
import RevisionsList from "./components/RevisionsList";
import RevisionsListItem from "./components/RevisionsListItem";
import { makeRevisionsUrl, makeEditUrl } from "./route";
import UserContent from "@library/components/UserContent";

interface IProps
    extends IDeviceProps,
        IInjectableRevisionsState,
        IInjectableRevisionsPageActions,
        RouteComponentProps<{
            id: string;
            revisionID?: string;
        }> {}

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
            <Modal size={ModalSizes.FULL_SCREEN} exitHandler={this.navigateToBacklink} label={t("Article Revisions")}>
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
                                revisionList={this.renderList()}
                                canSubmit={this.canSubmit}
                            />
                        </form>
                    </DocumentTitle>
                </PageLoader>
            </Modal>
        );
    }

    private renderTitle(): React.ReactNode {
        const { selectedRevision } = this.props;
        return selectedRevision.status === LoadStatus.SUCCESS && selectedRevision.data ? (
            <PageTitle
                title={selectedRevision.data.name}
                backUrl={null}
                meta={
                    <ArticleMeta
                        updateUser={selectedRevision.data.insertUser!}
                        dateUpdated={selectedRevision.data.dateInserted}
                        permaLink={makeRevisionsUrl(selectedRevision.data)}
                    />
                }
            />
        ) : null;
    }

    private renderList(): React.ReactNode {
        const { revisions, selectedRevisionID } = this.props;
        return (
            revisions.status === LoadStatus.SUCCESS &&
            revisions.data && (
                <RevisionsList>
                    {revisions.data
                        .slice()
                        .reverse()
                        .map(item => (
                            <RevisionsListItem
                                {...item}
                                isSelected={item.articleRevisionID === selectedRevisionID}
                                url={makeRevisionsUrl(item)}
                                key={item.articleRevisionID}
                            />
                        ))}
                </RevisionsList>
            )
        );
    }

    /**
     * If the component mounts without data we need to intialize it.
     */
    public componentDidMount() {
        this.initializeFromUrl();
    }

    /**
     * If the pages url changes we need to fetch the article data again.
     */
    public componentDidUpdate(prevProps: IProps) {
        const { params } = this.props.match;
        const prevParams = prevProps.match.params;
        if (params.id !== prevParams.id) {
            this.initializeFromUrl();
        } else if (params.revisionID !== prevParams.revisionID) {
            const revisionID = params.revisionID !== undefined ? parseInt(params.revisionID, 10) : undefined;
            this.props.revisionsPageActions.setActiveRevision(revisionID);
        }
    }

    /**
     * When the component unmounts we need to be sure to clear out the data we requested in componentDidMount.
     */
    public componentWillUnmount() {
        // this.props.revisionsPageActions.();
    }

    /**
     * Form submit handler. Fetch the values out of the form and pass them to the callback prop.
     */
    private onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        if (this.canSubmit) {
            this.props.history.push(makeEditUrl(this.props.selectedRevision.data!));
        }
    };

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
    private initializeFromUrl() {
        const { revisionsPageActions } = this.props;
        const { id, revisionID } = this.props.match.params;

        const numID = parseInt(id, 10);
        const numRevID = revisionID !== undefined ? parseInt(revisionID, 10) : undefined;
        void revisionsPageActions.init(numID, numRevID);
    }

    /**
     * Route back to the previous location if its available.
     */
    private navigateToBacklink = () => {
        this.props.history.goBack();
    };
}

const withRedux = connect(
    RevisionsPageModel.getInjectableProps,
    RevisionsPageActions.getInjectableActions,
);

export default withRedux(withDevice(RevisionsPage));
