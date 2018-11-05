/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/application";
import { Modal } from "@library/components/modal";
import Button from "@library/components/forms/Button";
import FramePanel from "@library/components/frame/FramePanel";
import InputTextBlock from "@library/components/forms/InputTextBlock";
import { newFolder } from "@library/components/Icons";
import { Frame, FrameHeader, FrameBody, FrameFooter } from "@library/components/frame";
import ModalSizes from "@library/components/modal/ModalSizes";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import ButtonLoader from "@library/components/ButtonLoader";
import { IKbCategoryFragment } from "@knowledge/@types/api/kbCategory";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import getStore from "@library/state/getStore";
import apiv2 from "@library/apiv2";
import { LoadStatus } from "@library/@types/api";
import { IStoreState } from "@knowledge/state/model";

interface IProps {
    exitHandler: () => void;
    className?: string;
    parentCategory: IKbCategoryFragment | null;
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
    public state = {
        valid: false,
        categoryName: "",
        url: "",
        isSubmitLoading: false,
    };

    private categoryActions = new CategoryActions(getStore().dispatch, apiv2);

    private id;

    public constructor(props) {
        super(props);
        this.id = uniqueIDFromPrefix("navigationItemList");
    }

    private get titleID() {
        return this.id + "-title";
    }

    public render() {
        return (
            <Modal titleID={this.titleID} size={ModalSizes.SMALL} exitHandler={this.props.exitHandler}>
                <Frame>
                    <FrameHeader id={this.titleID} closeFrame={this.props.exitHandler}>
                        {t("New Folder")}
                    </FrameHeader>
                    <FrameBody>
                        <FramePanel>
                            <InputTextBlock
                                label={t("Name")}
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
                            title={t("New Folder")}
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

    /**
     * Attempt to add category.
     */
    private handleFormSubmit = async () => {
        const parentCategoryID = this.props.parentCategory ? this.props.parentCategory.knowledgeCategoryID : -1;

        this.setState({
            isSubmitLoading: true,
        });

        await this.categoryActions.postCategory({
            name: this.state.categoryName,
            parentID: parentCategoryID,
        });

        this.props.exitHandler();
    };

    /**
     * Change handler for the name input.
     */
    private handleNameChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { value } = event.target;
        const state = {
            categoryName: value,
            valid: false,
        };
        if (value.length > 0) {
            state.valid = true;
        }
        this.setState(state);
    };

    /**
     * Change handler for the email input.
     */
    private handleUrlCodeChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { value } = event.target;
        this.setState({ url: value });
    };
}
