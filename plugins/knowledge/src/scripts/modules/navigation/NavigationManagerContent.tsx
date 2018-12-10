/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ITreeItem } from "@atlaskit/tree";
import NavigationManagerItemIcon from "@knowledge/modules/navigation/NavigationManagerItemIcon";
import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import ModalConfirm from "@library/components/modal/ModalConfirm";
import Translate from "@library/components/translation/Translate";
import classNames from "classnames";
import React from "react";
import { DraggableProvided, DraggableStateSnapshot } from "react-beautiful-dnd";
import NavigationManagerNameForm from "./NavigationManagerNameForm";
import { INavigationItem } from "@library/@types/api";
import ConditionalWrap from "@library/components/ConditionalWrap";

interface IProps {
    className?: string;
    item: ITreeItem<INavigationItem>;
    snapshot: DraggableStateSnapshot;
    provided: DraggableProvided;
    hasChildren: boolean;
    onRenameSubmit: () => void;
    onDelete: () => void;
    handleDelete: () => void;
    expandItem: (itemId: string) => void;
    collapseItem: (itemId: string) => void;
    selectedItem: ITreeItem<INavigationItem> | null; // Item in rename mode. Parent manages it so only 1 can be in rename mode at a time.
    selectItem: (
        item: ITreeItem<INavigationItem> | null,
        writeMode: boolean,
        deleteMode: boolean,
        element: HTMLElement,
        callback?: () => void,
    ) => void;
    unSelectItem: () => void;
    disableTree: (callback?: () => void) => void;
    enableTree: (callback?: () => void) => void;
    type: string;
    writeMode: boolean;
    deleteMode: boolean;
    firstID: string | null;
}

interface IState {
    newName: string;
    showConfirmation: boolean;
}

export default class NavigationManagerContent extends React.Component<IProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private wrapRef: React.RefObject<HTMLDivElement> = React.createRef();

    public state: IState = {
        newName: this.props.item.data!.name,
        showConfirmation: false,
    };

    public render() {
        const { item, provided, snapshot } = this.props;
        const name = item.data!.name;
        const isEditing = this.props.writeMode && !!this.isCurrent();
        return (
            <div className={"navigationManager-itemFocusManager"} ref={this.wrapRef}>
                <div
                    ref={provided.innerRef}
                    {...provided.draggableProps}
                    {...provided.dragHandleProps}
                    aria-roledescription={
                        provided.dragHandleProps ? t(provided.dragHandleProps["aria-roledescription"]) : undefined
                    }
                    className={classNames("navigationManager-item", {
                        isDragging: snapshot.isDragging,
                        isActive: this.isCurrent(),
                    })}
                    aria-expanded={this.props.hasChildren ? item.isExpanded : undefined}
                    tabIndex={
                        (this.props.selectedItem === null &&
                            this.props.firstID !== null &&
                            this.props.firstID === item.id) ||
                        (this.props.selectedItem && this.isCurrent())
                            ? 0
                            : -1
                    }
                    role="treeitem"
                    onClick={this.focusSelf}
                >
                    <div className={classNames("navigationManager-draggable", this.props.className)}>
                        <ConditionalWrap condition={isEditing} className="isVisibilityHidden">
                            <NavigationManagerItemIcon
                                expanded={!!item.isExpanded}
                                expandItem={this.handleExpand}
                                collapseItem={this.handleCollapse}
                                itemId={item.id}
                                className="tree-itemIcon"
                                hasChildren={this.props.hasChildren}
                            />
                        </ConditionalWrap>

                        {isEditing ? (
                            <NavigationManagerNameForm
                                currentName={name}
                                focusOnExit={this.buttonRef}
                                applyNewName={this.props.onRenameSubmit}
                                cancel={this.cancelRename}
                            />
                        ) : (
                            <>
                                <span
                                    className={classNames("navigationManager-itemLabel", {
                                        isFolder: this.props.hasChildren,
                                    })}
                                >
                                    {name}
                                </span>
                                <Button
                                    onClick={this.renameItem}
                                    className={classNames(
                                        "navigationManager-rename",
                                        "navigationManager-action",
                                        this.props.className,
                                    )}
                                    baseClass={ButtonBaseClass.CUSTOM}
                                    buttonRef={this.buttonRef}
                                    tabIndex={0}
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
                                    baseClass={ButtonBaseClass.CUSTOM}
                                    buttonRef={this.buttonRef}
                                    tabIndex={0}
                                >
                                    {t("Delete")}
                                </Button>
                                {this.props.deleteMode &&
                                    this.isCurrent() && (
                                        <ModalConfirm
                                            title={
                                                (<Translate source={'Delete "<0/>"'} c0={name} /> as unknown) as string
                                            }
                                            onCancel={this.hideConfirmation}
                                            onConfirm={this.props.handleDelete}
                                            elementToFocusOnExit={this.buttonRef.current!}
                                        >
                                            <Translate
                                                source={'Are you sure you want to delete <0/> "<1/>"?'}
                                                c0={this.props.type}
                                                c1={
                                                    <strong>
                                                        <em>{name}</em>
                                                    </strong>
                                                }
                                            />
                                        </ModalConfirm>
                                    )}
                            </>
                        )}
                    </div>
                </div>
            </div>
        );
    }

    private renameItem = (e: React.SyntheticEvent) => {
        this.props.selectItem(this.props.item, true, false, this.getRef());
    };

    private cancelRename = (e: React.SyntheticEvent) => {
        this.props.selectItem(this.props.item, false, false, this.getRef());
    };

    private handleExpand = () => {
        this.props.selectItem(this.props.item, false, false, this.getRef());
        this.props.expandItem(this.props.item.id);
    };

    private handleCollapse = () => {
        this.props.selectItem(this.props.item, false, false, this.getRef());
        this.props.collapseItem(this.props.item.id);
    };

    private showConfirmation = () => {
        this.props.disableTree(() => {
            this.props.selectItem(this.props.item, false, true, this.getRef());
        });
    };

    private hideConfirmation = () => {
        this.props.disableTree(() => {
            this.props.selectItem(this.props.item, false, false, this.getRef());
        });
    };

    private getRef(): HTMLElement {
        return this.wrapRef.current!.firstChild as HTMLElement;
    }

    private focusSelf = () => {
        const content = this.wrapRef.current!.firstChild as HTMLElement;
        if (content) {
            content.focus();
        }
    };

    private selectSelf = () => {
        this.props.selectItem(this.props.item, this.props.writeMode, this.props.deleteMode, this.getRef());
    };

    private isCurrent = () => {
        if (this.props.selectedItem) {
            return this.props.selectedItem.id === this.props.item.id;
        } else {
            return false;
        }
    };

    private handleKeyDown = (e: React.KeyboardEvent) => {
        // const currentItem = null;
        // const tabHandler = new TabHandler(this.self.current!);
        const shift = "-Shift";
        window.console.log("e.key ", e.key);
        e.stopPropagation();
        switch (`${e.key}${e.shiftKey ? shift : ""}`) {
            case "Escape":
                this.cancelRename(e);
                break;
        }
    };
}
