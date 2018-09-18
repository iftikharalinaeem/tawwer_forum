/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import AriaModal from "react-aria-modal";
import EditorLayout from "@knowledge/pages/editor/components/EditorLayout";
import { withRouter, RouteComponentProps } from "react-router-dom";

interface IProps extends RouteComponentProps<{}> {}

export class EditorPage extends React.Component<IProps> {
    public render() {
        if (this.isModal) {
            return (
                <AriaModal
                    includeDefaultStyles={false}
                    onExit={this.dismissModal}
                    applicationNode={this.getApplicationNode()}
                    titleText="Editor Page"
                    dialogClass="dialogue dialogue-full dialogue-editor"
                >
                    <EditorLayout />
                </AriaModal>
            );
        } else {
            return <EditorLayout />;
        }
    }

    /**
     * Whether or not the we are navigated inside of a router.
     */
    private get isModal(): boolean {
        const { location } = this.props;
        return !!(location && location.state && location.state.modal);
    }

    private getApplicationNode = () => {
        return document.getElementById("app")!;
    };

    private dismissModal = () => {
        this.props.history.push(this.props.location.state.lastLocation || "/");
    };
}

export default withRouter(EditorPage);
