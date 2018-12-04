/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { RefObject } from "react";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { folderClosed, folderOpen, article } from "@library/components/icons/tree";
import classNames from "classNames";
import { downTriangle, rightTriangle } from "@library/components/icons/common";
import { t } from "@library/application";
import ButtonSubmit from "@library/components/forms/ButtonSubmit";
import { ITreeItem } from "@atlaskit/tree";

interface IProps {
    className?: string;
    item: ITreeItem;
    handleEdit: (item: ITreeItem) => boolean;
    beforeEdit?: React.ReactNode;
    afterEdit?: React.ReactNode;
}

interface IState {
    error: boolean;
    editMode: boolean;
    disabled: boolean;
    newName: string;
}

export default class NavigationManagerContent extends React.Component<IProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    public state = {
        error: false,
        editMode: false,
        disabled: false,
        newName: "",
    };
    public render() {
        if (this.state.editMode) {
            return (
                <form className={classNames("navigationManger-editMode", { hasError: this.state.error })}>
                    <input value={this.props.item.name} name="renameItem" onKeyPress={this.handleOnChange} />
                    <Button onClick={this.stopEdit} className={"navigationManger-cancelRename"}>
                        {t("Cancel")}
                    </Button>
                    <ButtonSubmit className={"navigationManger-submitRename"}>{t("Apply")}</ButtonSubmit>
                </form>
            );
        } else {
            return (
                <>
                    {this.props.beforeEdit}
                    <Button
                        onClick={this.startEdit}
                        className={classNames("navigationManager-rename", this.props.className)}
                        baseClass={ButtonBaseClass.ICON}
                        buttonRef={this.buttonRef}
                    >
                        {t("Edit")}
                    </Button>
                    {this.props.afterEdit}
                </>
            );
        }
    }

    private startEdit = () => {
        this.setState({
            editMode: true,
        });
    };

    private stopEdit = () => {
        this.setState({
            editMode: false,
        });
    };

    private handleOnChange = e => {
        this.setState({
            error: false,
            newName: e.value,
        });
    };

    private submit = () => {
        const newName = this.state.newName;
        if (newName !== "" && newName !== this.props.item.name) {
            this.setState(
                {
                    disabled: true,
                },
                () => {
                    const editResult = this.props.handleEdit(this.props.item);
                    if (editResult) {
                        // Success
                        this.setState({
                            editMode: false,
                            disabled: false,
                            error: false,
                            newName: "",
                        });
                    } else {
                        // Problem
                        this.setState({
                            error: editResult,
                            disabled: false,
                        });
                    }
                },
            );
        }
    };
}
