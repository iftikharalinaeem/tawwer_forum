/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";

interface ICommonHeadingProps {
    depth?: 1 | 2 | 3 | 4 | 5 | 6;
    className?: string;
}

interface IStringTitle extends ICommonHeadingProps {
    title: string;
}

interface IComponentTitle extends ICommonHeadingProps {
    children: JSX.Element | string;
}

type IHeadingProps = IStringTitle | IComponentTitle;

export default class PageTitle extends React.Component<IHeadingProps> {
    public static defaultProps = {
        depth: 2,
    };

    public render() {
        const Tag = `h${this.props.depth}`;
        const contents = "title" in this.props ? this.props.title : this.props.children;

        return (
            <Tag
                className={classNames(
                    "heading",
                    `heading-${this.props.depth}`,
                    { pageTitle: this.props.depth === 1 },
                    this.props.className,
                )}
            >
                {contents}
            </Tag>
        );
    }
}
