/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import className from "classnames";

interface IPanelWidget {
    className?: string;
    children: JSX.Element;
}

export default class PanelWidget extends React.Component<IPanelWidget> {
    public render() {
        return <div className={className("panelWidget", this.props.className)}>{this.props.children}</div>;
    }
}
