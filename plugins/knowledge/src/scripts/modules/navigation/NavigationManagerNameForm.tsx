/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import ButtonSubmit from "@library/components/forms/ButtonSubmit";
import classNames from "classnames";
import React from "react";

interface IProps {
    className?: string;
    currentName: string;
    focusOnExit: React.RefObject<HTMLButtonElement>;
    applyNewName: (newName: string) => void;
    cancel: (e: React.MouseEvent) => void;
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
                <label className="navigationManger-text">
                    <span className="sr-only">{t("New Name: ")}</span>
                    <input type="text" value={this.state.newName} onChange={this.handleChange} ref={this.inputRef} />
                </label>
                <Button
                    onClick={this.props.cancel}
                    baseClass={ButtonBaseClass.CUSTOM}
                    className="navigationManger-cancelRename navigationManager-action"
                    tabIndex={0}
                >
                    {t("Cancel")}
                </Button>
                <ButtonSubmit
                    className="navigationManger-submitRename navigationManager-action"
                    baseClass={ButtonBaseClass.CUSTOM}
                    disabled={this.isSubmitDisabled}
                    tabIndex={0}
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

    private get isSubmitDisabled(): boolean {
        return this.state.newName === this.props.currentName || this.state.newName === "";
    }

    private submit = e => {
        e.preventDefault();
        e.stopPropagation();
        this.props.applyNewName(this.state.newName);
    };

    private handleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        event.preventDefault();
        this.setState({
            newName: event.target.value,
        });
    };
}
