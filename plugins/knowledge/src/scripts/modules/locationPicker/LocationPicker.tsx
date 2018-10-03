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
import { ILocationPickerProps, withLocationPicker } from "@knowledge/modules/locationPicker/state";
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
        const { items } = this.props;

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
                                onCategoryNavigate={this.props.navigateToCategory}
                                onItemSelect={this.props.selectCategory}
                                selectedCategory={this.props.selectedCategory}
                                initialCategoryID={1}
                                items={items}
                            />
                        </FramePanel>
                    </FrameBody>
                    <FrameFooter className="isShadowed">
                        <Button
                            disabled={this.props.items.status !== LoadStatus.SUCCESS}
                            className="button-pushLeft"
                            onClick={this.handleChoose}
                        >
                            {t("Choose")}
                        </Button>
                        <Button
                            title={t("New Category")}
                            disabled={this.props.items.status !== LoadStatus.SUCCESS}
                            className="locationPicker-newFolder isSquare"
                            onClick={this.showNewCategoryModal}
                            baseClass={ButtonBaseClass.STANDARD}
                        >
                            {newFolder()}
                        </Button>
                    </FrameFooter>
                </Frame>
                {this.state.showNewCategoryModal && <NewCategoryForm exitHandler={this.hideNewFolderModal} />}
            </React.Fragment>
        );
    }

    /**
     * Cleanup on unmount.
     */
    public componentWillUnmount() {
        this.props.resetNavigation();
    }

    private get canNavigateBack(): boolean {
        return this.props.navigatedCategory.parentID !== -1;
    }

    /**
     * Navigate one level up in the category hierarchy.
     */
    private goBack = () => {
        if (this.canNavigateBack) {
            this.props.navigateToCategory(this.props.navigatedCategory.parentID);
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
        this.props.chooseCategory(this.props.selectedCategory.knowledgeCategoryID);
        this.props.onChoose();
    };
}

export default withLocationPicker(LocationPicker);
