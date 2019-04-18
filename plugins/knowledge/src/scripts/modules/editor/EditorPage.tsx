/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { RouteComponentProps } from "react-router-dom";
import { connect } from "react-redux";
import apiv2 from "@library/apiv2";
import Modal from "@library/modal/Modal";
import EditorForm from "@knowledge/modules/editor/EditorForm";
import { LoadStatus } from "@library/@types/api/core";
import EditorPageModel, { IInjectableEditorProps } from "@knowledge/modules/editor/EditorPageModel";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import ModalSizes from "@library/modal/ModalSizes";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import Permission from "@library/features/users/Permission";
import ErrorPage from "@knowledge/pages/ErrorPage";
import QueryString from "@library/routing/QueryString";
import { DefaultError } from "@knowledge/modules/common/ErrorMessage";

interface IOwnProps
    extends RouteComponentProps<{
        id?: string;
    }> {}

interface IProps extends IOwnProps, IInjectableEditorProps {
    actions: EditorPageActions;
}

interface IState {
    categoryError?: string;
    titleError?: string;
    bodyError?: string;
}

/**
 * Page for editing an article.
 */
export class EditorPage extends React.Component<IProps, IState> {
    private id = uniqueIDFromPrefix("editorPage");

    public state: IState = {};

    public render() {
        return (
            <Modal
                titleID={this.titleID}
                size={ModalSizes.FULL_SCREEN}
                exitHandler={this.navigateToBacklink}
                elementToFocusOnExit={document.activeElement as HTMLElement}
            >
                <Permission permission="articles.add" fallback={<ErrorPage defaultError={DefaultError.PERMISSION} />}>
                    {this.renderQueryString()}
                    <EditorForm
                        titleID={this.titleID}
                        categoryError={this.state.categoryError}
                        titleError={this.state.titleError}
                        bodyError={this.state.bodyError}
                        removeCategoryError={this.removeCategoryError}
                        removeTitleError={this.removeTitleError}
                        removeBodyError={this.removeBodyError}
                    />
                </Permission>
            </Modal>
        );
    }

    /**
     * Initial setup for the page.
     *
     * Either creates an article and changes to the edit page, or gets an existing article.
     */
    public componentDidMount() {
        const { article, match, actions, history } = this.props;
        if (article.status === LoadStatus.PENDING) {
            if (match.params.id === undefined) {
                void actions.initializeAddPage(history);
            } else {
                void actions.initializeEditPage(history, parseInt(match.params.id, 10));
            }
        }
    }

    /**
     * Cleanup the page contents.
     */
    public componentWillUnmount() {
        this.props.actions.reset();
    }

    /**
     * Render a query string component from the form value.
     */
    private renderQueryString(): React.ReactNode {
        const { draft, saveDraft, submit } = this.props;

        // Only push a new draft query string if publish is not loading, because submitting/saving a draft can cause a race condition where we redirect to the article URL & where we "redirect" to the draft URL.
        if (submit.status !== LoadStatus.LOADING && saveDraft.status === LoadStatus.SUCCESS && draft.data) {
            return <QueryString value={{ draftID: draft.data.draftID }} syncOnFirstMount={true} />;
        } else {
            return null;
        }
    }

    private get titleID() {
        return this.id + "-title";
    }

    /**
     * Route back to the previous location if its available.
     */
    private navigateToBacklink = () => {
        if (this.props.history.length > 1) {
            this.props.history.goBack();
        } else {
            this.props.history.push("/kb");
        }
    };

    /**
     * Remove error message for body
     */
    private removeBodyError = () => {
        this.setState({
            bodyError: undefined,
        });
    };

    /**
     * Remove error message for title
     */
    private removeTitleError = () => {
        this.setState({
            titleError: undefined,
        });
    };

    /**
     * Remove error message for category
     */
    private removeCategoryError = () => {
        this.setState({
            categoryError: undefined,
        });
    };
}

function mapDispatchToProps(dispatch) {
    return {
        actions: new EditorPageActions(dispatch, apiv2),
    };
}

const withRedux = connect(
    EditorPageModel.getInjectableProps,
    mapDispatchToProps,
);

export default withRedux(EditorPage);
