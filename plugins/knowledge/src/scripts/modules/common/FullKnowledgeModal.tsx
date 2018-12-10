/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { RouteComponentProps, withRouter } from "react-router";
import { Modal, ModalSizes } from "@library/components/modal";

interface IProps
    extends RouteComponentProps<{
            id?: string;
        }> {
    children: React.ReactNode;
    titleID: string;
    className?: string;
}

export class FullKnowledgeModal extends React.Component<IProps> {
    public render() {
        return (
            <Modal
                titleID={this.props.titleID}
                size={ModalSizes.FULL_SCREEN}
                exitHandler={this.navigateToBacklink}
                elementToFocusOnExit={document.activeElement as HTMLElement}
                className={this.props.className}
                isWholePage={true}
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
