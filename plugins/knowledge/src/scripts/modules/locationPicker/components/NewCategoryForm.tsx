/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import apiv2 from "@library/apiv2";
import { t } from "@library/dom/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Button from "@library/forms/Button";
import InputTextBlock from "@library/forms/InputTextBlock";
import { Frame, FrameBody, FrameFooter, FrameHeader } from "@library/layout/frame";
import FramePanel from "@library/layout/frame/FramePanel";
import { Modal } from "@library/modal";
import ModalSizes from "@library/modal/ModalSizes";
import getStore from "@library/redux/getStore";
import * as React from "react";
import { buttonClasses } from "@library/styles/buttonStyles";
import classNames from "classnames";
import { ButtonTypes } from "@library/styles/buttonStyles";

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
        const buttons = buttonClasses();
        return (
            <Modal
                titleID={this.titleID}
                size={ModalSizes.SMALL}
                exitHandler={this.props.exitHandler}
                elementToFocusOnExit={this.props.buttonRef.current! as HTMLElement}
            >
                <form onSubmit={this.handleFormSubmit}>
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
                        <FrameFooter selfPadded={true}>
                            <Button
                                baseClass={ButtonTypes.COMPACT}
                                className={classNames("locationPicker-validate")}
                                onClick={this.props.exitHandler}
                            >
                                {t("Cancel")}
                            </Button>
                            <Button
                                title={t("New Category")}
                                baseClass={ButtonTypes.COMPACT_PRIMARY}
                                className={classNames("locationPicker-newFolder")}
                                disabled={!this.state.valid}
                                type="submit"
                            >
                                {this.state.isSubmitLoading ? <ButtonLoader /> : t("Save")}
                            </Button>
                        </FrameFooter>
                    </Frame>
                </form>
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
    private handleFormSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        e.stopPropagation();

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
