/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout from "@knowledge/layouts/PanelLayout";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { Devices } from "@library/components/DeviceChecker";
import EditorHeader from "./EditorHeader";
import { withRouter, RouteComponentProps } from "react-router-dom";

interface IProps {
    device: Devices;
    backUrl: string | null;
    children: React.ReactNode;
}

/**
 * Page layout for the Editor.
 */
export class EditorLayout extends React.Component<IProps> {
    public render() {
        return (
            <React.Fragment>
                <EditorHeader backUrl={this.props.backUrl} device={this.props.device} canSubmit={true} />
                <Container className="inheritHeight">
                    <h1 className="sr-only">{t("Write Discussion")}</h1>
                    <PanelLayout className="isOneCol" growMiddleBottom={true} device={this.props.device}>
                        <PanelLayout.MiddleBottom>
                            <PanelWidget>{this.props.children}</PanelWidget>
                        </PanelLayout.MiddleBottom>
                    </PanelLayout>
                </Container>
            </React.Fragment>
        );
    }
}

export default withDevice<IProps>(EditorLayout);
