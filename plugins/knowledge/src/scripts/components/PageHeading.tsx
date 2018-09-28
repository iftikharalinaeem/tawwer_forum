/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import BackLink from "@knowledge/components/BackLink";
import Heading from "@knowledge/components/Heading";

interface IPageHeading {
    title: string;
    backUrl?: string | null; // back link
    className?: string;
    menu?: JSX.Element;
}

export default class PageHeading extends React.Component<IPageHeading> {
    public render() {
        return (
            <div className={classNames("pageHeading", this.props.className)}>
                <div className="pageHeading-main">
                    <BackLink url={this.props.backUrl} className="pageHeading-backLink" />
                    {/* Will not render if no url is passed */}
                    <Heading title={this.props.title} depth={1} />
                </div>
                {this.props.menu && <div className="pageHeading-actions">{this.props.menu}</div>}
            </div>
        );
    }
}
