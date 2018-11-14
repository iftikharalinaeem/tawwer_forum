/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { withRouter, RouteComponentProps } from "react-router-dom";
import { connect } from "react-redux";
import { DeltaOperation } from "quill/core";
import apiv2 from "@library/apiv2";
import Modal from "@library/components/modal/Modal";
import EditorForm from "@knowledge/modules/editor/components/EditorForm";
import { LoadStatus } from "@library/@types/api";
import { Format, IPatchArticleRequestBody } from "@knowledge/@types/api";
import EditorPageModel, { IInjectableEditorProps } from "@knowledge/modules/editor/EditorPageModel";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import ModalSizes from "@library/components/modal/ModalSizes";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import Permission from "@library/users/Permission";
import ErrorPage, { DefaultErrors } from "@knowledge/routes/ErrorPage";
import qs from "qs";
import { withDevice } from "@library/contexts/DeviceContext";
import { IDeviceProps } from "@library/components/DeviceChecker";

interface IOwnProps
    extends RouteComponentProps<{
            id?: string;
        }> {}

interface IProps extends IOwnProps, IInjectableEditorProps, IDeviceProps {
    actions: EditorPageActions;
}

interface IState {
    showFolderPicker: boolean;
}

/**
 * Page for editing an article.
 */
export class EditorPage extends React.Component<IProps, IState> {
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
            >
                <Permission
                    permission="articles.add"
                    fallback={<ErrorPage loadable={DefaultErrors.PERMISSION_LOADABLE} />}
                >
                    <EditorForm
                        key={this.props.article.status}
                        content={
                            this.props.revision.status !== LoadStatus.PENDING ? this.props.revision : this.props.article
                        }
                        article={this.props.article}
                        submitHandler={this.formSubmit}
                        currentCategory={this.props.locationCategory}
                        isSubmitLoading={this.isSubmitLoading}
                        titleID={this.titleID}
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
        const queryParams = qs.parse(history.location.search.replace(/^\?/, ""));

        if (article.status === LoadStatus.PENDING) {
            if (match.params.id === undefined) {
                void actions.createArticleForEdit(history);
            } else {
                const articleID = parseInt(match.params.id, 10);
                if (queryParams.revisionID) {
                    const revisionID = parseInt(queryParams.revisionID, 10);
                    void actions.fetchArticleAndRevisionForEdit(articleID, revisionID);
                } else {
                    void actions.fetchArticleForEdit(articleID);
                }
            }
        }
    }

    /**
     * Cleanup the page contents.
     */
    public componentWillUnmount() {
        this.props.actions.reset();
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

    private get isSubmitLoading(): boolean {
        const { submit } = this.props;
        return submit.status === LoadStatus.LOADING;
    }

    /**
     * Handle the form submission for a revision.
     */
    private formSubmit = (content: DeltaOperation[], title: string) => {
        const { article, history, actions, locationCategory } = this.props;

        if (article.status === LoadStatus.SUCCESS && article.data) {
            const articleRequest: IPatchArticleRequestBody = {
                articleID: article.data.articleID,
                name: title,
                body: JSON.stringify(content),
                format: Format.RICH,
            };

            if (locationCategory !== null) {
                articleRequest.knowledgeCategoryID = locationCategory.knowledgeCategoryID;
            }
            void actions.updateArticle(articleRequest, history);
        }
    };

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

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch) {
    return {
        actions: new EditorPageActions(dispatch, apiv2),
    };
}

const withRedux = connect(
    EditorPageModel.getInjectableProps,
    mapDispatchToProps,
);

export default withDevice(withRouter(withRedux(EditorPage)));
