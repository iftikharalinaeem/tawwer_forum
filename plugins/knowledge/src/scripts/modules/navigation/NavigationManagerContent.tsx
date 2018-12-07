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
import { NavigationRecordType } from "@knowledge/@types/api";
import { INormalizedNavigationItem } from "@knowledge/modules/navigation/NavigationModel";

interface IProps {
    className?: string;
    item: ITreeItem<INormalizedNavigationItem>;
    snapshot: DraggableStateSnapshot;
    provided: DraggableProvided;
    hasChildren: boolean;
    onRenameSubmit: (item: INormalizedNavigationItem, newName: string) => void;
    onDelete: () => void;
    handleDelete: () => void;
    expandItem: (itemId: string) => void;
    collapseItem: (itemId: string) => void;
    selectedItem: ITreeItem<INormalizedNavigationItem> | null; // Item in rename mode. Parent manages it so only 1 can be in rename mode at a time.
    selectItem: (
        item: ITreeItem<INormalizedNavigationItem> | null,
        writeMode: boolean,
        deleteMode: boolean,
        callback?: () => void,
    ) => void;
    unSelectItem: () => void;
    disableTree: (callback?: () => void) => void;
    enableTree: (callback?: () => void) => void;
    writeMode: boolean;
    deleteMode: boolean;
}

interface IState {
    newName: string;
    showConfirmation: boolean;
}

export default class NavigationManagerContent extends React.Component<IProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private wrapRef: React.RefObject<HTMLDivElement> = React.createRef();

    public state: IState = {
        newName: this.props.item.data.name,
        showConfirmation: false,
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
                            focusOnExit={this.buttonRef}
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
                                baseClass={ButtonBaseClass.CUSTOM}
                                buttonRef={this.buttonRef}
                            >
                                {t("Delete")}
                            </Button>
                            {this.props.deleteMode && this.isCurrent() && (
                                <ModalConfirm
                                    title={
                                        ((
                                            <Translate source={'Delete "<0/>"'} c0={this.displayName} />
                                        ) as unknown) as string
                                    }
                                    onCancel={this.hideConfirmation}
                                    onConfirm={this.props.handleDelete}
                                    elementToFocusOnExit={this.buttonRef.current!}
                                >
                                    <Translate
                                        source={'Are you sure you want to delete <0/> "<1/>" ?'}
                                        c0={this.typeLabel}
                                        c1={
                                            <strong>
                                                <em>{this.displayName}</em>
                                            </strong>
                                        }
                                    />
                                </ModalConfirm>
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

    private renameItem = (e: React.SyntheticEvent) => {
        this.props.selectItem(this.props.item, true, false);
    };

    private cancelRename = (e: React.SyntheticEvent) => {
        this.props.selectItem(this.props.item, false, false);
        // this.forceUpdate();
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

    private focusSelf = () => {
        this.props.expandItem(this.props.item.id);
        const content = this.wrapRef.current!.firstChild as HTMLElement;
        content.focus();
    };

    private isCurrent = () => {
        return this.props.selectedItem && this.props.selectedItem.id === this.props.item.id;
    };

    /**
     * The label of the current type.
     */
    private get typeLabel(): string {
        const { recordType } = this.props.item.data;
        switch (recordType) {
            case NavigationRecordType.ARTICLE:
                return t("article");
            case NavigationRecordType.KNOWLEDGE_CATEGORY:
                return t("category");
            default:
                return recordType;
        }
    }
}
