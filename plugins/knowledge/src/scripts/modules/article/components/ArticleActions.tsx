/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import Heading from "@library/components/Heading";
import { t } from "@library/application";

interface IProps {}

export default class ArticleActions extends React.Component<IProps> {
    public render() {
        return (
            <PanelWidget>
                <Heading title={t("Top Left")} />
                <p>{`${"(Actions)"}`}</p>
            </PanelWidget>
        );
    }
}
