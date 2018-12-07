/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { folderClosed, folderOpen, article } from "@library/components/icons/navigationManager";
import classNames from "classnames";
import { downTriangle, rightTriangle } from "@library/components/icons/common";
import { t } from "@library/application";
import ButtonSubmit from "@library/components/forms/ButtonSubmit";
import { IKbNavigationItem } from "@knowledge/@types/api/kbNavigation";

interface IProps {
    className?: string;
    currentName: string;
    focusOnExit: React.RefObject<HTMLButtonElement>;
    applyNewName: (newName: string) => void;
    cancel: (e) => void;
}

interface IState {
    newName: string;
}

export default class NavigationManagerNameForm extends React.Component<IProps, IState> {
    private inputRef: React.RefObject<HTMLInputElement> = React.createRef();

    public state: IState = {
        newName: this.props.currentName,
    };

    public render() {
        return (
            <form className={classNames("navigationManger-editMode", this.props.className)} onSubmit={this.submit}>
                <label>
                    <span className="sr-only">{t("New Name: ")}</span>
                    <input type="text" value={this.state.newName} onChange={this.handleOnChange} ref={this.inputRef} />
                </label>
                <Button
                    onClick={this.props.cancel}
                    baseClass={ButtonBaseClass.CUSTOM}
                    className="navigationManger-cancelRename"
                >
                    {t("Cancel")}
                </Button>
                <ButtonSubmit
                    className="navigationManger-submitRename"
                    baseClass={ButtonBaseClass.CUSTOM}
                    disabled={this.state.newName === this.props.currentName}
                >
                    {t("Apply")}
                </ButtonSubmit>
            </form>
        );
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
        this.props.applyNewName(this.state.newName);
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
