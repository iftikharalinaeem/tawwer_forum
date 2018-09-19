/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import EditorForm from "@knowledge/pages/editor/components/EditorForm";
import { withRouter, RouteComponentProps, Redirect } from "react-router-dom";
import Modal from "@knowledge/components/Modal";
import { componentActions as pageActions } from "@knowledge/pages/editor/editorPageActions";
import { componentActions as articleActions } from "@knowledge/state/articleActions";
import { componentActions as revisionActions } from "@knowledge/state/revisionActions";
import { connect } from "react-redux";
import { IStoreState, IEditorPageState } from "@knowledge/@types/state";
import { LoadStatus } from "@library/@types/api";
import { DeltaOperation } from "quill/core";
import { IPostArticleRevisionRequestBody, Format } from "@knowledge/@types/api";

interface IOwnProps
    extends RouteComponentProps<{
            id?: number;
        }> {}

interface IProps extends IOwnProps {
    initArticle: () => void;
    getArticle: (id: number) => void;
    postRevision: typeof revisionActions.postRevision;
    pageState: IEditorPageState;
    clearPageState: () => void;
}

/**
 * Page for editing an article.
 */
export class EditorPage extends React.Component<IProps> {
    public render() {
        const { article, revision } = this.props.pageState;

        // If we have an article and revision posted we should redirect to that article.
        // Right now the workflow for getting the canonical URL is a bit tedious so I'm redirecting
        // To a non-canonical version. This should be fixed later.
        if (article.status === LoadStatus.SUCCESS && revision.status === LoadStatus.SUCCESS) {
            return <Redirect to={`/kb/articles/-${article.data.articleID}`} />;
        }

        if (this.isModal) {
            return (
                <Modal
                    exitHandler={this.navigateToBacklink}
                    appContainer={document.getElementById("app")!}
                    container={document.getElementById("modals")!}
                >
                    <EditorForm submitHandler={this.formSubmit} />
                </Modal>
            );
        } else {
            return <EditorForm submitHandler={this.formSubmit} />;
        }
    }

    /**
     * Initial setup for the page.
     */
    public componentDidMount() {
        this.ensureArticle();
        this.ensureCorrectURL();
    }

    /**
     * Continuous checking as we get new data for the page.
     */
    public componentDidUpdate() {
        this.ensureCorrectURL();
    }

    /**
     * Cleanup the page contents.
     */
    public componentWillUnmount() {
        this.props.clearPageState();
    }

    /**
     * Ensure that the page is on the correct URL.
     *
     * If we're on /articles/add and have now created an article we need to change the URL.
     * This replacement should be seemless and not create a new history entry.
     */
    private ensureCorrectURL() {
        const { history, pageState } = this.props;
        const { article } = pageState;
        if (history.location.pathname === "/kb/articles/add" && article.status === LoadStatus.SUCCESS) {
            const replacementUrl = `/kb/articles/${article.data.articleID}/editor`;
            const newLocation = {
                ...history.location,
                pathname: replacementUrl,
            };
            history.replace(newLocation);
        }
    }

    /**
     * Ensure that we have an article for the editor.
     * Either fetch one if we have an ID in the URL or create a new one.
     */
    private ensureArticle() {
        const { pageState, initArticle, getArticle, match } = this.props;
        if (pageState.article.status === LoadStatus.PENDING) {
            if (match.params.id != null) {
                getArticle(match.params.id);
            } else {
                initArticle();
            }
        }
    }

    /**
     * Handle the form submission for a revision.
     */
    private formSubmit = (content: DeltaOperation[], title: string) => {
        const { pageState, postRevision } = this.props;
        const { article } = pageState;

        if (article.status === LoadStatus.SUCCESS) {
            const data: IPostArticleRevisionRequestBody = {
                articleID: article.data.articleID,
                name: title,
                body: JSON.stringify(content),
                format: Format.RICH,
            };
            postRevision(data);
        }
    };

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
        getArticle: (id: number) => dispatch(articleActions.getArticle(id)),
        postRevision: (body: IPostArticleRevisionRequestBody) => dispatch(revisionActions.postRevision(body)),
        initArticle: () => dispatch(articleActions.postArticle({ knowledgeCategoryID: 0 })),
        clearPageState: () => dispatch(pageActions.clearEditorPageState()),
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRouter(withRedux(EditorPage));
