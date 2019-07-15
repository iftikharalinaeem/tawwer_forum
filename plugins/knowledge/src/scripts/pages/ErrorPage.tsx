/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import TitleBar from "@library/headers/TitleBar";
import Container from "@library/layout/components/Container";
import { IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import { PanelWidget, PanelWidgetVerticalPadding } from "@library/layout/PanelLayout";
import DocumentTitle from "@library/routing/DocumentTitle";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import React from "react";
import PageErrorMessage, {
    getErrorCode,
    IErrorMessageProps,
    messageFromErrorCode,
} from "@knowledge/modules/common/PageErrorMessage";

export class ErrorPage extends React.Component<IProps> {
    public render() {
        const code = getErrorCode(this.props);
        const message = messageFromErrorCode(code);
        const classes = {
            inheritHeight: inheritHeightClass(),
        };

        return (
            <DocumentTitle title={message}>
                <TitleBar />
                <Container className={classes.inheritHeight}>
                    <PanelWidgetVerticalPadding className={classes.inheritHeight}>
                        <PanelWidget className={classes.inheritHeight}>
                            <PageErrorMessage {...this.props} className={classes.inheritHeight} />
                        </PanelWidget>
                    </PanelWidgetVerticalPadding>
                </Container>
            </DocumentTitle>
        );
    }
}

interface IProps extends IErrorMessageProps, IDeviceProps {}

export default withDevice(ErrorPage);
