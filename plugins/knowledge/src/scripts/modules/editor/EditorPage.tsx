/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { RouteComponentProps } from "react-router-dom";
import { connect } from "react-redux";
import apiv2 from "@library/apiv2";
import Modal from "@library/components/modal/Modal";
import EditorForm from "@knowledge/modules/editor/EditorForm";
import { LoadStatus } from "@library/@types/api";
import EditorPageModel, { IInjectableEditorProps } from "@knowledge/modules/editor/EditorPageModel";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import ModalSizes from "@library/components/modal/ModalSizes";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import Permission from "@library/users/Permission";
import ErrorPage, { DefaultError } from "@knowledge/routes/ErrorPage";
import QueryString from "@library/components/navigation/QueryString";
import { withDevice } from "@library/contexts/DeviceContext";
import { IDeviceProps } from "@library/components/DeviceChecker";

interface IOwnProps
    extends RouteComponentProps<{
        id?: string;
    }> {}

interface IProps extends IOwnProps, IInjectableEditorProps {
    actions: EditorPageActions;
}

/**
 * Page for editing an article.
 */
export class EditorPage extends React.PureComponent<IProps> {
    private id = uniqueIDFromPrefix("editorPage");

    public state = {
        showFolderPicker: false,
    };

    public render() {
        return (
            <Modal
                titleID={this.titleID}
                size={ModalSizes.FULL_SCREEN}
                exitHandler={this.navigateToBacklink}
                elementToFocusOnExit={document.activeElement as HTMLElement}
                isWholePage={true}
            >
                <Permission permission="articles.add" fallback={<ErrorPage defaultError={DefaultError.PERMISSION} />}>
                    {this.renderQueryString()}
                    <EditorForm titleID={this.titleID} />
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
            return <QueryString value={{ draftID: draft.data.draftID }} />;
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
