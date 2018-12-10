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
        callback?: () => void,
    ) => void;
    unSelectItem: () => void;
    disableTree: (callback?: () => void) => void;
    enableTree: (callback?: () => void) => void;
    type: string;
    writeMode: boolean;
    deleteMode: boolean;
    isFirst: boolean;
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
                tabIndex={this.props.isFirst ? 0 : -1}
                role="treeitem"
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
                                        title={(<Translate source={'Delete "<0/>"'} c0={name} /> as unknown) as string}
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
        );
    }
    //
    // public componentDidMount() {
    //     this.props.setDomElement(this.props.item.id, this.getRef());
    // }

    private renameItem = (e: React.SyntheticEvent) => {
        this.props.selectItem(this.props.item, true, false);
    };

    private cancelRename = (e: React.SyntheticEvent) => {
        this.props.selectItem(this.props.item, false, false);
    };

    private handleExpand = () => {
        this.props.selectItem(this.props.item, false, false);
        this.props.expandItem(this.props.item.id);
    };

    private handleCollapse = () => {
        this.props.selectItem(this.props.item, false, false);
        this.props.collapseItem(this.props.item.id);
    };

    private showConfirmation = () => {
        this.props.disableTree(() => {
            this.props.selectItem(this.props.item, false, true);
        });
    };

    private hideConfirmation = () => {
        this.props.disableTree(() => {
            this.props.selectItem(this.props.item, false, false);
        });
    };

    private getRef() {
        return this.wrapRef.current!.firstChild as HTMLElement;
    }

    private focusSelf = () => {
        const content = this.wrapRef.current!.firstChild as HTMLElement;
        content.focus();
    };

    private selectSelf = () => {
        this.props.selectItem(this.props.item, this.props.writeMode, this.props.deleteMode);
    };

    private isCurrent = () => {
        return this.props.selectedItem && this.props.selectedItem.id === this.props.item.id;
    };

    /**
     * Keyboard handler for arrow up, arrow down, home and end.
     * For full accessibility docs, see https://www.w3.org/TR/wai-aria-practices-1.1/examples/treeview/treeview-1/treeview-1a.html
     * Note that some of the events are on SiteNavNode.tsx
     * @param event
     */
    private handleKeyDown = (e: React.KeyboardEvent) => {
        // const currentItem = null;
        // const tabHandler = new TabHandler(this.self.current!);
        const shift = "-Shift";
        window.console.log("e.key ", e.key);
        e.stopPropagation();
        switch (`${e.key}${e.shiftKey ? shift : ""}`) {
            case "Escape":
                this.cancelRename(e);
            // this.setState({
            //     disabled: false,
            //     writeMode: false,
            // });
            // case "Tab":
            //     e.stopPropagation();
            //     e.preventDefault();
            //     const nextElement = tabHandler.getNext(currentItem, false, true);
            //     if (nextElement) {
            //         nextElement.focus();
            //     }
            //     break;
            // case "Tab" + shift:
            //     e.stopPropagation();
            //     e.preventDefault();
            //     const prevElement = tabHandler.getNext(currentItem, true, true);
            //     if (prevElement) {
            //         prevElement.focus();
            //     }
            //     break;
            // case "ArrowDown":
            //     /*
            //         Moves focus one row or one cell down, depending on whether a row or cell is currently focused.
            //         If focus is on the bottom row, focus does not move.
            //      */
            //     e.preventDefault();
            //     e.stopPropagation();
            //     if (currentItem) {
            //         const nextElement = tabHandler.getNext(currentItem, false, false);
            //         if (nextElement) {
            //             nextElement.focus();
            //         }
            //     }
            //     break;
            // case "ArrowUp":
            //     /*
            //         Moves focus one row or one cell up, depending on whether a row or cell is currently focused.
            //         If focus is on the top row, focus does not move.
            //      */
            //     if (currentItem) {
            //         e.preventDefault();
            //         e.stopPropagation();
            //         const prevElement = tabHandler.getNext(currentItem, true, false);
            //         if (prevElement) {
            //             prevElement.focus();
            //         }
            //     }
            //     break;
            // case "Home":
            //     /*
            //         If a cell is focused, moves focus to the previous interactive widget in the current row.
            //         If a row is focused, moves focus out of the treegrid.
            //      */
            //     e.preventDefault();
            //     e.stopPropagation();
            //     const firstLink = tabHandler.getInitial();
            //     if (firstLink) {
            //         firstLink.focus();
            //     }
            //     break;
            // case "End":
            //     /*
            //         If a row is focused, moves to the first row.
            //         If a cell is focused, moves focus to the first cell in the row containing focus.
            //      */
            //     e.preventDefault();
            //     e.stopPropagation();
            //     const lastLink = tabHandler.getLast();
            //     if (lastLink) {
            //         lastLink.focus();
            //     }
            //     break;
        }
    };
}
