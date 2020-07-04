/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import TitleBar from "@library/headers/TitleBar";
import Container from "@library/layout/components/Container";
import DocumentTitle from "@library/routing/DocumentTitle";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import React from "react";
import KbErrorMessages, {
    IKbErrorMessageProps,
    messageFromKbErrorCode,
} from "@knowledge/modules/common/KbErrorMessages";
import { getErrorCode } from "@library/errorPages/CoreErrorMessages";
import PanelWidgetVerticalPadding from "@vanilla/library/src/scripts/layout/components/PanelWidgetVerticalPadding";
import PanelWidget from "@vanilla/library/src/scripts/layout/components/PanelWidget";

export class KbErrorPage extends React.Component<IProps> {
    public render() {
        const code = getErrorCode(this.props);
        const message = messageFromKbErrorCode(code);
        const classes = {
            inheritHeight: inheritHeightClass(),
        };

        return (
            <DocumentTitle title={message}>
                <TitleBar />
                <Container className={classes.inheritHeight}>
                    <PanelWidgetVerticalPadding className={classes.inheritHeight}>
                        <PanelWidget className={classes.inheritHeight}>
                            <KbErrorMessages {...this.props} className={classes.inheritHeight} />
                        </PanelWidget>
                    </PanelWidgetVerticalPadding>
                </Container>
            </DocumentTitle>
        );
    }
}

interface IProps extends IKbErrorMessageProps {}
