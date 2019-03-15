/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import Heading from "@library/layout/Heading";
import { t } from "@library/utility/appUtils";
import { PanelWidget } from "@library/layout/PanelLayout";

interface IProps {}

export default class ArticleActionsPanel extends React.Component<IProps> {
    public render() {
        return (
            <PanelWidget>
                <Heading title={t("Top Left")} />
                <p>{`${"(Actions)"}`}</p>
            </PanelWidget>
        );
    }
}
