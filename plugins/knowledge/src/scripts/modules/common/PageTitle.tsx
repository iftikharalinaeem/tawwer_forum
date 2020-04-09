/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import classNames from "classnames";
import * as React from "react";
import { metasClasses } from "@library/styles/metasStyles";
import { PageHeading } from "@library/layout/PageHeading";
import { pageTitleClasses } from "@library/layout/pageTitleStyles";

export interface IPageTitle {
    title: React.ReactNode;
    actions?: React.ReactNode;
    meta?: React.ReactNode;
    className?: string;
    includeBackLink?: boolean;
    headingClassName?: string;
    chevronClass?: string;
    backLinkClass?: string;
    backLinkContainerClass?: string;
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
                    className={this.props.backLinkContainerClass}
                    chevronClass={this.props.chevronClass}
                    backLinkClass={this.props.backLinkClass}
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
