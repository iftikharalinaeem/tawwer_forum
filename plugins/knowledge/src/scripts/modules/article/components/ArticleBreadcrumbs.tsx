/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import Breadcrumbs, { ICrumb } from "@library/components/Breadcrumbs";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";

interface IProps {}

export default class ArticleBreadcrumbs extends React.Component<IProps> {
    public render() {
        return (
            <PanelWidget>
                <Breadcrumbs>{this.dummyBreadcrumbData}</Breadcrumbs>
            </PanelWidget>
        );
    }

    /**
     * Get hardcoded breadcrumb data until we implement breadcrumbs at the API level.
     */
    private get dummyBreadcrumbData(): ICrumb[] {
        return [
            {
                name: "Home",
                url: "/kb",
            },
            {
                name: "two",
                url: "#",
            },
            {
                name: "three",
                url: "#",
            },
            {
                name: "four",
                url: "#",
            },
            {
                name: "five",
                url: "#",
            },
            {
                name: "six",
                url: "#",
            },
        ];
    }
}
