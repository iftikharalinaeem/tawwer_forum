/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ArticleStatus, IArticle } from "@knowledge/@types/api";
import ArticleActions, { IArticleActionsProps } from "@knowledge/modules/article/ArticleActions";
import ArticleMenuModel, { IArticleMenuState } from "@knowledge/modules/article/ArticleMenuModel";
import InsertUpdateMetas from "@knowledge/modules/common/InsertUpdateMetas";
import { EditorRoute, RevisionsRoute } from "@knowledge/routes/pageRoutes";
import { LoadStatus } from "@library/@types/api";
import { t } from "@library/application";
import { Devices } from "@library/components/DeviceChecker";
import {
    DropDownItem,
    DropDownItemButton,
    DropDownItemLink,
    DropDownItemSeparator,
} from "@library/components/dropdown";
import DropDown from "@library/components/dropdown/DropDown";
import { ModalConfirm } from "@library/components/modal";
import Permission from "@library/users/Permission";
import * as React from "react";
import { connect } from "react-redux";

interface IProps extends IArticleMenuState, IArticleActionsProps {
    article: IArticle;
    buttonClassName?: string;
    device?: Devices;
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
        const isMobile = this.props.device === Devices.MOBILE;

        const { insertUser, updateUser, dateInserted, dateUpdated } = article;

        return (
            <Permission permission="articles.add">
                <DropDown
                    id={this.domID}
                    name={t("Article Options")}
                    buttonClassName={this.props.buttonClassName}
                    renderLeft={true}
                    openAsModal={this.props.device === Devices.MOBILE}
                    title={isMobile ? t("Article") : undefined}
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
                            data={{ articleID: this.props.article.articleID }}
                            className={DropDownItemLink.CSS_CLASS}
                        >
                            {t("Edit article")}
                        </EditorRoute.Link>
                    </DropDownItem>
                    <DropDownItem>
                        <EditorRoute.Link
                            data={{
                                knowledgeCategoryID: this.props.article.knowledgeCategoryID!,
                            }}
                            className={DropDownItemLink.CSS_CLASS}
                        >
                            {t("New Article")}
                        </EditorRoute.Link>
                    </DropDownItem>
                    <DropDownItem>
                        <RevisionsRoute.Link data={article} className={DropDownItemLink.CSS_CLASS}>
                            {t("Revision History")}
                        </RevisionsRoute.Link>
                    </DropDownItem>
                    <DropDownItemSeparator />
                    {this.props.article.status === ArticleStatus.PUBLISHED ? deleteButton : restoreButton}
                </DropDown>
                {this.renderDeleteModal()}
                {this.renderRestoreModal()}
            </Permission>
        );
    }

    /**
     * Render the delete modal if it should be shown.
     */
    private renderDeleteModal(): React.ReactNode {
        return (
            this.state.showDeleteDialogue && (
                <ModalConfirm
                    title={t("Delete an Article")}
                    onCancel={this.closeDeleteDialogue}
                    onConfirm={this.handleDeleteDialogueConfirm}
                    isConfirmLoading={this.props.delete.status === LoadStatus.LOADING}
                    elementToFocusOnExit={document.activeElement as HTMLElement}
                >
                    {t("This is a non-destructive action. You will be able to restore your article if you wish.")}
                </ModalConfirm>
            )
        );
    }

    /**
     * Handler for the confirmation of the delete dialogue.
     *
     * Calls to update the article status and closes the dialogue after.
     */
    private handleDeleteDialogueConfirm = async () => {
        const { articleActions, article } = this.props;
        await articleActions.patchStatus({ articleID: article.articleID, status: ArticleStatus.DELETED });
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
        await articleActions.patchStatus({ articleID: article.articleID, status: ArticleStatus.PUBLISHED });
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
            this.state.showRestoreDialogue && (
                <ModalConfirm
                    title={t("Restore an Article")}
                    onCancel={this.closeRestoreDialogue}
                    onConfirm={this.handleRestoreDialogueConfirm}
                    isConfirmLoading={this.props.delete.status === LoadStatus.LOADING}
                    elementToFocusOnExit={document.activeElement as HTMLElement}
                >
                    {t("This is a non-destructive action. You will be able to restore your article if you wish.")}
                </ModalConfirm>
            )
        );
    }

    private get domID(): string {
        return "articleMenuDropDown-" + this.props.article.articleID;
    }

    private doNothing = e => {
        e.stopPropagation();
    };
}

const withRedux = connect(
    ArticleMenuModel.mapStateToProps,
    ArticleActions.mapDispatchToProps,
);

export default withRedux(ArticleMenu);
