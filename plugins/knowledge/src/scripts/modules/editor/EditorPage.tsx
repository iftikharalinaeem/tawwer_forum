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
import { EditorForm } from "@knowledge/modules/editor/components";
import EditorLayout from "@knowledge/modules/editor/components/EditorLayout";
import categoryModel from "@knowledge/modules/categories/CategoryModel";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import {
    IPostArticleRevisionRequestBody,
    Format,
    IKbCategoryFragment,
    IPatchArticleRequestBody,
} from "@knowledge/@types/api";
import { IEditorPageState } from "@knowledge/modules/editor/EditorPageModel";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import ModalSizes from "@library/components/modal/ModalSizes";

interface IOwnProps
    extends RouteComponentProps<{
            id?: number;
        }> {}

interface IProps extends IOwnProps {
    pageState: IEditorPageState;
    actions: EditorPageActions;
    locationCategory: IKbCategoryFragment | null;
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
            <EditorLayout backUrl={this.backLink}>
                <EditorForm
                    key={this.props.pageState.revision.status}
                    submitHandler={this.formSubmit}
                    revision={this.props.pageState.revision}
                    currentCategory={this.props.locationCategory}
                />
            </EditorLayout>
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
     *
     * Either creates an article and changes to the edit page, or gets an existing article.
     */
    public componentDidMount() {
        const { pageState, match, actions, history } = this.props;
        if (pageState.article.status !== LoadStatus.SUCCESS) {
            if (match.params.id === undefined) {
                void actions.createArticleForEdit(history);
            } else {
                void actions.fetchArticleForEdit(match.params.id);
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

    /**
     * Handle the form submission for a revision.
     */
    private formSubmit = (content: DeltaOperation[], title: string) => {
        const { pageState, history, actions, locationCategory } = this.props;
        const { article } = pageState;

        if (article.status === LoadStatus.SUCCESS) {
            const articleRequest: IPatchArticleRequestBody = {
                articleID: article.data.articleID,
            };

            if (locationCategory !== null) {
                articleRequest.knowledgeCategoryID = locationCategory.knowledgeCategoryID;
            }

            const revisionRequest: IPostArticleRevisionRequestBody = {
                articleID: article.data.articleID,
                name: title,
                body: JSON.stringify(content),
                format: Format.RICH,
            };
            void actions.updateArticle(articleRequest, revisionRequest, history);
        }
    };

    /**
     * Whether or not the we are navigated inside of a router.
     */
    private get isModal(): boolean {
        const { location } = this.props;
        return !!(location && location.state && location.state.modal);
    }

    private get backLink(): string | null {
        const { state } = this.props.location;
        return state && state.lastLocation ? state.lastLocation.pathname : "/kb";
    }

    /**
     * Route back to the previous location if its available.
     */
    private navigateToBacklink = () => {
        if (this.backLink) {
            this.props.history.goBack();
        } else {
            this.props.history.push("/kb");
        }
    };
}

/**
 * Map in the state from the redux store.
 */
function mapStateToProps(state: IStoreState) {
    let locationCategory: IKbCategoryFragment | null = null;
    const { editorPage, locationPicker } = state.knowledge;
    if (editorPage.article.status === LoadStatus.SUCCESS) {
        locationCategory = categoryModel.selectKbCategoryFragment(state, locationPicker.chosenCategoryID);
    }

    return {
        pageState: editorPage,
        locationCategory,
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
    mapStateToProps,
    mapDispatchToProps,
);

export default withRouter(withRedux(EditorPage));
