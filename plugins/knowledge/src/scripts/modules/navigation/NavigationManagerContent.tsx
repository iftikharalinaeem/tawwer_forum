/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ITreeItem } from "@atlaskit/tree";
import NavigationManagerItemIcon from "@knowledge/modules/navigation/NavigationManagerItemIcon";
import { INormalizedNavigationItem } from "@knowledge/modules/navigation/NavigationModel";
import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import classNames from "classnames";
import React from "react";
import { DraggableProvided, DraggableStateSnapshot } from "react-beautiful-dnd";
import NavigationManagerNameForm from "@knowledge/modules/navigation/NavigationManagerNameForm";
import ConditionalWrap from "@library/components/ConditionalWrap";

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
}

interface IState {
    newName: string;
}

export default class NavigationManagerContent extends React.Component<IProps, IState> {
    private renameButtonRef = React.createRef<HTMLButtonElement>();
    private deleteButtonRef = React.createRef<HTMLButtonElement>();
    private selfRef: HTMLDivElement | null;

    public state: IState = {
        newName: this.props.item.data.name,
    };

    public render() {
        const { item, provided, snapshot } = this.props;
        const { error } = item.data;
        const isEditing = this.props.writeMode && !!this.isCurrent();
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
                className={classNames("navigationManager-item", {
                    isDragging: snapshot.isDragging,
                    hasError: error,
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
                onClick={this.selectSelf}
                onFocus={this.handleFocusIn}
            >
                <div className={classNames("navigationManager-draggable", this.props.className)}>
                    <ConditionalWrap condition={isEditing} className="isVisibilityHidden">
                        <NavigationManagerItemIcon
                            expanded={!!item.isExpanded}
                            expandItem={this.handleExpand}
                            collapseItem={this.handleCollapse}
                            itemId={item.id}
                            className="tree-itemIcon"
                            type={item.data.recordType}
                            hasChildren={this.props.hasChildren}
                        />
                    </ConditionalWrap>

                    {isEditing ? (
                        <NavigationManagerNameForm
                            currentName={this.displayName}
                            focusOnExit={this.renameButtonRef}
                            applyNewName={this.applyNewName}
                            cancel={this.cancelRename}
                        />
                    ) : (
                        <>
                            <span
                                className={classNames("navigationManager-itemLabel", {
                                    isFolder: this.props.hasChildren,
                                })}
                            >
                                {this.displayName}
                            </span>
                            <Button
                                onClick={this.renameItem}
                                className={classNames(
                                    "navigationManager-rename",
                                    "navigationManager-action",
                                    this.props.className,
                                )}
                                baseClass={ButtonBaseClass.CUSTOM}
                                buttonRef={this.renameButtonRef}
                                tabIndex={0}
                            >
                                {t("Rename")}
                            </Button>
                            {this.props.item.children.length === 0 && (
                                <Button
                                    onClick={this.handleDeleteClick}
                                    className={classNames(
                                        "navigationManager-delete",
                                        "navigationManager-action",
                                        this.props.className,
                                    )}
                                    baseClass={ButtonBaseClass.CUSTOM}
                                    buttonRef={this.deleteButtonRef}
                                    tabIndex={0}
                                >
                                    {t("Delete")}
                                </Button>
                            )}
                        </>
                    )}
                </div>
            </div>
        );
    }

    public componentDidUpdate(prevProps: Readonly<IProps>): void {
        const wasPrevSelected = prevProps.selectedItem && prevProps.selectedItem.id === prevProps.item.id;
        const isNowSelected = this.props.selectedItem && this.props.selectedItem.id === this.props.item.id;
        if (!wasPrevSelected && isNowSelected) {
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

    private cancelRename = (e: React.MouseEvent) => {
        e.stopPropagation();
        this.props.selectItem(this.props.item, false);
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
