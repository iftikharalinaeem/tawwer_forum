/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import EditorLayout from "@knowledge/pages/editor/components/EditorLayout";
import { withRouter, RouteComponentProps } from "react-router-dom";
import Modal from "@knowledge/components/Modal";
import { componentActions as pageActions } from "@knowledge/pages/editor/editorPageActions";
import { componentActions as articleActions } from "@knowledge/state/articleActions";
import { connect } from "react-redux";
import { IStoreState, IEditorPageState } from "@knowledge/@types/state";
import { LoadStatus } from "@library/@types/api";

interface IOwnProps
    extends RouteComponentProps<{
            id?: number;
        }> {}

interface IProps extends IOwnProps {
    initArticle: () => void;
    pageState: IEditorPageState;
    clearPageState: () => void;
}

/**
 * Page for editing an article.
 */
export class EditorPage extends React.Component<IProps> {
    public render() {
        if (this.isModal) {
            return (
                <Modal
                    exitHandler={this.navigateToBacklink}
                    appContainer={document.getElementById("app")!}
                    container={document.getElementById("modals")!}
                >
                    <EditorLayout />
                </Modal>
            );
        } else {
            return <EditorLayout />;
        }
    }

    public componentDidMount() {
        this.ensureArticle();
        this.ensureCorrectURL();
    }

    public componentDidUpdate() {
        this.ensureCorrectURL();
    }

    public componentWillUnmount() {
        this.props.clearPageState();
    }

    private ensureCorrectURL() {
        const { history, pageState } = this.props;
        if (history.location.pathname === "/kb/articles/add" && pageState.data && pageState.data!.article) {
            const replacementUrl = `/kb/articles/${pageState.data!.article!.articleID}/editor`;
            const newLocation = {
                ...history.location,
                pathname: replacementUrl,
            };
            history.replace(newLocation);
        }
    }

    private ensureArticle() {
        const { pageState, initArticle, match } = this.props;
        if (pageState.status === LoadStatus.PENDING) {
            if (match.params.id != null) {
                // fetchArticle();
            } else {
                initArticle();
            }
        }
    }

    /**
     * Whether or not the we are navigated inside of a router.
     */
    private get isModal(): boolean {
        const { location } = this.props;
        return !!(location && location.state && location.state.modal);
    }

    /**
     * Route back to the previous location if its available.
     */
    private navigateToBacklink = () => {
        this.props.history.push(this.props.location.state.lastLocation || "/kb");
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
function mapDispatchToProps(dispatch, props: IOwnProps) {
    return {
        initArticle: () => dispatch(articleActions.postArticle({ knowledgeCategoryID: 0 })),
        clearPageState: () => dispatch(pageActions.clearEditorPageState()),
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRouter(withRedux(EditorPage));
