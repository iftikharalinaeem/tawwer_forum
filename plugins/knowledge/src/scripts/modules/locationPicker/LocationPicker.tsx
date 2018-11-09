/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { FramePanel, FrameFooter, FrameBody, FrameHeader, Frame } from "@library/components/frame";
import { newFolder } from "@library/components/icons/common";
import { LocationContents, NewCategoryForm } from "@knowledge/modules/locationPicker/components";
import { ILPActionsProps } from "@knowledge/modules/locationPicker/LocationPickerActions";
import { ILPConnectedData } from "@knowledge/modules/locationPicker/LocationPickerModel";

interface IProps extends ILPActionsProps, ILPConnectedData {
    className?: string;
    onCloseClick: () => void;
    onChoose: () => void;
}

interface IState {
    showNewCategoryModal: boolean;
}

/**
 * Component for choosing a location for a new article.
 */
export default class LocationPicker extends React.Component<IProps, IState> {
    public state = {
        showNewCategoryModal: false,
    };

    public render() {
        const { actions } = this.props;

        return (
            <React.Fragment>
                <Frame>
                    <FrameHeader
                        className="isShadowed"
                        onBackClick={this.canNavigateBack ? this.goBack : undefined}
                        closeFrame={this.props.onCloseClick}
                    >
                        {this.props.navigatedCategory ? this.props.navigatedCategory.name : t("Knowledge Bases")}
                    </FrameHeader>
                    <FrameBody className="isSelfPadded">
                        <FramePanel>
                            <LocationContents
                                onCategoryNavigate={actions.navigateToCategory}
                                onItemSelect={actions.selectCategory}
                                selectedCategory={this.props.selectedCategory}
                                navigatedCategory={this.props.navigatedCategory}
                                chosenCategory={this.props.choosenCategory}
                                items={this.props.pageContents}
                            />
                        </FramePanel>
                    </FrameBody>
                    <FrameFooter className="isShadowed">
                        <Button
                            title={t("New Category")}
                            className="locationPicker-newFolder isSquare button-pushLeft"
                            onClick={this.showNewCategoryModal}
                            baseClass={ButtonBaseClass.STANDARD}
                        >
                            {newFolder()}
                        </Button>
                        <Button onClick={this.handleChoose} disabled={!this.canChoose}>
                            {t("Choose")}
                        </Button>
                    </FrameFooter>
                </Frame>
                {this.state.showNewCategoryModal && (
                    <NewCategoryForm
                        exitHandler={this.hideNewFolderModal}
                        parentCategory={this.props.navigatedCategory}
                    />
                )}
            </React.Fragment>
        );
    }

    /**
     * If the current navigated category has a valid parent we can navigate back to it.
     */
    private get canNavigateBack(): boolean {
        return this.props.navigatedCategory !== null;
    }

    /**
     * Navigate one level up in the category hierarchy.
     */
    private goBack = () => {
        if (this.canNavigateBack) {
            void this.props.actions.navigateToCategory(this.props.navigatedCategory!.parentID);
        }
    };

    /**
     * Display the modal for creating a new category.
     */
    private showNewCategoryModal = () => {
        this.setState({
            showNewCategoryModal: true,
        });
    };

    /**
     * Hide the
     */
    private hideNewFolderModal = () => {
        this.setState({
            showNewCategoryModal: false,
        });
    };

    private get canChoose(): boolean {
        return this.props.selectedCategory !== null;
    }

    private handleChoose = () => {
        if (this.canChoose) {
            this.props.actions.chooseCategory(this.props.selectedCategory!.knowledgeCategoryID);
            this.props.onChoose();
        }
    };
}
