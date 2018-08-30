/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import className from "classnames";

export interface IPanelArea {
    className?: string;
    children?: JSX.Element;
    render?: boolean;
}

export default class PanelArea extends React.Component<IPanelArea> {
    public static defaultProps = {
        render: true,
    };
    public render() {
        if (this.props.render && this.props.children) {
            return <div className={className("panelArea", this.props.className)}>{this.props.children}</div>;
        } else {
            return null;
        }
    }
}
