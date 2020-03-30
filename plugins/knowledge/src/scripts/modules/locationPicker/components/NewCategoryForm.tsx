/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import apiv2 from "@library/apiv2";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import InputTextBlock from "@library/forms/InputTextBlock";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import getStore from "@library/redux/getStore";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import classNames from "classnames";
import * as React from "react";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";

interface IProps {
    isVisible: boolean;
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
    private inputRef = React.createRef<HTMLInputElement>();

    public render() {
        const classesFrameFooter = frameFooterClasses();
        return (
            <Modal
                isVisible={this.props.isVisible}
                titleID={this.titleID}
                size={ModalSizes.SMALL}
                exitHandler={this.props.exitHandler}
                elementToFocus={this.inputRef.current as HTMLElement}
                elementToFocusOnExit={this.props.buttonRef.current! as HTMLElement}
            >
                <form onSubmit={this.handleFormSubmit}>
                    <Frame
                        header={
                            <FrameHeader
                                id={this.titleID}
                                closeFrame={this.props.exitHandler}
                                title={t("New Category")}
                            />
                        }
                        body={
                            <FrameBody>
                                <InputTextBlock
                                    label={t("New Category")}
                                    labelClassName="sr-only"
                                    className="isFirst isLast"
                                    inputProps={{
                                        inputRef: this.inputRef,
                                        value: this.state.categoryName,
                                        onChange: this.handleNameChange,
                                        placeholder: t("Example: Appearance"),
                                    }}
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight={true}>
                                <Button
                                    baseClass={ButtonTypes.TEXT}
                                    className={classesFrameFooter.actionButton}
                                    onClick={this.props.exitHandler}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button
                                    title={t("New Category")}
                                    baseClass={ButtonTypes.TEXT_PRIMARY}
                                    className={classesFrameFooter.actionButton}
                                    disabled={!this.state.valid}
                                    submit={true}
                                >
                                    {this.state.isSubmitLoading ? <ButtonLoader /> : t("Save")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
        );
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
