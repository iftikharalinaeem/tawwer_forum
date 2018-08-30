/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";

interface IPageHeading {
    title: string;
}

export default class PageHeading extends React.Component<IPageHeading> {
    public render() {
        return <h1 className="pageTitle">{this.props.title}</h1>;
    }
}
