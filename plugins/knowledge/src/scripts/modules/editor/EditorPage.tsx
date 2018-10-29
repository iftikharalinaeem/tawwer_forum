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
import categoryModel from "@knowledge/modules/categories/CategoryModel";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import { Format, IKbCategoryFragment, IPatchArticleRequestBody } from "@knowledge/@types/api";
import { IEditorPageState } from "@knowledge/modules/editor/EditorPageModel";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import ModalSizes from "@library/components/modal/ModalSizes";
import { uniqueIDFromPrefix } from "@library/componentIDs";

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
    private id;

    public state = {
        showFolderPicker: false,
    };

    public constructor(props: IProps) {
        super(props);
        this.id = uniqueIDFromPrefix("editorPage");
    }

    public render() {
        const pageContent = (
            <EditorForm
                backUrl=""
                key={this.props.pageState.article.status}
                article={this.props.pageState.article}
                submitHandler={this.formSubmit}
                currentCategory={this.props.locationCategory}
                isSubmitLoading={this.isSubmitLoading}
                titleID={this.titleID}
            />
        );

        return (
            <Modal titleID={this.titleID} size={ModalSizes.FULL_SCREEN} exitHandler={this.navigateToBacklink}>
                {pageContent}
            </Modal>
        );
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

    private get isSubmitLoading(): boolean {
        const { submit } = this.props.pageState;
        return submit.status === LoadStatus.LOADING;
    }

    /**
     * Handle the form submission for a revision.
     */
    private formSubmit = (content: DeltaOperation[], title: string) => {
        const { pageState, history, actions, locationCategory } = this.props;
        const { article } = pageState;

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
