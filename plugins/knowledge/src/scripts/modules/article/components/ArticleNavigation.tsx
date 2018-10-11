/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { t } from "@library/application";
import Heading from "@library/components/Heading";

interface IProps {}

/**
 * Implements the site nav, to go in a layout template
 */
export default class ArticleNavigation extends React.Component<IProps> {
    public render() {
        return (
            <PanelWidget>
                <Heading title={t("Left Bottom")} />
                <p>{`${"(Navigation)"}`}</p>
            </PanelWidget>
        );
    }
}
