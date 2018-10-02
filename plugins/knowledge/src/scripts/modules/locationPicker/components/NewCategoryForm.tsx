/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/application";
import Modal, { ModalSizes } from "@library/components/Modal";
import Button from "@library/components/forms/Button";
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
 * A modal based new form for adding a new category
 */
export default class NewCategoryForm extends React.Component<IProps, IState> {
    public state = {
        valid: false,
        categoryName: "",
        url: "",
    };

    public render() {
        return (
            <Modal size={ModalSizes.SMALL}>
                <Frame>
                    <FrameHeader closeFrame={this.props.exitHandler}>{t("New Folder")}</FrameHeader>
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
                            {t("Cancel")}
                        </Button>
                        <Button
                            title={t("New Folder")}
                            className="locationPicker-newFolder buttonPrimary"
                            onClick={this.tempClick}
                            disabled={!this.state.valid}
                        >
                            {t("Save")}
                        </Button>
                    </FrameFooter>
                </Frame>
            </Modal>
        );
    }

    /**
     * Dummy click handler until full functionality is introduced.
     */
    private tempClick = () => {
        alert("do click");
    };

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
