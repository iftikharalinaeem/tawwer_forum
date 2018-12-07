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
import NavigationManagerNameForm from "@knowledge/modules/navigation/NavigationManagerNameForm";

interface IProps {
    className?: string;
    item: ITreeItem<IKbNavigationItem>;
    snapshot: DraggableStateSnapshot;
    provided: DraggableProvided;
    hasChildren: boolean;
    onRenameSubmit: () => void;
    onDelete: () => void;
    handleDelete: () => void;
    expandItem: (itemId: string) => void;
    collapseItem: (itemId: string) => void;
    selectedItem: ITreeItem<IKbNavigationItem> | null; // Item in rename mode. Parent manages it so only 1 can be in rename mode at a time.
    selectItem: (item: ITreeItem<IKbNavigationItem> | null, writeMode: boolean) => void;
    unSelectItem: () => void;
    disableTree: () => void;
    enableTree: () => void;
    type: string;
    handleKeyDown: (e) => void;
    writeMode: boolean;
}

interface IState {
    newName: string;
    showConfirmation: boolean;
    deleteMode: boolean;
}

export default class NavigationManagerContent extends React.Component<IProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private wrapRef: React.RefObject<HTMLDivElement> = React.createRef();

    public state: IState = {
        newName: this.props.item.data!.name,
        showConfirmation: false,
        deleteMode: false,
    };

    public render() {
        const { item, provided, snapshot } = this.props;
        const name = item.data!.name;
        return (
            <div ref={this.wrapRef} onKeyDown={this.props.handleKeyDown}>
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
                    tabIndex={0}
                    onClick={this.focusSelf}
                >
                    {this.props.writeMode && this.isCurrent() ? (
                        <NavigationManagerNameForm
                            currentName={name}
                            focusOnExit={this.buttonRef}
                            applyNewName={this.props.onRenameSubmit}
                            cancel={this.cancelRename}
                        />
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
                                baseClass={ButtonBaseClass.CUSTOM}
                                buttonRef={this.buttonRef}
                            >
                                {t("Delete")}
                            </Button>
                            {this.state.deleteMode && (
                                <ModalConfirm
                                    title={(<Translate source={'Delete "<0/>"'} c0={name} /> as unknown) as string}
                                    onCancel={this.hideConfirmation}
                                    onConfirm={this.props.handleDelete}
                                    elementToFocusOnExit={this.buttonRef.current!}
                                >
                                    <Translate
                                        source={'Are you sure you want to delete <0/> "<1/>" ?'}
                                        c0={this.props.type}
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
            </div>
        );
    }

    public componentDidUpdate(prevProps: IProps, prevState: IState) {
        if (prevProps.writeMode !== this.props.writeMode) {
            this.forceUpdate();
            console.log("component did update!");
        }
    }

    // public componentDidMount() {
    //     if (this.isCurrent()) {
    //         const input = this.inputRef.current;
    //         if (input) {
    //             input.select();
    //         }
    //     }
    // }

    private renameItem = (e: React.SyntheticEvent) => {
        this.props.selectItem(this.props.item, true);
    };

    private cancelRename = (e: React.SyntheticEvent) => {
        console.log("click");
        this.props.selectItem(this.props.item, false);
        this.forceUpdate();
    };

    private handleOnChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        e.preventDefault();
        if (e.target.value) {
            this.setState({
                newName: e.target.value,
            });
        }
    };

    private showConfirmation = () => {
        this.props.selectItem(this.props.item, false);
    };

    private hideConfirmation = () => {
        this.props.selectItem(this.props.item, false);
    };

    private focusSelf = () => {
        const content = this.wrapRef.current!.firstChild as HTMLElement;
        content.focus();
    };

    private isCurrent = () => {
        return this.props.selectedItem && this.props.selectedItem.id === this.props.item.id;
    };
}
