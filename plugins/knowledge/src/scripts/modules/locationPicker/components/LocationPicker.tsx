/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import Modal from "@knowledge/components/Modal";
import Button from "@dashboard/components/forms/Button";
import { FramePanel, FrameFooter, FrameBody, FrameHeader, Frame } from "@library/components/frame";
import { newFolder } from "@library/components/Icons";
import NewFolder from "./NewFolder";
import FolderContents from "./FolderContents";
import { ModalSizes } from "@knowledge/components/Modal";

interface IProps {
    exitHandler: () => void;
    className?: string;
    // selectedCategory: any;
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
                <Modal
                    exitHandler={this.props.exitHandler}
                    size={ModalSizes.SMALL}
                    className={classNames(this.props.className)}
                    description={t("Choose a location for this page.")}
                >
                    <Frame>
                        <FrameHeader onBackClick={this.tempClick} closeFrame={this.props.exitHandler}>
                            {this.state.selectedCategory ? this.state.selectedCategory.name : t("Category")}
                        </FrameHeader>
                        <FrameBody>
                            <FramePanel>
                                <FolderContents initialCategoryID={1} />
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
                </Modal>
                {!!this.state.showNewFolderModal && <NewFolder exitHandler={this.hideNewFolderModal} />}
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
