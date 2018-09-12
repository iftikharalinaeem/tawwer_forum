/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";

interface IHeading {
    title: string;
    depth?: 1 | 2 | 3 | 4 | 5 | 6;
    className?: string;
}

export default class PageTitle extends React.Component<IHeading> {
    public static defaultProps = {
        depth: 2,
    };

    public render() {
        const Tag = `h${this.props.depth}`;
        return (
            <Tag
                className={classNames(
                    "heading",
                    `heading-${this.props.depth}`,
                    { pageTitle: this.props.depth === 1 },
                    this.props.className,
                )}
            >
                {this.props.title}
            </Tag>
        );
    }
}
