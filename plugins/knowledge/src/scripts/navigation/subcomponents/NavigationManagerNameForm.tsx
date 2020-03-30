/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import ButtonSubmit from "@library/forms/ButtonSubmit";
import classNames from "classnames";
import React from "react";
import { nextTick } from "process";
import { navigationManagerClasses } from "@knowledge/navigation/navigationManagerStyles";
import { ButtonTypes } from "@library/forms/buttonTypes";

interface IProps {
    className?: string;
    currentName: string;
    focusOnExit: React.RefObject<HTMLButtonElement>;
    applyNewName: (newName: string) => void;
    cancel: (event: React.SyntheticEvent) => void;
    isFolder: boolean;
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
        const classesNavigationManager = navigationManagerClasses();
        return (
            <form
                className={classNames(
                    "navigationManger-editMode",
                    this.props.className,
                    classesNavigationManager.editMode,
                )}
                onSubmit={this.submit}
            >
                <label className={classNames("navigationManger-text", classesNavigationManager.text)}>
                    <span className="sr-only">{t("New Name: ")}</span>
                    <input
                        maxLength={255}
                        type="text"
                        value={this.state.newName}
                        onChange={this.handleChange}
                        ref={this.inputRef}
                        onKeyDown={this.handleKeyDown}
                        className={classNames("navigationManger-input", classesNavigationManager.input, {
                            isFolder: this.props.isFolder,
                        })}
                    />
                </label>
                <Button
                    onClick={this.props.cancel}
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(
                        "navigationManger-cancelRename",
                        "navigationManager-action",
                        classesNavigationManager.action,
                    )}
                    tabIndex={0}
                >
                    {t("Cancel")}
                </Button>
                <ButtonSubmit
                    className={classNames(
                        "navigationManger-submitRename",
                        "navigationManager-action",
                        classesNavigationManager.action,
                        classesNavigationManager.submitRename,
                    )}
                    baseClass={ButtonTypes.CUSTOM}
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
            nextTick(() => {
                input.select();
            });
        }
    }

    private handleKeyDown = (event: React.KeyboardEvent) => {
        if (event.key === "Escape" && !event.shiftKey) {
            event.preventDefault();
            event.stopPropagation();
            this.props.cancel(event);
        }
    };

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
