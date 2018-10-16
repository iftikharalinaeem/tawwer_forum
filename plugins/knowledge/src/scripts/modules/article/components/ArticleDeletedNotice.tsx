/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import Heading from "@library/components/Heading";
import { t } from "@library/application";
import Button from "@library/components/forms/Button";

interface IProps {}

export default class ArticleDeletedNotice extends React.Component<IProps> {
    public render() {
        const message = t("This article has been deleted. To restore it click here:");

        return (
            <div className="messages">
                <div className="message">
                    <div className="message-main">
                        <h2 className="message-title">This article has been deleted.</h2>
                        <p className="message-text">You can see this message because you have special permissions.</p>
                    </div>
                    <Button className="message-button buttonPrimary">Restore</Button>
                </div>
            </div>
        );
    }
}
