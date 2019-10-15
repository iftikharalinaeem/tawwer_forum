/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import classNames from "classnames";
import * as React from "react";
import { metasClasses } from "@library/styles/metasStyles";
import { pageTitleClasses } from "@library/layout/pageHeadingStyles";
import { PageHeading } from "@library/layout/PageHeading";

export interface IPageTitle {
    title: string;
    actions?: React.ReactNode;
    meta?: React.ReactNode;
    className?: string;
    includeBackLink?: boolean;
    headingClassName?: string;
}

/**
 * Generates main title for page as well as possibly a back link and some meta information about the page
 */
export default class PageTitle extends React.Component<IPageTitle> {
    public static defaultProps = {
        includeBackLink: true,
    };

    public render() {
        const classes = pageTitleClasses();
        const classesMetas = metasClasses();
        return (
            <div className={classNames("pageTitleContainer", this.props.className)}>
                <PageHeading
                    actions={this.props.actions}
                    title={this.props.title}
                    includeBackLink={this.props.includeBackLink}
                    headingClassName={classNames(classes.root, this.props.headingClassName)}
                />
                {this.props.meta && (
                    <div className={classNames("pageMetas", "pageTitleContainer-metas", classesMetas.root)}>
                        {this.props.meta}
                    </div>
                )}
            </div>
        );
    }
}
