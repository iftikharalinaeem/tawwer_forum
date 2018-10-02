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

interface IProps extends ILocationPickerProps {
    className?: string;
    onCloseClick: () => void;
}

interface IState {
    selectedCategory?: any;
    showNewCategoryModal: boolean;
}

/**
 * Component for choosing a location for a new article.
 */
export class LocationPicker extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            showNewCategoryModal: false,
        };
    }

    public tempClick = () => {
        alert("do click");
    };

    public render() {
        return (
            <React.Fragment>
                <Frame>
                    <FrameHeader onBackClick={this.goBack} closeFrame={this.props.onCloseClick}>
                        {this.state.selectedCategory ? this.state.selectedCategory.name : t("Category")}
                    </FrameHeader>
                    <FrameBody>
                        <FramePanel>
                            <LocationContents initialCategoryID={1} />
                        </FramePanel>
                    </FrameBody>
                    <FrameFooter>
                        <Button
                            disabled={!!this.state.selectedCategory}
                            className="button-pushLeft"
                            onClick={this.tempClick}
                        >
                            {t("Choose")}
                        </Button>
                        <Button
                            title={t("New Category")}
                            className="locationPicker-newFolder"
                            onClick={this.showNewCategoryModal}
                            baseClass={ButtonBaseClass.ICON_BORDERED}
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
     * Navigate one level up in the category hierarchy.
     */
    private goBack = () => {
        const { navigateToCategory, locationBreadcrumb } = this.props;
        if (locationBreadcrumb.length < 2) {
            return;
        }

        const lastCategory = locationBreadcrumb[locationBreadcrumb.length - 1];
        navigateToCategory(lastCategory.parentID);
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
}

export default withLocationPicker(LocationPicker);
