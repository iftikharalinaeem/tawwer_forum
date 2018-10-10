/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { withRouter, RouteComponentProps } from "react-router-dom";
import { bindActionCreators } from "redux";
import { connect } from "react-redux";
import { DeltaOperation } from "quill/core";
import { Modal } from "@library/components/modal";
import apiv2 from "@library/apiv2";
import { EditorForm, EditorLayout } from "@knowledge/modules/editor/components";
import { model as categoryModel } from "@knowledge/modules/categories/state";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import { IPostArticleRevisionRequestBody, Format, IKbCategoryFragment } from "@knowledge/@types/api";
import { ModalSizes } from "@library/components/modal/ModalSizes";
import { IEditorPageState } from "@knowledge/modules/editor/EditorPageReducer";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";

interface IOwnProps
    extends RouteComponentProps<{
            id?: number;
        }> {}

interface IProps extends IOwnProps {
    pageState: IEditorPageState;
    actions: EditorPageActions;
    articleCategory: IKbCategoryFragment;
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
                <EditorLayout backUrl={this.backLink}>
                    <EditorForm
                        submitHandler={this.formSubmit}
                        revision={this.props.pageState.revision}
                        articleCategory={this.props.articleCategory}
                    />
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
        void this.props.actions.initPageFromLocation(this.props.history);
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
        const { pageState, history, actions } = this.props;
        const { article } = pageState;

        if (article.status === LoadStatus.SUCCESS) {
            const data: IPostArticleRevisionRequestBody = {
                articleID: article.data.articleID,
                name: title,
                body: JSON.stringify(content),
                format: Format.RICH,
            };
            void actions.submitNewRevision(data, history);
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
    let articleCategory;
    const { editorPage } = state.knowledge;
    if (editorPage.article.status === LoadStatus.SUCCESS) {
        articleCategory = categoryModel.selectKbCategoryFragment(state, editorPage.article.data.knowledgeCategoryID);
    }

    return {
        pageState: editorPage,
        articleCategory,
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
