/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import className from "classnames";

export interface IMobileMenu {
    className?: string;
    render?: boolean;
    children?: JSX.Element;
}

export default class MobileMenu extends React.Component<IMobileMenu> {
    public render() {
        if (this.props.children && this.props.render) {
            return (
                <React.Fragment>
                    <div className={className("mobileMenu", this.props.className)}>{this.props.children}</div>
                </React.Fragment>
            );
        } else {
            return null;
        }
    }
}
