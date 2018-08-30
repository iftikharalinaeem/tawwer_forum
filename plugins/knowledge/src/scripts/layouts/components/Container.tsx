/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import className from "classnames";

export interface IContainer {
    className?: string;
    children?: JSX.Element;
    tag?: string;
}

export default class Container extends React.Component<IContainer> {
    public static defaultProps = {
        tag: "div",
    };

    public render() {
        if (this.props.children) {
            const Tag = `${this.props.tag}`;
            return <Tag className={className("container", this.props.className)}>{this.props.children}</Tag>;
        } else {
            return null;
        }
    }
}
