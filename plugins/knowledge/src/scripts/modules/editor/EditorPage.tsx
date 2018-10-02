/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { withRouter, RouteComponentProps, Redirect } from "react-router-dom";
import { bindActionCreators } from "redux";
import { connect } from "react-redux";
import { DeltaOperation } from "quill/core";
import Modal, { ModalSizes } from "@knowledge/components/Modal";
import { EditorForm, EditorLayout } from "@knowledge/modules/editor/components";
import { thunks, actions, model, constants } from "@knowledge/modules/editor/state";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import { IPostArticleRevisionRequestBody, Format } from "@knowledge/@types/api";
import PageLocation from "@knowledge/modules/locationPicker/PageLocation";

interface IOwnProps
    extends RouteComponentProps<{
            id?: number;
        }> {}

interface IProps extends IOwnProps {
    pageState: model.IState;
    clearPageState: () => void;
    initPageFromLocation: typeof thunks.initPageFromLocation;
    submitNewRevision: typeof thunks.submitNewRevision;
    data: any; // temp
}

interface IState {
    showFolderPicker: boolean;
}

/**
 * Page for editing an article.
 */
export class EditorPage extends React.Component<IProps, IState> {
    public state = {
        showFolderPicker: false,
    };

    public render() {
        const pageContent = (
            <React.Fragment>
                <PageLocation children={this.props.data} />
                <EditorLayout backUrl={this.backLink}>
                    <EditorForm submitHandler={this.formSubmit} />
                </EditorLayout>
            </React.Fragment>
        );

        if (this.isModal) {
            return (
                <Modal size={ModalSizes.FULL_SCREEN} exitHandler={this.navigateToBacklink}>
                    {pageContent}
                </Modal>
            );
        } else {
            return pageContent;
        }
    }

    /**
     * Initial setup for the page.
     */
    public componentDidMount() {
        this.props.initPageFromLocation(this.props.history);
    }

    /**
     * Cleanup the page contents.
     */
    public componentWillUnmount() {
        this.props.clearPageState();
    }

    public showLocationPicker() {
        this.setState({
            showFolderPicker: true,
        });
    }

    public hideLocationPicker() {
        this.setState({
            showFolderPicker: false,
        });
    }

    /**
     * Handle the form submission for a revision.
     */
    private formSubmit = (content: DeltaOperation[], title: string) => {
        const { pageState, history } = this.props;
        const { article } = pageState;

        if (article.status === LoadStatus.SUCCESS) {
            const data: IPostArticleRevisionRequestBody = {
                articleID: article.data.articleID,
                name: title,
                body: JSON.stringify(content),
                format: Format.RICH,
            };
            this.props.submitNewRevision(data, history);
        }
    };

    /**
     * Whether or not the we are navigated inside of a router.
     */
    private get isModal(): boolean {
        const { location } = this.props;
        return !!(location && location.state && location.state.modal);
    }

    private get backLink(): string {
        const { state } = this.props.location;
        return state && state.lastLocation ? state.lastLocation.pathname : "/kb";
    }

    /**
     * Route back to the previous location if its available.
     */
    private navigateToBacklink = () => {
        this.props.history.push(this.backLink);
    };
}

/**
 * Map in the state from the redux store.
 */
function mapStateToProps(state: IStoreState) {
    return {
        pageState: state.knowledge.editorPage,
    };
}

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch) {
    const { initPageFromLocation, submitNewRevision } = thunks;
    const { clearPageState } = actions;
    return bindActionCreators({ initPageFromLocation, submitNewRevision, clearPageState }, dispatch);
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRouter(withRedux(EditorPage));
