/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import * as React from "react";
import { t } from "@library/application";
import { ButtonBaseClass } from "@library/components/forms/Button";
import DropDown from "@library/components/dropdown/DropDown";
import DropDownItemButton from "@library/components/dropdown/items/DropDownItemButton";
import DropDownItemLink from "@library/components/dropdown/items/DropDownItemLink";
import ModalConfirm from "@library/components/modal/ModalConfirm";

interface IProps {
    className?: string;
    url: string;
    deleteFunction: () => void;
}

interface IState {
    showDeleteDialogue: boolean;
}

/**
 * Implements actions to take on draft
 */
export default class DraftActions extends React.Component<IProps, IState> {
    private toggleButtonRef: React.RefObject<HTMLButtonElement> = React.createRef();

    public constructor(props) {
        super(props);
        this.state = {
            showDeleteDialogue: false,
        };
    }

    public render() {
        return (
            <React.Fragment>
                <DropDown
                    name={t("Draft Options")}
                    buttonClassName={ButtonBaseClass.CUSTOM}
                    renderLeft={true}
                    setExternalButtonRef={this.setButtonRef}
                    toggleButtonClassName="draftPreview-actionsToggle"
                    className="draftPreview-actions"
                >
                    <DropDownItemLink name={t("Edit")} to={this.props.url} className="draftPreview-option" />
                    <DropDownItemButton
                        name={t("Delete")}
                        onClick={this.openDeleteDialogue}
                        className="draftPreview-option"
                    />
                </DropDown>
                {this.state.showDeleteDialogue && (
                    <ModalConfirm
                        title={t("Delete Draft")}
                        onCancel={this.closeDeleteDialogue}
                        onConfirm={this.props.deleteFunction}
                        elementToFocusOnExit={this.toggleButtonRef.current! as HTMLElement}
                    >
                        {t("This is a non-destructive action. You will be able to restore your article if you wish.")}
                    </ModalConfirm>
                )}
            </React.Fragment>
        );
    }

    /**
     * Open the delete dialogue.
     */
    private openDeleteDialogue = () => {
        this.setState({ showDeleteDialogue: true });
    };

    /**
     * Close the delete dialogue.
     */
    private closeDeleteDialogue = () => {
        this.setState({ showDeleteDialogue: false });
    };

    /*
     * Set reference to button we need to target
     */
    private setButtonRef = (ref: React.RefObject<HTMLButtonElement>) => {
        this.toggleButtonRef = ref;
        this.forceUpdate();
    };
}
