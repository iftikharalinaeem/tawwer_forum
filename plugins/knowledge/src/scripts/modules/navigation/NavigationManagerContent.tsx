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
    onRenameSubmit: () => void;
    handleDelete: () => void;
    hasChildren: boolean;
    expandItem: (itemId: string) => void;
    collapseItem: (itemId: string) => void;
    snapshot: DraggableStateSnapshot;
    provided: DraggableProvided;
    activateRenameMode: (itemID: string | null) => void; //Start Rename Mode
    disableRenameMode: () => void; // Stop Rename Mode
    renameItemID: string | null; // Item in rename mode. Parent manages it so only 1 can be in rename mode at a time.
    disableTree: () => void;
    enableTree: () => void;
    deleteMode: boolean;
    showConfirmation: (id: string) => void;
    closeConfirmation: () => void;
    onDelete: () => void;
}

interface IState {
    newName: string;
}

export default class NavigationManagerContent extends React.Component<IProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private inputRef: React.RefObject<HTMLInputElement> = React.createRef();

    public state: IState = {
        newName: this.props.item.data!.name,
    };

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
                    isActive: isRenaming || this.props.deleteMode,
                })}
            >
                {isRenaming && this.state.newName !== null ? (
                    <form className={classNames("navigationManger-editMode")} onSubmit={this.submit}>
                        <label>
                            <span className="sr-only">{t("New Name: ")}</span>
                            <input
                                type="text"
                                value={this.state.newName}
                                onChange={this.handleOnChange}
                                ref={this.inputRef}
                            />
                        </label>
                        <Button onClick={this.resetSelf} className={"navigationManger-cancelRename"}>
                            {t("Cancel")}
                        </Button>
                        <ButtonSubmit
                            className={"navigationManger-submitRename"}
                            disabled={this.state.newName === name}
                        >
                            {t("Apply")}
                        </ButtonSubmit>
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
                            onClick={this.props.showConfirmation}
                            className={classNames(
                                "navigationManager-delete",
                                "navigationManager-action",
                                this.props.className,
                            )}
                            baseClass={ButtonBaseClass.CUSTOM}
                            buttonRef={this.buttonRef}
                        >
                            {t("Delete")}
                        </Button>
                        {this.props.deleteMode && (
                            <ModalConfirm
                                title={(<Translate source={'Delete "<0/>"'} c0={name} /> as unknown) as string}
                                onCancel={this.props.closeConfirmation}
                                onConfirm={this.props.handleDelete}
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

    public componentDidMount() {
        if (this.isEditMode()) {
            this.inputRef.current!.select();
        }
    }

    private resetSelf = e => {
        this.props.disableRenameMode();
    };

    private submit = e => {
        e.preventDefault();
        e.stopPropagation();
        this.props.onRenameSubmit();
    };

    private isEditMode = () => {
        return this.props.renameItemID === this.props.item.id;
    };

    private renameItem = (e: React.SyntheticEvent) => {
        if (e && e.target) {
            e.stopPropagation();
            const data = this.props.item.data;
            this.props.activateRenameMode(this.props.item.id);
        }
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

    private handleOnChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        e.preventDefault();
        if (e.target.value) {
            this.setState({
                newName: e.target.value,
            });
        }
    };
}
