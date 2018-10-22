/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import DropDown from "@library/components/dropdown/DropDown";
import { t, makeProfileUrl } from "@library/application";
import { InlineTypes } from "@library/components/translation/Sentence";
import { IArticle, ArticleStatus } from "@knowledge/@types/api";
import {
    DropDownItemLink,
    DropDownItemButton,
    DropDownItemMetas,
    DropDownItemSeparator,
} from "@library/components/dropdown";
import { makeEditUrl } from "@knowledge/modules/editor/route";
import { ModalConfirm } from "@library/components/modal";
import { connect } from "react-redux";
import ArticleMenuModel, { IArticleMenuState } from "@knowledge/modules/article/ArticleMenuModel";
import ArticleActions, { IArticleActionsProps } from "@knowledge/modules/article/ArticleActions";
import { LoadStatus } from "@library/@types/api";
import Translate from "@library/components/translation/Translate";
import DateTime from "@library/components/DateTime";
import { Link } from "react-router-dom";

interface IProps extends IArticleMenuState, IArticleActionsProps {
    article: IArticle;
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
        const editUrl = makeEditUrl(article.articleID);

        const deleteButton = <DropDownItemButton name={t("Delete")} onClick={this.openDeleteDialogue} />;
        const restoreButton = <DropDownItemButton name={t("Restore")} onClick={this.openRestoreDialogue} />;

        const { insertUser, updateUser, dateInserted, dateUpdated } = article;

        return (
            <React.Fragment>
                <DropDown id={this.domID} name={t("Article Options")} buttonClassName={this.props.buttonClassName}>
                    <DropDownItemMetas>
                        <Translate
                            source="Published on <0/> by <1/>"
                            c0={<DateTime timestamp={dateInserted} />}
                            c1={
                                <a className="dropDownMeta-link" href={makeProfileUrl(insertUser!.name)}>
                                    {insertUser!.name}
                                </a>
                            }
                        />
                    </DropDownItemMetas>
                    <DropDownItemMetas>
                        <Translate
                            source="Updated on <0/> by <1/>"
                            c0={<DateTime timestamp={dateUpdated} />}
                            c1={
                                <a className="dropDownItem-link" href={makeProfileUrl(updateUser!.name)}>
                                    {updateUser!.name}
                                </a>
                            }
                        />
                    </DropDownItemMetas>
                    <DropDownItemSeparator />
                    <DropDownItemButton name={t("Customize SEO")} onClick={this.dummyClick} />
                    <DropDownItemButton name={t("Move")} onClick={this.dummyClick} />
                    <DropDownItemLink name={t("Edit article")} to={editUrl} isModalLink={true} />
                    <DropDownItemSeparator />
                    <DropDownItemButton name={t("Revision History")} onClick={this.dummyClick} />
                    <DropDownItemSeparator />
                    {this.props.article.status === ArticleStatus.PUBLISHED ? deleteButton : restoreButton}
                </DropDown>
                {this.renderDeleteModal()}
                {this.renderRestoreModal()}
            </React.Fragment>
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
                >
                    {t("This is a non-destructive action. You will be able to restore your article if you wish.")}
                </ModalConfirm>
            )
        );
    }

    private get domID(): string {
        return "articleMenuDropDown-" + this.props.article.articleID;
    }

    /**
     * Fallback click handle until all functionaility has been implemented.
     */
    private dummyClick = () => {
        alert("Click works");
    };
}

const withRedux = connect(
    ArticleMenuModel.mapStateToProps,
    ArticleActions.mapDispatchToProps,
);

export default withRedux<IProps>(ArticleMenu);
