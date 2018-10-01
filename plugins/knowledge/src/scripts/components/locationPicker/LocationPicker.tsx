/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import Modal from "@knowledge/components/Modal";
import Frame from "@library/components/frame/Frame";
import FrameHeader from "@library/components/frame/FrameHeader";
import FrameBody from "@library/components/frame/FrameBody";
import FrameFooter from "@library/components/frame/FrameFooter";
import Button from "@dashboard/components/forms/Button";
import FramePanel from "@library/components/frame/FramePanel";
import NewFolder from "@knowledge/components/locationPicker/NewFolder";
import { newFolder } from "@library/components/Icons";
import FolderContents from "@knowledge/components/locationPicker/FolderContents";
import { ModalSizes } from "@knowledge/components/Modal";

interface IProps {
    exitHandler: () => void;
    className?: string;
    children: any;
    selectedCategory: any;
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
