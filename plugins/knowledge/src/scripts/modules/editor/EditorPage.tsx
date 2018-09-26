/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { withRouter, RouteComponentProps, Redirect } from "react-router-dom";
import { bindActionCreators } from "redux";
import { connect } from "react-redux";
import { DeltaOperation } from "quill/core";
import Modal from "@knowledge/components/Modal";
import { EditorForm, EditorLayout } from "@knowledge/modules/editor/components";
import { thunks, actions, model, constants } from "@knowledge/modules/editor/state";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import { IPostArticleRevisionRequestBody, Format } from "@knowledge/@types/api";

interface IOwnProps
    extends RouteComponentProps<{
            id?: number;
        }> {}

interface IProps extends IOwnProps {
    initPageFromLocation: typeof thunks.initPageFromLocation;
    pageState: model.IState;
    clearPageState: () => void;
    submitNewRevision: (data: IPostArticleRevisionRequestBody) => void;
}

/**
 * Page for editing an article.
 */
export class EditorPage extends React.Component<IProps> {
    public render() {
        const pageContent = (
            <EditorLayout backUrl={this.backLink}>
                <EditorForm submitHandler={this.formSubmit} />
            </EditorLayout>
        );

        if (this.isModal) {
            return (
                <Modal
                    exitHandler={this.navigateToBacklink}
                    appContainer={document.getElementById("app")!}
                    container={document.getElementById("modals")!}
                >
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
        this.props.initPageFromLocation(this.props.location);
    }

    /**
     * Cleanup the page contents.
     */
    public componentWillUnmount() {
        this.props.clearPageState();
    }

    /**
     * Handle the form submission for a revision.
     */
    private formSubmit = (content: DeltaOperation[], title: string) => {
        const { pageState } = this.props;
        const { article } = pageState;

        if (article.status === LoadStatus.SUCCESS) {
            const data: IPostArticleRevisionRequestBody = {
                articleID: article.data.articleID,
                name: title,
                body: JSON.stringify(content),
                format: Format.RICH,
            };
            this.props.submitNewRevision(data);
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
        pageState: state.knowledge.editor,
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
