/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import Breadcrumbs, { ICrumb } from "@knowledge/components/Breadcrumbs";

interface IProps {}

export default class ArticleBreadcrumbs extends React.Component<IProps> {
    public render() {
        return <Breadcrumbs>{this.dummyBreadcrumbData}</Breadcrumbs>;
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
