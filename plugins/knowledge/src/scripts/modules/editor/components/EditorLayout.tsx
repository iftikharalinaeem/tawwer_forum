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
import PageHeading from "@library/components/PageHeading";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { Devices } from "@library/components/DeviceChecker";

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
            <Container className="inheritHeight">
                <PanelLayout growMiddleBottom={true} device={this.props.device}>
                    <PanelLayout.MiddleTop>
                        <PanelWidget>
                            <PageHeading backUrl={this.props.backUrl} title={t("Write Discussion")} />
                        </PanelWidget>
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <PanelWidget>{this.props.children}</PanelWidget>
                    </PanelLayout.MiddleBottom>
                </PanelLayout>
            </Container>
        );
    }
}

export default withDevice<IProps>(EditorLayout);
