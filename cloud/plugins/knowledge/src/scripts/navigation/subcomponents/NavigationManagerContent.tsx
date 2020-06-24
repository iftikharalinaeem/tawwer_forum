/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ITreeItem } from "@atlaskit/tree";
import { INormalizedNavigationItem } from "@knowledge/navigation/state/NavigationModel";
import NavigationManagerItemIcon from "@knowledge/navigation/subcomponents/NavigationManagerItemIcon";
import NavigationManagerNameForm from "@knowledge/navigation/subcomponents/NavigationManagerNameForm";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import classNames from "classnames";
import React from "react";
import { DraggableProvided, DraggableStateSnapshot } from "react-beautiful-dnd";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { navigationManagerClasses } from "@knowledge/navigation/navigationManagerStyles";

interface IProps {
    className?: string;
    item: ITreeItem<INormalizedNavigationItem>;
    snapshot: DraggableStateSnapshot;
    provided: DraggableProvided;
    hasChildren: boolean;
    onDeleteClick: (deleteButton: HTMLButtonElement) => void;
    onRenameSubmit: (item: INormalizedNavigationItem, newName: string) => void;
    expandItem: (itemId: string) => void;
    collapseItem: (itemId: string) => void;
    selectedItem: ITreeItem<INormalizedNavigationItem> | null; // Item in rename mode. Parent manages it so only 1 can be in rename mode at a time.
    selectItem: (item: ITreeItem<INormalizedNavigationItem> | null, writeMode: boolean, callback?: () => void) => void;
    writeMode: boolean;
    firstID: string | null;
    getItemID: (id: string) => string;
    isInRoot: boolean;
    isDeletedDisabled?: boolean;
    isRenameDisabled?: boolean;
}

interface IState {
    newName: string;
}

export default class NavigationManagerContent extends React.Component<IProps, IState> {
    private renameButtonRef = React.createRef<HTMLButtonElement>();
    private deleteButtonRef = React.createRef<HTMLButtonElement>();
    private selfRef: HTMLDivElement | null = null;

    public state: IState = {
        newName: this.props.item.data.name,
    };

    public render() {
        const { item, provided, snapshot, isInRoot, isDeletedDisabled, isRenameDisabled } = this.props;
        const { error } = item.data;
        const isEditing = this.props.writeMode && !!this.isCurrent();
        const classesNavigationManager = navigationManagerClasses();

        return (
            <div
                id={this.id}
                ref={ref => {
                    this.selfRef = ref;
                    provided.innerRef(ref);
                }}
                {...provided.draggableProps}
                {...provided.dragHandleProps}
                aria-roledescription={
                    provided.dragHandleProps ? t(provided.dragHandleProps["aria-roledescription"]) : undefined
                }
                className={classNames("navigationManager-item", classesNavigationManager.item, {
                    isDragging: snapshot.isDragging,
                    hasError: error,
                    isActive: this.isCurrent(),
                    isChild: item.data.parentID !== 0,
                    isArticle: item.data.recordType === KbRecordType.ARTICLE,
                    isInRoot,
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
                onClick={this.selectSelf}
                onFocus={this.handleFocusIn}
            >
                <div
                    className={classNames(
                        "navigationManager-draggable",
                        classesNavigationManager.draggable,
                        this.props.className,
                    )}
                >
                    <NavigationManagerItemIcon
                        expanded={!!item.isExpanded}
                        expandItem={this.handleExpand}
                        collapseItem={this.handleCollapse}
                        itemId={item.id}
                        className="tree-itemIcon"
                        type={item.data.recordType}
                        hasChildren={this.props.hasChildren}
                    />

                    {isEditing ? (
                        <NavigationManagerNameForm
                            currentName={this.displayName}
                            focusOnExit={this.renameButtonRef}
                            applyNewName={this.applyNewName}
                            cancel={this.cancelRename}
                            isFolder={this.props.hasChildren}
                        />
                    ) : (
                        <>
                            <span
                                className={classNames(
                                    "navigationManager-itemLabel",
                                    classesNavigationManager.itemLabel,
                                    {
                                        isFolder: this.props.hasChildren,
                                    },
                                )}
                            >
                                {this.displayName}
                            </span>
                            <Button
                                onClick={this.renameItem}
                                className={classNames(
                                    "navigationManager-rename",
                                    "navigationManager-action",
                                    classesNavigationManager.action,
                                    this.props.className,
                                )}
                                baseClass={ButtonTypes.CUSTOM}
                                buttonRef={this.renameButtonRef}
                                disabled={isRenameDisabled}
                                tabIndex={0}
                            >
                                {t("Rename")}
                            </Button>

                            <Button
                                onClick={this.handleDeleteClick}
                                className={classNames(
                                    "navigationManager-delete",
                                    "navigationManager-action",
                                    classesNavigationManager.action,
                                    classesNavigationManager.deleteItem,
                                    this.props.className,
                                )}
                                baseClass={ButtonTypes.CUSTOM}
                                buttonRef={this.deleteButtonRef}
                                tabIndex={0}
                                disabled={isDeletedDisabled}
                            >
                                {t("Delete")}
                            </Button>
                        </>
                    )}
                </div>
            </div>
        );
    }

    public componentDidUpdate(prevProps: Readonly<IProps>): void {
        const isNowSelected = this.props.selectedItem && this.props.selectedItem.id === this.props.item.id;
        if (isNowSelected) {
            this.focusSelf();
        }
    }

    // Focus self

    private focusingSelf = false;

    private focusSelf() {
        this.focusingSelf = true;
        if (this.selfRef) {
            this.selfRef.focus();
        }
    }

    private handleFocusIn = () => {
        if (this.focusingSelf) {
            this.focusingSelf = true;
            return;
        }

        this.selectSelf();
    };

    private get id() {
        return this.props.getItemID(this.props.item.id);
    }

    private get displayName(): string {
        const { name, tempName } = this.props.item.data;
        return tempName || name;
    }

    private applyNewName = (newName: string) => {
        this.props.onRenameSubmit(this.props.item.data, newName);
    };

    private renameItem = (e: React.MouseEvent) => {
        e.stopPropagation();
        this.props.selectItem(this.props.item, true);
    };

    private handleDeleteClick = () => {
        this.props.onDeleteClick(this.deleteButtonRef.current!);
    };

    private cancelRename = (event: React.SyntheticEvent) => {
        event.stopPropagation();
        this.props.selectItem(this.props.item, false);
        this.focusSelf();
    };

    private handleExpand = () => {
        this.props.expandItem(this.props.item.id);
    };

    private handleCollapse = () => {
        this.props.collapseItem(this.props.item.id);
    };

    private selectSelf = () => {
        if (!this.isCurrent()) {
            this.props.selectItem(this.props.item, this.props.writeMode);
        }
    };

    private isCurrent = () => {
        if (this.props.selectedItem) {
            return this.props.selectedItem.id === this.props.item.id;
        } else {
            return false;
        }
    };
}
