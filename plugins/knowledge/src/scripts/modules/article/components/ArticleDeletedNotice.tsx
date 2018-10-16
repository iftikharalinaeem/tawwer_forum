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
            // <PanelWidget>
            <div className="message">
                <div className="message-body">
                    <h2>This article has been deleted.</h2>
                    <p>You can see this message because you have special permissions.</p>
                </div>
                <Button className="message-button">Restore</Button>
            </div>
            // </PanelWidget>
        );
    }
}
