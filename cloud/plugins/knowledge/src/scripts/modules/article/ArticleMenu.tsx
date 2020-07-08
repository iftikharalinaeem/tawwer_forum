/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticle } from "@knowledge/@types/api/article";
import ArticleActions, { IArticleActionsProps } from "@knowledge/modules/article/ArticleActions";
import ArticleMenuModel, { IArticleMenuState } from "@knowledge/modules/article/ArticleMenuModel";
import { EditorRoute, RevisionsRoute } from "@knowledge/routes/pageRoutes";
import { LoadStatus, PublishStatus } from "@library/@types/api/core";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import ModalConfirm from "@library/modal/ModalConfirm";
import InsertUpdateMetas from "@library/result/InsertUpdateMetas";
import { t } from "@library/utility/appUtils";
import * as React from "react";
import { connect } from "react-redux";
import ArticleFeaturedMenuItem from "@knowledge/modules/article/components/ArticleFeaturedMenuItem";
import { IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";

interface IProps extends IArticleMenuState, IArticleActionsProps {
    article: IArticle;
    knowledgeBase?: IKnowledgeBase;
    buttonClassName?: string;
}

interface IState {
    showDeleteDialogue: boolean;
    showRestoreDialogue: boolean;
}

/**
 * Generates drop down menu for Article page
 */
export class ArticleMenu extends React.PureComponent<IProps, IState> {
    public state = {
        showDeleteDialogue: false,
        showRestoreDialogue: false,
    };

    public render() {
        const { article } = this.props;

        const deleteButton = <DropDownItemButton name={t("Delete")} onClick={this.openDeleteDialogue} />;
        const restoreButton = <DropDownItemButton name={t("Restore")} onClick={this.openRestoreDialogue} />;

        const { insertUser, updateUser, dateInserted, dateUpdated } = article;

        const classesDropDown = dropDownClasses();

        return (
            <>
                <DropDown
                    id={this.domID}
                    name={t("Article Options")}
                    buttonClassName={this.props.buttonClassName}
                    renderLeft={true}
                    mobileTitle={t("Article")}
                    flyoutType={FlyoutType.LIST}
                >
                    <InsertUpdateMetas
                        dateInserted={dateInserted}
                        dateUpdated={dateUpdated}
                        insertUser={insertUser!}
                        updateUser={updateUser!}
                    />
                    <DropDownItemSeparator />
                    <DropDownItem>
                        <EditorRoute.Link
                            className={classesDropDown.action}
                            data={{
                                knowledgeCategoryID: this.props.article.knowledgeCategoryID,
                                knowledgeBaseID: this.props.article.knowledgeBaseID,
                            }}
                        >
                            {t("New Article")}
                        </EditorRoute.Link>
                    </DropDownItem>
                    <DropDownItemSeparator />
                    <DropDownItem>
                        <EditorRoute.Link
                            className={classesDropDown.action}
                            data={{ articleID: this.props.article.articleID }}
                        >
                            {t("Edit")}
                        </EditorRoute.Link>
                    </DropDownItem>
                    <DropDownItem>
                        <RevisionsRoute.Link className={classesDropDown.action} data={article}>
                            {t("Revision History")}
                        </RevisionsRoute.Link>
                    </DropDownItem>
                    <ArticleFeaturedMenuItem article={article} />
                    <DropDownItemSeparator />
                    {this.props.article.status === PublishStatus.PUBLISHED ? deleteButton : restoreButton}
                </DropDown>
                {this.renderDeleteModal()}
                {this.renderRestoreModal()}
            </>
        );
    }

    /**
     * Render the delete modal if it should be shown.
     */
    private renderDeleteModal(): React.ReactNode {
        return (
            <ModalConfirm
                isVisible={this.state.showDeleteDialogue}
                title={t("Delete an Article")}
                onCancel={this.closeDeleteDialogue}
                onConfirm={this.handleDeleteDialogueConfirm}
                isConfirmLoading={this.props.delete.status === LoadStatus.LOADING}
                elementToFocusOnExit={document.activeElement as HTMLElement}
            >
                {t("This is a non-destructive action. You will be able to restore your article if you wish.")}
            </ModalConfirm>
        );
    }

    /**
     * Handler for the confirmation of the delete dialogue.
     *
     * Calls to update the article status and closes the dialogue after.
     */
    private handleDeleteDialogueConfirm = async () => {
        const { articleActions, article } = this.props;
        await articleActions.patchStatus({ articleID: article.articleID, status: PublishStatus.DELETED });
        this.closeDeleteDialogue();
    };

    /**
     * Open the delete dialogue.
     */
    private openDeleteDialogue = () => {
        this.setState({ showDeleteDialogue: true });
    };

    /**
     * Close the delete dialogue.
     */
    private closeDeleteDialogue = () => {
        this.setState({ showDeleteDialogue: false });
    };

    /**
     * Handler for the confirmation of the restore dialogue.
     *
     * Calls to update the article status and closes the dialogue after.
     */
    private handleRestoreDialogueConfirm = async () => {
        const { articleActions, article } = this.props;
        await articleActions.patchStatus({ articleID: article.articleID, status: PublishStatus.PUBLISHED });
        this.closeRestoreDialogue();
    };

    /**
     * Open the restore dialogue.
     */
    private openRestoreDialogue = () => {
        this.setState({ showRestoreDialogue: true });
    };

    /**
     * Close the restore dialogue.
     */
    private closeRestoreDialogue = () => {
        this.setState({ showRestoreDialogue: false });
    };

    /**
     * Render the restore modal if it should be shown.
     */
    private renderRestoreModal(): React.ReactNode {
        return (
            <ModalConfirm
                isVisible={this.state.showRestoreDialogue}
                title={t("Restore an Article")}
                onCancel={this.closeRestoreDialogue}
                onConfirm={this.handleRestoreDialogueConfirm}
                isConfirmLoading={this.props.delete.status === LoadStatus.LOADING}
                elementToFocusOnExit={document.activeElement as HTMLElement}
            >
                {t("This is a non-destructive action. You will be able to restore your article if you wish.")}
            </ModalConfirm>
        );
    }

    private get domID(): string {
        return "articleMenuDropDown-" + this.props.article.articleID;
    }

    private doNothing = e => {
        e.stopPropagation();
    };
}

const withRedux = connect(ArticleMenuModel.mapStateToProps, ArticleActions.mapDispatchToProps);

export default withRedux(ArticleMenu);
