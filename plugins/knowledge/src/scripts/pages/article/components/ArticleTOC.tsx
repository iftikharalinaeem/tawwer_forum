/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { t } from "@dashboard/application";
import PageHeading from "@knowledge/components/PageHeading";

interface IProps {}

export default class ArticleTOC extends React.Component<IProps> {
    public render() {
        return (
            <PanelWidget>
                <PageHeading title={t("Table of Contents")} />
            </PanelWidget>
        );
    }
}
