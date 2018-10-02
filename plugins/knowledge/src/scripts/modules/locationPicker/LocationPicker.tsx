/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/application";
import Button from "@library/components/forms/Button";
import { FramePanel, FrameFooter, FrameBody, FrameHeader, Frame } from "@library/components/frame";
import { newFolder } from "@library/components/Icons";
import { LocationContents } from "@knowledge/modules/locationPicker/components";
import { ILocationPickerProps, withLocationPicker } from "@knowledge/modules/locationPicker/state";
import NewFolderForm from "@knowledge/modules/locationPicker/components/NewFolderForm";

interface IProps extends ILocationPickerProps {
    className?: string;
    onCloseClick: () => void;
}

interface IState {
    selectedCategory?: any;
    showNewFolderModal: boolean;
}

/**
 * This component allows to display and edit the location of the current page.
 * Calls the LocationChooser component when clicked.
 */
export class LocationPicker extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            showNewFolderModal: false,
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
                            className="locationPicker-validate"
                            onClick={this.tempClick}
                        >
                            {t("Choose")}
                        </Button>
                        <Button
                            title={t("New Folder")}
                            className="locationPicker-newFolder"
                            onClick={this.showNewFolderModal}
                        >
                            {newFolder()}
                        </Button>
                    </FrameFooter>
                </Frame>
                {this.state.showNewFolderModal && <NewFolderForm exitHandler={this.hideNewFolderModal} />}
            </React.Fragment>
        );
    }

    private goBack = () => {
        const { navigateToCategory, locationBreadcrumb } = this.props;
        if (locationBreadcrumb.length < 2) {
            return;
        }

        const lastCategory = locationBreadcrumb[locationBreadcrumb.length - 1];
        navigateToCategory(lastCategory.parentID);
    };

    private showNewFolderModal = () => {
        this.setState({
            showNewFolderModal: true,
        });
    };

    private hideNewFolderModal = () => {
        this.setState({
            showNewFolderModal: false,
        });
    };
}

export default withLocationPicker(LocationPicker);
