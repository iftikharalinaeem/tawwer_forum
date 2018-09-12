/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import className from "classnames";
import { initAllUserContent } from "@library/user-content";

interface IUserContent {
    className?: string;
    content: string;
}

export default class UserContent extends React.Component<IUserContent> {
    public render() {
        return (
            <div
                className={className("userContent", this.props.className)}
                dangerouslySetInnerHTML={{ __html: this.props.content }}
            />
        );
    }

    public componentDidMount() {
        initAllUserContent();
    }
}
