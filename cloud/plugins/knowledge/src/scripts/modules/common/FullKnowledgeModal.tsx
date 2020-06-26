/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { RouteComponentProps, withRouter } from "react-router";
import Modal, { PAGE_CONTAINER_ID } from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";

interface IProps
    extends RouteComponentProps<{
        id?: string;
    }> {
    children: React.ReactNode;
    titleID: string;
    className?: string;
    scrollable?: boolean;
}

export class FullKnowledgeModal extends React.Component<IProps> {
    public render() {
        return (
            <Modal
                isVisible={true}
                titleID={this.props.titleID}
                size={ModalSizes.FULL_SCREEN}
                exitHandler={this.navigateToBacklink}
                className={this.props.className}
                scrollable={this.props.scrollable}
            >
                {this.props.children}
            </Modal>
        );
    }

    /**
     * Route back to the previous location if its available.
     */
    private navigateToBacklink = () => {
        if (this.props.history.length > 1) {
            this.props.history.goBack();
        } else {
            this.props.history.push("/kb");
        }
    };
}

export default withRouter(FullKnowledgeModal);
