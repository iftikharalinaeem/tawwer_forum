/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbCategoryFragment } from "@knowledge/@types/api/kbCategory";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import apiv2 from "@library/apiv2";
import { t } from "@library/application";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import ButtonLoader from "@library/components/ButtonLoader";
import Button from "@library/components/forms/Button";
import InputTextBlock from "@library/components/forms/InputTextBlock";
import { Frame, FrameBody, FrameFooter, FrameHeader } from "@library/components/frame";
import FramePanel from "@library/components/frame/FramePanel";
import { Modal } from "@library/components/modal";
import ModalSizes from "@library/components/modal/ModalSizes";
import getStore from "@library/state/getStore";
import * as React from "react";

interface IProps {
    exitHandler: (e: React.SyntheticEvent) => void;
    className?: string;
    parentCategoryID: number;
    buttonRef: React.RefObject<HTMLButtonElement>;
    onSuccessfulSubmit?: () => Promise<void>;
}

interface IState {
    valid: boolean;
    categoryName: string;
    url: string;
    isSubmitLoading: boolean;
}

/**
 * A modal based new form for adding a new category
 */
export default class NewCategoryForm extends React.Component<IProps, IState> {
    public state: IState = {
        valid: false,
        categoryName: "",
        url: "",
        isSubmitLoading: false,
    };

    private categoryActions = new CategoryActions(getStore().dispatch, apiv2);

    private id = uniqueIDFromPrefix("navigationItemList");
    private inputRef = React.createRef<InputTextBlock>();

    public render() {
        return (
            <Modal
                titleID={this.titleID}
                size={ModalSizes.SMALL}
                exitHandler={this.props.exitHandler}
                elementToFocusOnExit={this.props.buttonRef.current! as HTMLElement}
            >
                <Frame>
                    <FrameHeader id={this.titleID} closeFrame={this.props.exitHandler} title={t("New Category")} />
                    <FrameBody>
                        <FramePanel>
                            <InputTextBlock
                                ref={this.inputRef}
                                label={t("New Category")}
                                labelClassName="sr-only"
                                className="isFirst isLast"
                                inputProps={{
                                    value: this.state.categoryName,
                                    onChange: this.handleNameChange,
                                    placeholder: t("Example: Appearance"),
                                }}
                            />
                        </FramePanel>
                    </FrameBody>
                    <FrameFooter>
                        <Button className="locationPicker-validate" onClick={this.props.exitHandler}>
                            {t("Cancel")}
                        </Button>
                        <Button
                            title={t("New Category")}
                            className="locationPicker-newFolder buttonPrimary"
                            disabled={!this.state.valid}
                            onClick={this.handleFormSubmit}
                        >
                            {this.state.isSubmitLoading ? <ButtonLoader /> : t("Save")}
                        </Button>
                    </FrameFooter>
                </Frame>
            </Modal>
        );
    }

    public componentDidMount() {
        this.inputRef.current!.focus();
    }

    private get titleID() {
        return this.id + "-title";
    }

    /**
     * Attempt to add category.
     */
    private handleFormSubmit = async e => {
        const { parentCategoryID } = this.props;

        this.setState({
            isSubmitLoading: true,
        });

        await this.categoryActions.postCategory({
            name: this.state.categoryName,
            parentID: parentCategoryID,
        });

        if (this.props.onSuccessfulSubmit) {
            await this.props.onSuccessfulSubmit();
        }

        this.props.exitHandler(e);
    };

    /**
     * Change handler for the name input.
     */
    private handleNameChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { value } = event.target;
        this.setState({
            categoryName: value,
            valid: value.length > 0,
        });
    };
}
