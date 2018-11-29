/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import Heading from "@library/components/Heading";
import { t } from "@library/application";
import { PanelWidget } from "@library/components/layouts/PanelLayout";

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
