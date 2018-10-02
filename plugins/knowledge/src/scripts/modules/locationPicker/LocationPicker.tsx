/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import Button from "@dashboard/components/forms/Button";
import { FramePanel, FrameFooter, FrameBody, FrameHeader, Frame } from "@library/components/frame";
import { newFolder } from "@library/components/Icons";
import { LocationContents } from "@knowledge/modules/locationPicker/components";
import NewFolderForm from "@knowledge/modules/locationPicker/components/NewFolderForm";

interface IProps {
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
export default class LocationPicker extends React.Component<IProps, IState> {
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
                    <FrameHeader onBackClick={this.tempClick} closeFrame={this.props.onCloseClick}>
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
