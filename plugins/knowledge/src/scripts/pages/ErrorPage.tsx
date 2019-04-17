/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ErrorMessage, {
    getErrorCode,
    IErrorMessageProps,
    messageFromErrorCode,
} from "@knowledge/modules/common/ErrorMessage";
import VanillaHeader from "@library/headers/VanillaHeader";
import Container from "@library/layout/components/Container";
import { IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import { PanelWidget, PanelWidgetVerticalPadding } from "@library/layout/PanelLayout";
import DocumentTitle from "@library/routing/DocumentTitle";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import React from "react";

export class ErrorPage extends React.Component<IProps> {
    public render() {
        const code = getErrorCode(this.props);
        const message = messageFromErrorCode(code);
        const classes = {
            inheritHeight: inheritHeightClass(),
        };

        return (
            <DocumentTitle title={message}>
                <VanillaHeader />
                <Container className={classes.inheritHeight}>
                    <PanelWidgetVerticalPadding className={classes.inheritHeight}>
                        <PanelWidget className={classes.inheritHeight}>
                            <ErrorMessage {...this.props} classNames={[classes.inheritHeight]} />
                        </PanelWidget>
                    </PanelWidgetVerticalPadding>
                </Container>
            </DocumentTitle>
        );
    }
}

interface IProps extends IErrorMessageProps, IDeviceProps {}

export default withDevice(ErrorPage);
