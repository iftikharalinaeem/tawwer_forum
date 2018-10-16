/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import DropDown from "@library/components/dropdown/DropDown";
import { t } from "@library/application";
import { InlineTypes } from "@library/components/Sentence";
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

        return (
            <React.Fragment>
                <DropDown id={this.domID} name={t("Article Options")} buttonClassName={this.props.buttonClassName}>
                    <DropDownItemMetas>{this.publishedMeta}</DropDownItemMetas>
                    <DropDownItemMetas>{this.updatedMeta}</DropDownItemMetas>
                    <DropDownItemSeparator />
                    <DropDownItemButton name={t("Customize SEO")} onClick={this.buttonClick} />
                    <DropDownItemButton name={t("Move")} onClick={this.buttonClick} />
                    <DropDownItemLink name={t("Edit article")} to={editUrl} isModalLink={true} />
                    <DropDownItemSeparator />
                    <DropDownItemButton name={t("Revision History")} onClick={this.buttonClick} />
                    <DropDownItemSeparator />
                    {this.props.article.status === ArticleStatus.PUBLISHED ? deleteButton : restoreButton}
                </DropDown>
                {this.renderDeleteModal()}
                {this.renderRestoreModal()}
            </React.Fragment>
        );
    }

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

    private handleDeleteDialogueConfirm = async () => {
        const { articleActions, article } = this.props;
        await articleActions.patchStatus({ articleID: article.articleID, status: ArticleStatus.DELETED });
        this.closeDeleteDialogue();
    };

    private openDeleteDialogue = () => {
        this.setState({ showDeleteDialogue: true });
    };

    private closeDeleteDialogue = () => {
        this.setState({ showDeleteDialogue: false });
    };

    private handleRestoreDialogueConfirm = async () => {
        const { articleActions, article } = this.props;
        await articleActions.patchStatus({ articleID: article.articleID, status: ArticleStatus.PUBLISHED });
        this.closeRestoreDialogue();
    };

    private openRestoreDialogue = () => {
        this.setState({ showRestoreDialogue: true });
    };

    private closeRestoreDialogue = () => {
        this.setState({ showRestoreDialogue: false });
    };

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

    private buttonClick = () => {
        alert("Click works");
    };

    private publishedMeta = [
        {
            type: InlineTypes.TEXT,
            children: "Published ",
        },
        {
            type: InlineTypes.DATETIME,
            timeStamp: "2017-05-20 10:00",
            children: "20th May, 2018 10:00 AM",
        },
        {
            type: InlineTypes.TEXT,
            children: t(" by "),
        },
        {
            type: InlineTypes.LINK,
            to: "#user/Todd_Burry",
            children: "Todd Burry",
        },
    ];

    private updatedMeta = [
        {
            type: InlineTypes.TEXT,
            children: "Updated ",
        },
        {
            type: InlineTypes.DATETIME,
            timeStamp: "2017-05-20 10:00",
            children: "20th May, 2018 10:00 AM",
        },
        {
            type: InlineTypes.TEXT,
            children: t(" by "),
        },
        {
            type: InlineTypes.LINK,
            to: "#user/Todd_Burry",
            children: "Todd Burry",
        },
    ];
}

const withRedux = connect(
    ArticleMenuModel.mapStateToProps,
    ArticleActions.mapDispatchToProps,
);

export default withRedux<IProps>(ArticleMenu);
