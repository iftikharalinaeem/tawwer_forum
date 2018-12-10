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
import NavigationManagerNameForm from "./NavigationManagerNameForm";

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
}

interface IState {
    newName: string;
}

export default class NavigationManagerContent extends React.Component<IProps, IState> {
    private renameButtonRef = React.createRef<HTMLButtonElement>();
    private deleteButtonRef = React.createRef<HTMLButtonElement>();
    private wrapRef = React.createRef<HTMLDivElement>();

    public state: IState = {
        newName: this.props.item.data.name,
    };

    public render() {
        const { item, provided, snapshot } = this.props;
        const { error } = item.data;
        return (
            <div ref={this.wrapRef}>
                <div
                    ref={provided.innerRef}
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
                    tabIndex={0}
                    onClick={this.focusSelf}
                >
                    {this.props.writeMode && this.isCurrent() ? (
                        <NavigationManagerNameForm
                            currentName={this.displayName}
                            focusOnExit={this.renameButtonRef}
                            applyNewName={this.applyNewName}
                            cancel={this.cancelRename}
                        />
                    ) : (
                        <div className={classNames("navigationManager-draggable", this.props.className)}>
                            <NavigationManagerItemIcon
                                expanded={!!item.isExpanded}
                                expandItem={this.handleExpand}
                                collapseItem={this.handleCollapse}
                                itemId={item.id}
                                className="tree-itemIcon"
                                type={item.data.recordType}
                            />
                            <span className="navigationManager-itemLabel">{this.displayName}</span>
                            <Button
                                onClick={this.renameItem}
                                className={classNames(
                                    "navigationManager-rename",
                                    "navigationManager-action",
                                    this.props.className,
                                )}
                                baseClass={ButtonBaseClass.CUSTOM}
                                buttonRef={this.renameButtonRef}
                            >
                                {t("Rename")}
                            </Button>
                            {item.children.length === 0 && (
                                <Button
                                    onClick={this.deleteItem}
                                    className={classNames(
                                        "navigationManager-delete",
                                        "navigationManager-action",
                                        this.props.className,
                                    )}
                                    baseClass={ButtonBaseClass.CUSTOM}
                                    buttonRef={this.deleteButtonRef}
                                >
                                    {t("Delete")}
                                </Button>
                            )}
                        </div>
                    )}
                </div>
            </div>
        );
    }

    private get displayName(): string {
        const { name, tempName } = this.props.item.data;
        return tempName || name;
    }

    private applyNewName = (newName: string) => {
        this.props.onRenameSubmit(this.props.item.data, newName);
    };

    private renameItem = () => {
        this.props.selectItem(this.props.item, false);
    };

    private deleteItem = () => {
        this.props.onDeleteClick(this.deleteButtonRef.current!);
    };

    private cancelRename = () => {
        this.props.selectItem(this.props.item, false);
    };

    private handleExpand = () => {
        this.props.selectItem(this.props.item, false);
        this.props.expandItem(this.props.item.id);
    };

    private handleCollapse = () => {
        this.props.selectItem(this.props.item, false);
        this.props.collapseItem(this.props.item.id);
    };

    private focusSelf = () => {
        this.props.expandItem(this.props.item.id);
        const content = this.wrapRef.current!.firstChild as HTMLElement;
        content.focus();
    };

    private isCurrent = () => {
        return this.props.selectedItem && this.props.selectedItem.id === this.props.item.id;
    };
}
