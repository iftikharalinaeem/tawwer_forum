/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { RefObject } from "react";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { folderClosed, folderOpen, article } from "@library/components/icons/navigationManager";
import classNames from "classnames";
import { downTriangle, rightTriangle } from "@library/components/icons/common";
import { t } from "@library/application";
import ButtonSubmit from "@library/components/forms/ButtonSubmit";
import ModalConfirm from "@library/components/modal/ModalConfirm";
import Translate from "@library/components/translation/Translate";
import NavigationManagerItemIcon from "@knowledge/modules/navigation/NavigationManagerItemIcon";
import { DraggableProvided, DraggableStateSnapshot } from "react-beautiful-dnd";
import { IKbNavigationItem } from "@knowledge/@types/api/kbNavigation";
import { ITreeItem } from "@atlaskit/tree";

interface IProps {
    className?: string;
    item: ITreeItem<IKbNavigationItem>;
    handleRename: (
        e: React.SyntheticEvent,
        item: ITreeItem<IKbNavigationItem>,
        newName: string,
        callback?: (result: any) => void,
    ) => boolean;
    handleDelete: (
        item: ITreeItem<IKbNavigationItem>,
        deleteButtonRef: React.RefObject<HTMLButtonElement>,
        callback: () => void,
    ) => void;
    hasChildren: boolean;
    expandItem: (itemId: string) => void;
    collapseItem: (itemId: string) => void;
    snapshot: DraggableStateSnapshot;
    provided: DraggableProvided;
    renameMode: (itemID: number | null) => void; //Start Rename Mode
    stopRenameMode: () => void; // Stop Rename Mode
    renameItemID: number | null; // Item in rename mode. Parent manages it so only 1 can be in rename mode at a time.
    disableTree: () => void;
    enableTree: () => void;
}

interface IState {
    error: boolean;
    disabled: boolean;
    newName: string;
    deleteMode: boolean;
}

export default class NavigationManagerContent extends React.Component<IProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private inputRef: React.RefObject<HTMLInputElement> = React.createRef();

    constructor(props) {
        super(props);
        this.state = {
            error: false,
            disabled: false,
            deleteMode: false,
            newName: this.props.item.data!.name,
        };
    }

    public render() {
        const { item, provided, snapshot } = this.props;
        const name = item.data!.name;
        const isRenaming = this.isEditMode();
        return (
            <div
                ref={provided.innerRef}
                {...provided.draggableProps}
                {...provided.dragHandleProps}
                aria-roledescription={
                    provided.dragHandleProps ? t(provided.dragHandleProps["aria-roledescription"]) : undefined
                }
                className={classNames("navigationManager-item", {
                    isDragging: snapshot.isDragging,
                    isActive: isRenaming || this.state.deleteMode,
                })}
            >
                {isRenaming ? (
                    <form className={classNames("navigationManger-editMode", { hasError: this.state.error })}>
                        <label>
                            <span className="sr-only">{t("New Name: ")}</span>
                            <input
                                type="text"
                                value={this.state.newName}
                                onChange={this.handleOnChange}
                                ref={this.inputRef}
                            />
                        </label>
                        <Button onClick={this.props.stopRenameMode} className={"navigationManger-cancelRename"}>
                            {t("Cancel")}
                        </Button>
                        <ButtonSubmit className={"navigationManger-submitRename"}>{t("Apply")}</ButtonSubmit>
                    </form>
                ) : (
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
                            onClick={this.renameItem}
                            className={classNames(
                                "navigationManager-rename",
                                "navigationManager-action",
                                this.props.className,
                            )}
                            baseClass={ButtonBaseClass.CUSTOM}
                            buttonRef={this.buttonRef}
                        >
                            {t("Rename")}
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
                        {this.state.deleteMode && (
                            <ModalConfirm
                                title={(<Translate source={'Delete "<0/>"'} c0={name} /> as unknown) as string}
                                onCancel={this.closeConfirmation}
                                onConfirm={this.handleDelete}
                                elementToFocusOnExit={this.buttonRef.current!}
                            >
                                <Translate
                                    source={'Are you sure you want to delete <0/> "<1/>" ?'}
                                    c0={this.getType(item.data!.recordType)}
                                    c1={
                                        <strong>
                                            <em>{name}</em>
                                        </strong>
                                    }
                                />
                            </ModalConfirm>
                        )}
                    </div>
                )}
            </div>
        );
    }

    public componentDidUpdate(prevProps, prevState) {
        if (prevState.deleteMode !== this.state.deleteMode) {
            if (this.state.deleteMode) {
                this.props.disableTree();
            } else {
                this.props.enableTree();
            }
        }

        if (
            this.props.renameItemID !== prevProps.renameItemID &&
            this.props.renameItemID === this.props.item.data!.recordID
        ) {
            this.setState(
                {
                    newName: this.props.item.data!.name,
                },
                () => {
                    this.inputRef.current!.focus();
                },
            );
        }
    }

    private handleOnChange = e => {
        e.preventDefault();
        e.stopPropagation();
        this.setState({
            error: false,
            newName: e.target.value,
        });
    };

    private submit = e => {
        const newName = this.state.newName;
        if (newName !== "" && newName !== this.props.item.data!.name) {
            this.setState(
                {
                    disabled: true,
                },
                () => {
                    const editResult = this.props.handleRename(e, this.props.item, this.state.newName, () => {
                        const editReault = {
                            message: "Success",
                            success: true,
                        };
                        if (editReault.success) {
                            // Success
                            this.setState({
                                disabled: false,
                                error: false,
                                newName: "",
                            });
                            this.props.stopRenameMode();
                        } else {
                            // Problem
                            this.setState({
                                error: editResult,
                                disabled: false,
                            });
                        }
                    });
                },
            );
        }
    };

    private isEditMode = () => {
        return this.props.renameItemID === this.props.item.data!.recordID;
    };

    private renameItem = () => {
        this.setState(
            {
                newName: this.props.item.data!.name,
            },
            () => {
                this.props.renameMode(this.props.item.data!.recordID);
            },
        );
    };

    private closeConfirmation = () => {
        this.setState({
            deleteMode: false,
        });
    };
    private showConfirmation = () => {
        this.setState({
            deleteMode: true,
        });
    };

    private handleDelete = () => {
        this.props.handleDelete(this.props.item, this.buttonRef, () => {
            this.setState({
                deleteMode: false,
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
