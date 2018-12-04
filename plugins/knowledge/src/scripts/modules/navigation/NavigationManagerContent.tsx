/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { RefObject } from "react";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { folderClosed, folderOpen, article } from "@library/components/icons/tree";
import classNames from "classNames";
import { downTriangle, rightTriangle } from "@library/components/icons/common";
import { t } from "@library/application";
import ButtonSubmit from "@library/components/forms/ButtonSubmit";
import { ITreeItem } from "@atlaskit/tree";
import ModalConfirm from "@library/components/modal/ModalConfirm";
import Translate from "@library/components/translation/Translate";
import NavigationManagerItemIcon from "@knowledge/modules/navigation/NavigationManagerItemIcon";

interface IProps {
    className?: string;
    item: ITreeItem;
    handleEdit: (item: ITreeItem) => boolean;
    handleDelete: (item: ITreeItem, deleteButtonRef: React.RefObject<HTMLButtonElement>, callback: () => void) => void;
    hasChildren: boolean;
    expandItem: (itemId: string) => void;
    collapseItem: (itemId: string) => void;
}

interface IState {
    error: boolean;
    editMode: boolean;
    disabled: boolean;
    newName: string;
    delete: boolean;
}

export default class NavigationManagerContent extends React.Component<IProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    public state = {
        error: false,
        editMode: false,
        disabled: false,
        delete: false,
        newName: "",
    };
    public render() {
        const { item } = this.props;
        const name = item.data!.name;
        if (this.state.editMode) {
            return (
                <form className={classNames("navigationManger-editMode", { hasError: this.state.error })}>
                    <input value={this.props.item.name} name="renameItem" onKeyPress={this.handleOnChange} />
                    <Button onClick={this.stopEdit} className={"navigationManger-cancelRename"}>
                        {t("Cancel")}
                    </Button>
                    <ButtonSubmit className={"navigationManger-submitRename"}>{t("Apply")}</ButtonSubmit>
                </form>
            );
        } else {
            return (
                <div className={classNames("navigationManager-draggable", this.props.className)}>
                    <NavigationManagerItemIcon
                        expanded={!!item.isExpanded}
                        expandItem={this.props.expandItem}
                        collapseItem={this.props.collapseItem}
                        itemId={item.id}
                        className="tree-itemIcon"
                        hasChildren={this.props.hasChildren}
                    />
                    <span className="navigationManager-itemLabel">{name}</span>
                    <Button
                        onClick={this.startEdit}
                        className={classNames(
                            "navigationManager-rename",
                            "navigationManager-action",
                            this.props.className,
                        )}
                        baseClass={ButtonBaseClass.CUSTOM}
                        buttonRef={this.buttonRef}
                    >
                        {t("Edit")}
                    </Button>
                    <Button
                        onClick={this.showConfirmation}
                        className={classNames(
                            "navigationManager-delete",
                            "navigationManager-action",
                            this.props.className,
                        )}
                        disabled={this.state.disabled}
                        baseClass={ButtonBaseClass.CUSTOM}
                        buttonRef={this.buttonRef}
                    >
                        {t("Delete")}
                    </Button>
                    {this.state.delete && (
                        <ModalConfirm
                            title={(<Translate source={'Delete "<0/>"'} c0={name} /> as unknown) as string}
                            onCancel={this.closeConfirmation}
                            onConfirm={this.handleDelete}
                            elementToFocusOnExit={this.buttonRef.current!}
                        >
                            <Translate
                                source={'Are you sure you want to delete <0/>: "<1/>"'}
                                c0={this.getType(item.recordType)}
                                c1={name}
                            />
                        </ModalConfirm>
                    )}
                </div>
            );
        }
    }

    private startEdit = () => {
        this.setState({
            editMode: true,
        });
    };

    private stopEdit = () => {
        this.setState({
            editMode: false,
        });
    };

    private handleOnChange = e => {
        this.setState({
            error: false,
            newName: e.value,
        });
    };

    private submit = () => {
        const newName = this.state.newName;
        if (newName !== "" && newName !== this.props.item.name) {
            this.setState(
                {
                    disabled: true,
                },
                () => {
                    const editResult = this.props.handleEdit(this.props.item);
                    if (editResult) {
                        // Success
                        this.setState({
                            editMode: false,
                            disabled: false,
                            error: false,
                            newName: "",
                        });
                    } else {
                        // Problem
                        this.setState({
                            error: editResult,
                            disabled: false,
                        });
                    }
                },
            );
        }
    };

    private closeConfirmation = () => {
        this.setState({
            delete: false,
        });
    };
    private showConfirmation = () => {
        this.setState({
            delete: true,
        });
    };

    private handleDelete = () => {
        this.props.handleDelete(this.props.item, this.buttonRef, () => {
            this.setState({
                delete: false,
            });
        });
    };

    private getType = (type: string) => {
        switch (type) {
            case "article":
                return t("article");
            case "knowledgeCategory":
                return t("category");
            default:
                return type;
        }
    };
}
