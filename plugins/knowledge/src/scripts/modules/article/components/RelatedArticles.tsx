/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { t } from "@library/application";
import Heading from "@library/components/Heading";

interface IProps {}

export default class RelatedArticles extends React.Component<IProps> {
    public render() {
        return (
            <PanelWidget>
                <Heading title={t("Right Bottom")} />
                <p>{`${"(Related Articles)"}`}</p>
            </PanelWidget>
        );
    }
}
