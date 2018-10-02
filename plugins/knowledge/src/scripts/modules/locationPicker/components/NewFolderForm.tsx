/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import Modal, { ModalSizes } from "@knowledge/components/Modal";
import Button from "@dashboard/components/forms/Button";
import FramePanel from "@library/components/frame/FramePanel";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";
import { newFolder } from "@library/components/Icons";
import { Frame, FrameHeader, FrameBody, FrameFooter } from "@library/components/frame";

interface IProps {
    exitHandler: () => void;
    className?: string;
}

interface IState {
    valid: boolean;
    categoryName: string;
    url: string;
}

/**
 * This component allows to display and edit the location of the current page.
 * Calls the LocationChooser component when clicked.
 */
export default class NewFolderForm extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            valid: false,
            categoryName: "",
            url: "",
        };
    }

    public tempClick = () => {
        alert("do click");
    };

    public render() {
        return (
            <Modal size={ModalSizes.SMALL}>
                <Frame>
                    <FrameHeader onBackClick={this.tempClick} closeFrame={this.props.exitHandler}>
                        {t("New Folder")}
                    </FrameHeader>
                    <FrameBody>
                        <FramePanel>
                            <InputTextBlock
                                label={t("Name")}
                                placeholder={t("Example: Appearance")}
                                onChange={this.handleNameChange}
                                value={this.state.categoryName}
                            />
                            <InputTextBlock
                                label={t("URL Code")}
                                placeholder={t("Example: appearance")}
                                onChange={this.handleUrlCodeChange}
                                value={this.state.url}
                            />
                        </FramePanel>
                    </FrameBody>
                    <FrameFooter>
                        <Button className="locationPicker-validate" onClick={this.props.exitHandler}>
                            {t("Choose")}
                        </Button>
                        <Button
                            title={t("New Folder")}
                            className="locationPicker-newFolder"
                            onClick={this.tempClick}
                            disabled={!this.state.valid}
                        >
                            {newFolder()}
                        </Button>
                    </FrameFooter>
                </Frame>
            </Modal>
        );
    }

    /**
     * Change handler for the email input.
     */
    private handleNameChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { value } = event.target;
        this.setState({ categoryName: value });
    };

    /**
     * Change handler for the email input.
     */
    private handleUrlCodeChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { value } = event.target;
        this.setState({ url: value });
    };
}
