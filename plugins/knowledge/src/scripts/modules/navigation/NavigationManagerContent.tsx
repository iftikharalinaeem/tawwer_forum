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
    snapshot: DraggableStateSnapshot;
    provided: DraggableProvided;
    hasChildren: boolean;
    onRenameSubmit: () => void;
    onDelete: () => void;
    handleDelete: () => void;
    expandItem: (itemId: string) => void;
    collapseItem: (itemId: string) => void;
    selectedItem: ITreeItem<IKbNavigationItem> | null; // Item in rename mode. Parent manages it so only 1 can be in rename mode at a time.
    selectItem: (item: ITreeItem<IKbNavigationItem> | null, writeMode: boolean, deleteMode: boolean) => void;
    unSelectItem: () => void;
    disableTree: () => void;
    enableTree: () => void;
    type: string;
    writeMode: boolean;
    deleteMode: boolean;
    current: boolean;
    handleKeyDown: (e) => void;
}

interface IState {
    newName: string;
    showConfirmation: boolean;
}

export default class NavigationManagerContent extends React.Component<IProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private inputRef: React.RefObject<HTMLInputElement> = React.createRef();
    private wrapRef: React.RefObject<HTMLDivElement> = React.createRef();

    public state: IState = {
        newName: this.props.item.data!.name,
        showConfirmation: false,
    };

    public render() {
        const { item, provided, snapshot, writeMode, deleteMode, current } = this.props;
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
                        isActive: this.props.current,
                    })}
                    tabIndex={0}
                    onClick={this.focusSelf}
                >
                    {this.props.writeMode ? (
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
                            <Button
                                onClick={this.reset}
                                baseClass={ButtonBaseClass.CUSTOM}
                                className={"navigationManger-cancelRename"}
                            >
                                {t("Cancel")}
                            </Button>
                            <ButtonSubmit
                                className={"navigationManger-submitRename"}
                                baseClass={ButtonBaseClass.CUSTOM}
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
                            {this.props.deleteMode && (
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

    private reset = e => {
        e.stopPropagation();
        this.props.selectItem(this.props.item, false, false);
    };

    public componentDidUpdate(prevProps: IProps, prevState: IState) {
        if (!prevProps.current && this.props.current) {
            this.focusSelf();
            this.props.selectItem(this.props.item, this.props.writeMode, this.props.deleteMode);
        }
    }

    public componentDidMount() {
        const input = this.inputRef.current;
        if (input) {
            input.select();
        }
    }

    private submit = e => {
        e.preventDefault();
        e.stopPropagation();
        this.props.onRenameSubmit();
    };

    private renameItem = (e: React.SyntheticEvent) => {
        if (e && e.target) {
            e.stopPropagation();
            this.props.selectItem(this.props.item, true, false);
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

    private showConfirmation = () => {
        this.props.selectItem(this.props.item, false, true);
    };

    private hideConfirmation = () => {
        this.props.selectItem(this.props.item, false, false);
    };

    private focusSelf = () => {
        if (this.wrapRef.current) {
            const content = this.wrapRef.current.firstChild as HTMLElement;
            content.focus();
        }
    };
}
