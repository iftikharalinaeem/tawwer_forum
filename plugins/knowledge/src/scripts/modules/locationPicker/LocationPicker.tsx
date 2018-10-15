/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { FramePanel, FrameFooter, FrameBody, FrameHeader, Frame } from "@library/components/frame";
import { newFolder } from "@library/components/Icons";
import { LocationContents, NewCategoryForm } from "@knowledge/modules/locationPicker/components";
import { ILocationPickerProps, withLocationPicker } from "@knowledge/modules/locationPicker/LocationPickerContext";
import { LoadStatus } from "@library/@types/api";

interface IProps extends ILocationPickerProps {
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
export class LocationPicker extends React.Component<IProps, IState> {
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
                        {this.props.navigatedCategory ? this.props.navigatedCategory.name : t("Category")}
                    </FrameHeader>
                    <FrameBody className="isSelfPadded">
                        <FramePanel>
                            <LocationContents
                                onCategoryNavigate={actions.navigateToCategory}
                                onItemSelect={actions.selectCategory}
                                selectedCategory={this.props.selectedCategory}
                                items={this.props.navigatedCategoryContents}
                                initialCategoryID={this.props.initialCategoryID}
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
                        <Button onClick={this.handleChoose}>{t("Choose")}</Button>
                    </FrameFooter>
                </Frame>
                {this.state.showNewCategoryModal && <NewCategoryForm exitHandler={this.hideNewFolderModal} />}
            </React.Fragment>
        );
    }

    /**
     * If the current navigated category has a valid parent we can navigate back to it.
     */
    private get canNavigateBack(): boolean {
        return this.props.navigatedCategory.parentID !== -1;
    }

    /**
     * Navigate one level up in the category hierarchy.
     */
    private goBack = () => {
        if (this.canNavigateBack) {
            void this.props.actions.navigateToCategory(this.props.navigatedCategory.parentID);
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

    private handleChoose = () => {
        this.props.actions.chooseCategory(this.props.selectedCategory.knowledgeCategoryID);
        this.props.onChoose();
    };
}

export default withLocationPicker(LocationPicker);
