/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import PageHeading from "@library/components/PageHeading";
import classNames from "classnames";
import * as React from "react";

export interface IPageTitle {
    title: string;
    actions?: React.ReactNode;
    meta?: React.ReactNode;
    className?: string;
    includeBackLink?: boolean;
}

/**
 * Generates main title for page as well as possibly a back link and some meta information about the page
 */
export default class PageTitle extends React.Component<IPageTitle> {
    public static defaultProps = {
        includeBackLink: true,
    };

    public render() {
        return (
            <div className={classNames("pageTitleContainer", this.props.className)}>
                <PageHeading
                    actions={this.props.actions}
                    title={this.props.title}
                    includeBackLink={this.props.includeBackLink}
                />
                {this.props.meta && <div className="pageMetas metas pageTitleContainer-metas">{this.props.meta}</div>}
            </div>
        );
    }
}
